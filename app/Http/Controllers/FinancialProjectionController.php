<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\FinancialProjection;
use App\Models\FinancialProjectionAttachment;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FinancialProjectionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crud/FinancialProjections', [
            'projections' => FinancialProjection::with(['department:id,name', 'items', 'attachments'])->latest()->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        return $this->saveProjection($request, $data);
    }

    public function update(Request $request, FinancialProjection $financialProjection)
    {
        $data = $this->validated($request);
        return $this->saveProjection($request, $data, $financialProjection);
    }

    public function destroy(FinancialProjection $financialProjection)
    {
        foreach ($financialProjection->attachments as $attachment) Storage::disk('local')->delete($attachment->file_path);
        $financialProjection->delete();
        return back()->with('success', 'Proyección eliminada.');
    }

    public function showAttachment(FinancialProjectionAttachment $attachment)
    {
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return response()->file(Storage::disk('local')->path($attachment->file_path), [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->file_name).'"',
        ]);
    }

    public function convertToPurchase(FinancialProjection $financialProjection)
    {
        abort_unless($financialProjection->status === 'Aprobada', 422, 'Solo se pueden convertir proyecciones aprobadas.');

        DB::transaction(function () use ($financialProjection) {
            foreach ($financialProjection->items as $item) {
                Purchase::create([
                    'department_id' => $financialProjection->department_id,
                    'item' => $item->item,
                    'supplier' => $item->supplier,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total_amount' => $item->total_amount,
                    'requested_at' => now()->toDateString(),
                    'expected_at' => $financialProjection->expected_at?->toDateString(),
                    'status' => 'Pendiente',
                    'notes' => 'Generada desde proyección: '.$financialProjection->title,
                ]);
            }
            $financialProjection->update(['status' => 'Ejecutada']);
        });

        return back()->with('success', 'La proyección se convirtió en solicitudes de compra.');
    }

    private function saveProjection(Request $request, array $data, ?FinancialProjection $projection = null)
    {
        $wasCreated = $projection === null;
        $items = $data['items'];
        unset($data['items'], $data['attachments']);
        $data['created_by'] ??= $request->user()->id;
        $data['total_amount'] = collect($items)->sum(fn ($item) => (float) $item['quantity'] * (float) $item['unit_price']);

        DB::transaction(function () use ($request, $data, $items, $projection, $wasCreated) {
            $projection ??= FinancialProjection::create($data);
            if (!$wasCreated) $projection->update($data);
            $projection->items()->delete();
            $projection->items()->createMany(collect($items)->map(fn ($item) => [...$item, 'total_amount' => $item['quantity'] * $item['unit_price']])->all());
            foreach ($request->file('attachments', []) as $file) {
                $projection->attachments()->create([
                    'uploaded_by' => $request->user()->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $file->store('financial-projections', 'local'),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        });

        return back()->with('success', $wasCreated ? 'Proyección creada.' : 'Proyección actualizada.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'], 'title' => ['required', 'string', 'max:255'],
            'justification' => ['nullable', 'string'], 'expected_at' => ['nullable', 'date'],
            'priority' => ['required', 'in:Baja,Media,Alta'], 'status' => ['required', 'in:Borrador,En revisión,Aprobada,Rechazada,Ejecutada'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'], 'items.*.item' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'], 'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.supplier' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array', 'max:10'], 'attachments.*' => ['file', 'mimes:pdf,xlsx,xls,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);
    }
}
