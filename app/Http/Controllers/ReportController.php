<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crud/Reports', [
            'reports' => Report::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        Report::create($this->validated($request));

        return back()->with('success', 'Reporte creado.');
    }

    public function update(Request $request, Report $report)
    {
        $report->update($this->validated($request));

        return back()->with('success', 'Reporte actualizado.');
    }

    public function destroy(Report $report)
    {
        $report->delete();

        return back()->with('success', 'Reporte eliminado.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'period_start' => ['nullable', 'date'],
            'period_end' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
            'generated_by' => ['nullable', 'string', 'max:255'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
