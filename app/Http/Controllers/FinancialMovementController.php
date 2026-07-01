<?php

namespace App\Http\Controllers;

use App\Models\FinancialMovement;
use App\Models\Department;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FinancialMovementController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crud/FinancialMovements', [
            'movements' => FinancialMovement::with('department:id,name')->latest()->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('financial-movements', 'public');
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
                Storage::disk('public')->delete($financialMovement->image_path);
            }

            $data['image_path'] = $request->file('image')->store('financial-movements', 'public');
        }

        $financialMovement->update($data);

        return back()->with('success', 'Movimiento actualizado.');
    }

    public function destroy(FinancialMovement $financialMovement)
    {
        if ($financialMovement->image_path) {
            Storage::disk('public')->delete($financialMovement->image_path);
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
        ]);
    }
}
