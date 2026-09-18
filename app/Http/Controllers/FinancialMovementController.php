<?php

namespace App\Http\Controllers;

use App\Models\FinancialMovement;
use App\Models\Department;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Services\Ocr\FinancialDocumentParser;
use App\Services\Ocr\OcrService;
use App\Services\Ocr\ImagePreprocessor;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Throwable;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinancialMovementController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Crud/FinancialMovements', [
            'movements' => FinancialMovement::with('department:id,name')->latest()->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'ocrDraft' => $request->query('ocr_token') ? Arr::only(session('ocr_drafts.'.$request->query('ocr_token'), []), ['token', 'document_type', 'invoice_number', 'supplier_name', 'supplier_tax_id', 'currency', 'date', 'subtotal', 'discount_amount', 'tax_amount', 'total_amount', 'line_items', 'totals_valid', 'totals_difference', 'confidence']) : null,
        ]);
    }

    public function analyze(Request $request, OcrService $ocr, FinancialDocumentParser $parser, ImagePreprocessor $preprocessor)
    {
        $data = $request->validate(['document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240']]);
        $file = $data['document'];
        $path = $file->store('financial-ocr', 'local');

        try {
            $prepared = $preprocessor->prepare(Storage::disk('local')->path($path), $file->getMimeType(), $file->getClientOriginalName());
            $text = $ocr->extract($prepared['path'], $prepared['mime_type'], $prepared['original_name']);
            if ($prepared['temporary']) @unlink($prepared['path']);
            $draft = $parser->parse($text);
            $draft['support_path'] = $path;
            $draft['support_name'] = $file->getClientOriginalName();
            $draft['support_mime_type'] = $file->getMimeType();
            $draft['token'] = (string) Str::uuid();
            session()->put('ocr_drafts.'.$draft['token'], $draft);
            return response()->json(['draft' => Arr::except($draft, ['support_path', 'support_name', 'support_mime_type', 'raw_text'])]);
        } catch (Throwable) {
            if (isset($prepared) && ($prepared['temporary'] ?? false)) @unlink($prepared['path']);
            Storage::disk('local')->delete($path);
            return response()->json(['message' => 'No pudimos leer correctamente este documento. Puedes intentar con otra imagen o registrar el gasto manualmente.'], 422);
        }
    }

    public function support(FinancialMovement $financialMovement)
    {
        abort_unless($financialMovement->support_path && Storage::disk('local')->exists($financialMovement->support_path), 404);
        return response()->file(Storage::disk('local')->path($financialMovement->support_path), [
            'Content-Type' => $financialMovement->support_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($financialMovement->support_name ?: 'documento').'"',
        ]);
    }

    public function image(FinancialMovement $financialMovement)
    {
        abort_unless($financialMovement->image_path && Storage::disk('local')->exists($financialMovement->image_path), 404);
        return response()->file(Storage::disk('local')->path($financialMovement->image_path));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $ocrToken = $data['ocr_token'] ?? null;
        unset($data['ocr_token']);
        $draft = $ocrToken ? session('ocr_drafts.'.$ocrToken) : null;
        if ($draft) {
            foreach (['supplier_name', 'supplier_tax_id', 'invoice_number', 'subtotal', 'tax_amount', 'discount_amount', 'currency', 'support_path', 'support_name', 'support_mime_type'] as $key) $data[$key] = $draft[$key] ?? null;
            $data['source'] = 'ocr';
            session()->forget('ocr_drafts.'.$ocrToken);
        }
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('financial-movements', 'local');
        }

        FinancialMovement::create($data);

        return back()->with('success', 'Movimiento creado.');
    }

    public function update(Request $request, FinancialMovement $financialMovement)
    {
        $data = $this->validated($request);
        unset($data['image']);

        if ($request->hasFile('image')) {
            if ($financialMovement->image_path) {
                Storage::disk('local')->delete($financialMovement->image_path);
            }

            $data['image_path'] = $request->file('image')->store('financial-movements', 'local');
        }

        $financialMovement->update($data);

        return back()->with('success', 'Movimiento actualizado.');
    }

    public function destroy(FinancialMovement $financialMovement)
    {
        if ($financialMovement->image_path) {
            Storage::disk('local')->delete($financialMovement->image_path);
        }

        $financialMovement->delete();

        return back()->with('success', 'Movimiento eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'concept' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['Ingreso', 'Gasto'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'movement_date' => ['required', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:100', Rule::exists('payment_methods', 'code')->where('is_active', true)],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
            'ocr_token' => ['nullable', 'string', 'max:80'],
            'supplier_name' => ['nullable', 'string', 'max:255'], 'supplier_tax_id' => ['nullable', 'string', 'max:30'],
            'invoice_number' => ['nullable', 'string', 'max:100'], 'subtotal' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'], 'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'], 'source' => ['nullable', 'string', 'max:30'],
        ]);
    }
}
