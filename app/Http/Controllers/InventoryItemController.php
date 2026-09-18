<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryCategory;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InventoryItemController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crud/InventoryItems', [
            'items' => InventoryItem::with(['department:id,name', 'categoryRelation:id,name'])->latest()->get(),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'categories' => InventoryCategory::orderBy('name')->get(['id', 'name', 'department_id']),
        ]);
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:inventory_categories,name'],
            'department_id' => ['required', 'exists:departments,id'],
        ]);
        return response()->json(InventoryCategory::create($data), 201);
    }

    public function image(InventoryItem $inventoryItem)
    {
        abort_unless($inventoryItem->image_path && Storage::disk('local')->exists($inventoryItem->image_path), 404);
        return response()->file(Storage::disk('local')->path($inventoryItem->image_path));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['sku'] = $this->generateSku($data['name']);
        $data['category'] = $this->categoryName($data['category_id'] ?? null);
        unset($data['image']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('inventory-items', 'local');
        }

        InventoryItem::create($data);

        return back()->with('success', 'Item de inventario creado.');
    }

    public function update(Request $request, InventoryItem $inventoryItem)
    {
        $data = $this->validated($request, $inventoryItem);
        $data['category'] = $this->categoryName($data['category_id'] ?? null) ?? $inventoryItem->category;
        unset($data['image']);

        if ($request->hasFile('image')) {
            if ($inventoryItem->image_path) {
                Storage::disk('local')->delete($inventoryItem->image_path);
            }

            $data['image_path'] = $request->file('image')->store('inventory-items', 'local');
        }

        $inventoryItem->update($data);

        return back()->with('success', 'Item de inventario actualizado.');
    }

    public function destroy(InventoryItem $inventoryItem)
    {
        if ($inventoryItem->image_path) {
            Storage::disk('local')->delete($inventoryItem->image_path);
        }

        $inventoryItem->delete();

        return back()->with('success', 'Item de inventario eliminado.');
    }

    private function validated(Request $request, ?InventoryItem $inventoryItem = null): array
    {
        return $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:inventory_categories,id'],
            'category' => ['nullable', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);
    }

    private function generateSku(string $name): string
    {
        $prefix = Str::upper(Str::limit(Str::slug($name, '-'), 50, '')) ?: 'ITEM';
        return $prefix.'-'.Str::upper(str_replace('-', '', (string) Str::uuid()));
    }

    private function categoryName(?int $categoryId): ?string
    {
        return $categoryId ? InventoryCategory::find($categoryId)?->name : null;
    }
}
