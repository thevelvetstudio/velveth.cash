<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crud/Purchases', [
            'purchases' => Purchase::with('department:id,name')->latest()->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        Purchase::create($this->validated($request));

        return back()->with('success', 'Compra creada.');
    }

    public function update(Request $request, Purchase $purchase)
    {
        $purchase->update($this->validated($request));

        return back()->with('success', 'Compra actualizada.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->delete();

        return back()->with('success', 'Compra eliminada.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'item' => ['required', 'string', 'max:255'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'requested_at' => ['required', 'date'],
            'expected_at' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['total_amount'] = $data['quantity'] * $data['unit_price'];

        return $data;
    }
}
