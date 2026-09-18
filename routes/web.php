<?php

use App\Models\Department;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FinancialMovementController;
use App\Http\Controllers\FinancialProjectionController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserController;
use App\Models\FinancialMovement;
use App\Models\InventoryItem;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
});

Route::get('/dashboard', function () {
    $monthStart = now()->startOfMonth();
    $monthEnd = now()->endOfMonth();
    $previousMonthStart = now()->subMonthNoOverflow()->startOfMonth();
    $previousMonthEnd = now()->subMonthNoOverflow()->endOfMonth();

    $monthlyIncome = (float) FinancialMovement::query()
        ->where('type', 'Ingreso')
        ->whereBetween('movement_date', [$monthStart, $monthEnd])
        ->sum('amount');

    $previousMonthlyIncome = (float) FinancialMovement::query()
        ->where('type', 'Ingreso')
        ->whereBetween('movement_date', [$previousMonthStart, $previousMonthEnd])
        ->sum('amount');

    $monthlyExpenses = (float) FinancialMovement::query()
        ->where('type', 'Gasto')
        ->whereBetween('movement_date', [$monthStart, $monthEnd])
        ->sum('amount');

    $pendingPurchases = Purchase::query()->where('status', 'Pendiente');
    $pendingPurchasesAmount = (float) (clone $pendingPurchases)->sum('total_amount');
    $pendingPurchasesCount = (clone $pendingPurchases)->count();
    $lowInventoryCount = InventoryItem::query()
        ->whereColumn('quantity', '<=', 'minimum_stock')
        ->count();
    $totalBudget = (float) Department::query()->sum('monthly_budget');

    $cashFlowStart = now()->startOfMonth()->subMonths(5);
    $cashFlowRows = FinancialMovement::query()
        ->selectRaw("DATE_FORMAT(movement_date, '%Y-%m-01') as month_key")
        ->selectRaw("SUM(CASE WHEN type = 'Ingreso' THEN amount ELSE 0 END) as ingresos")
        ->selectRaw("SUM(CASE WHEN type = 'Gasto' THEN amount ELSE 0 END) as gastos")
        ->where('movement_date', '>=', $cashFlowStart)
        ->groupBy('month_key')
        ->orderBy('month_key')
        ->get()
        ->keyBy('month_key');

    $cashFlow = collect(range(0, 5))->map(function (int $offset) use ($cashFlowStart, $cashFlowRows) {
        $month = $cashFlowStart->copy()->addMonths($offset);
        $monthKey = $month->format('Y-m-01');
        $row = $cashFlowRows->get($monthKey);

        return [
            'month' => Carbon::parse($monthKey)->locale('es')->isoFormat('MMM'),
            'ingresos' => (float) ($row->ingresos ?? 0),
            'gastos' => (float) ($row->gastos ?? 0),
        ];
    })->values();

    $sectors = Department::query()
        ->leftJoin('financial_movements', function ($join) use ($monthStart, $monthEnd) {
            $join->on('departments.id', '=', 'financial_movements.department_id')
                ->where('financial_movements.type', 'Gasto')
                ->whereBetween('financial_movements.movement_date', [$monthStart, $monthEnd]);
        })
        ->select('departments.name')
        ->selectRaw('COALESCE(SUM(financial_movements.amount), 0) as value')
        ->groupBy('departments.id', 'departments.name')
        ->havingRaw('value > 0')
        ->orderByDesc('value')
        ->limit(6)
        ->get();

    $latestMovements = FinancialMovement::query()
        ->with('department:id,name')
        ->latest('movement_date')
        ->limit(8)
        ->get(['id', 'department_id', 'concept', 'amount', 'status', 'movement_date']);

    return Inertia::render('Dashboard', [
        'metrics' => [
            'monthlyIncome' => $monthlyIncome,
            'previousMonthlyIncome' => $previousMonthlyIncome,
            'monthlyExpenses' => $monthlyExpenses,
            'pendingPurchasesAmount' => $pendingPurchasesAmount,
            'pendingPurchasesCount' => $pendingPurchasesCount,
            'netFlow' => $monthlyIncome - $monthlyExpenses,
            'totalBudget' => $totalBudget,
            'lowInventoryCount' => $lowInventoryCount,
        ],
        'cashFlow' => $cashFlow,
        'sectors' => $sectors,
        'latestMovements' => $latestMovements,
        'departments' => Department::query()
            ->withSum([
                'financialMovements as spent' => fn ($query) => $query
                    ->where('type', 'Gasto')
                    ->whereBetween('movement_date', [$monthStart, $monthEnd]),
            ], 'amount')
            ->orderBy('name')
            ->get(['id', 'name', 'manager', 'monthly_budget', 'status']),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::resource('departments', DepartmentController::class)->except(['create', 'show', 'edit']);
    Route::resource('financial-movements', FinancialMovementController::class)->except(['create', 'show', 'edit']);
    Route::get('/financial-movements/{financialMovement}/image', [FinancialMovementController::class, 'image'])->name('financial-movements.image');
    Route::post('/financial-movements/analyze-document', [FinancialMovementController::class, 'analyze'])->name('financial-movements.analyze');
    Route::get('/financial-movements/{financialMovement}/support', [FinancialMovementController::class, 'support'])->name('financial-movements.support');
    Route::resource('financial-projections', FinancialProjectionController::class)->except(['create', 'show', 'edit']);
    Route::post('/financial-projections/{financialProjection}/convert-to-purchase', [FinancialProjectionController::class, 'convertToPurchase'])
        ->name('financial-projections.convert-to-purchase');
    Route::get('/financial-projection-attachments/{attachment}', [FinancialProjectionController::class, 'showAttachment'])
        ->name('financial-projections.attachments.show');
    Route::resource('purchases', PurchaseController::class)->except(['create', 'show', 'edit']);
    Route::resource('inventory-items', InventoryItemController::class)->except(['create', 'show', 'edit']);
    Route::get('/inventory-items/{inventoryItem}/image', [InventoryItemController::class, 'image'])->name('inventory-items.image');
    Route::resource('reports', ReportController::class)->except(['create', 'show', 'edit']);
    Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
