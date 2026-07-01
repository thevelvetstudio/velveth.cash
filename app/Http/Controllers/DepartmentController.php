<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crud/Departments', [
            'departments' => Department::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        Department::create($this->validated($request));

        return back()->with('success', 'Departamento creado.');
    }

    public function update(Request $request, Department $department)
    {
        $department->update($this->validated($request, $department));

        return back()->with('success', 'Departamento actualizado.');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return back()->with('success', 'Departamento eliminado.');
    }

    private function validated(Request $request, ?Department $department = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('departments', 'code')->ignore($department)],
            'manager' => ['nullable', 'string', 'max:255'],
            'monthly_budget' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
