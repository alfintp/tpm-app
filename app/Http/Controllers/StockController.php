<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockUsage;
use App\Models\ActivityLog;
use App\Models\Machine;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::query()
            ->select([
                'id', 'code', 'category', 'name', 'quantity', 'unit', 'limit_qty', 'is_active', 'created_at', 'updated_at',
            ]);

        if ($request->has('search') && $request->search) {
            $q = '%' . $request->search . '%';
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', $q)
                    ->orWhere('name', 'like', $q)
                    ->orWhere('category', 'like', $q);
            });
        }

        if ($request->has('category') && $request->category && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('low_stock') && filter_var($request->low_stock, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereColumn('quantity', '<=', 'limit_qty');
        }

        if ($request->has('inactive') && !filter_var($request->inactive, FILTER_VALIDATE_BOOLEAN)) {
            $query->where('is_active', true);
        }

        $sort = $request->get('sort', 'name');
        switch ($sort) {
            case 'name_desc':
                $query->orderByDesc('name');
                break;
            case 'quantity_asc':
                $query->orderBy('quantity');
                break;
            case 'quantity_desc':
                $query->orderByDesc('quantity');
                break;
            case 'code':
                $query->orderBy('code');
                break;
            default:
                $query->orderBy('name');
        }

        $stocks = $query->get()->map(function ($s) {
            $s->is_low_stock = $s->quantity <= $s->limit_qty;
            return $s;
        });

        return response()->json($stocks);
    }

    public function lowStock()
    {
        $stocks = Stock::whereColumn('quantity', '<=', 'limit_qty')
            ->where('is_active', true)
            ->orderBy('quantity')
            ->get();

        return response()->json($stocks);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $canAdd = $user->role === 'admin' || Role::where('name', $user->role)->value('can_add_data');
        if (!$canAdd) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:stocks,code',
            'category' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'limit_qty' => 'nullable|integer|min:0',
        ]);

        $validated['limit_qty'] = $validated['limit_qty'] ?? 1;
        $validated['unit'] = $validated['unit'] ?? 'pcs';

        $stock = Stock::create($validated);

        ActivityLog::log('Tambah Stok', "Menambahkan stok baru: {$stock->name} ({$stock->code}) - Qty: {$stock->quantity} {$stock->unit}");

        return response()->json($stock, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();
        $canAdd = $user->role === 'admin' || Role::where('name', $user->role)->value('can_add_data');
        if (!$canAdd) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $stock = Stock::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:stocks,code,' . $id,
            'category' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'quantity' => 'required|integer|min:0',
            'unit' => 'nullable|string|max:50',
            'limit_qty' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $stock->update($validated);
        $stock->is_low_stock = $stock->quantity <= $stock->limit_qty;

        ActivityLog::log('Edit Stok', "Memperbarui stok: {$stock->name} ({$stock->code})");

        return response()->json($stock);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $canDelete = $user->role === 'admin' || Role::where('name', $user->role)->value('can_delete_data');
        if (!$canDelete) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $stock = Stock::findOrFail($id);

        if ($stock->usages()->exists()) {
            $stock->update(['is_active' => false]);
            ActivityLog::log('Nonaktifkan Stok', "Menonaktifkan stok: {$stock->name} ({$stock->code}) karena masih memiliki riwayat pemakaian");
            return response()->json(['message' => 'Stok dinonaktifkan karena masih memiliki riwayat pemakaian.']);
        }

        ActivityLog::log('Hapus Stok', "Menghapus stok: {$stock->name} ({$stock->code})");
        $stock->delete();

        return response()->json(['message' => 'Stok berhasil dihapus.']);
    }

    public function import(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'stocks' => 'required|array',
            'stocks.*.code' => 'required|string|max:50',
            'stocks.*.category' => 'nullable|string|max:100',
            'stocks.*.name' => 'required|string|max:255',
            'stocks.*.quantity' => 'nullable|integer|min:0',
            'stocks.*.unit' => 'nullable|string|max:50',
            'stocks.*.limit_qty' => 'nullable|integer|min:0',
        ]);

        $created = 0;
        $updated = 0;

        try {
            DB::transaction(function () use ($request, &$created, &$updated) {
                foreach ($request->stocks as $item) {
                    $existing = Stock::where('code', $item['code'])->first();
                    if ($existing) {
                        $existing->update([
                            'category' => $item['category'] ?? $existing->category,
                            'name' => $item['name'],
                            'quantity' => $item['quantity'] ?? $existing->quantity,
                            'unit' => $item['unit'] ?? $existing->unit,
                            'limit_qty' => $item['limit_qty'] ?? $existing->limit_qty,
                        ]);
                        $updated++;
                    } else {
                        Stock::create([
                            'code' => $item['code'],
                            'category' => $item['category'] ?? null,
                            'name' => $item['name'],
                            'quantity' => $item['quantity'] ?? 0,
                            'unit' => $item['unit'] ?? 'pcs',
                            'limit_qty' => $item['limit_qty'] ?? 1,
                        ]);
                        $created++;
                    }
                }

                ActivityLog::log('Import Stok', "Mengimpor stok: {$created} baru, {$updated} diperbarui");
            }, 3);

            return response()->json([
                'message' => "Berhasil mengimpor {$created} stok baru, {$updated} diperbarui.",
                'created' => $created,
                'updated' => $updated,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengimpor stok: ' . $e->getMessage()], 500);
        }
    }

    public function bulkLimit(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|uuid|exists:stocks,id',
            'items.*.limit_qty' => 'required|integer|min:0',
        ]);

        $count = 0;

        try {
            DB::transaction(function () use ($request, &$count) {
                foreach ($request->items as $item) {
                    Stock::where('id', $item['id'])->update(['limit_qty' => $item['limit_qty']]);
                    $count++;
                }

                ActivityLog::log('Bulk Update Limit Stok', "Mengatur limit_qty untuk {$count} item stok");
            }, 3);

            return response()->json(['message' => "Berhasil mengatur limit qty untuk {$count} item stok."]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengatur limit qty: ' . $e->getMessage()], 500);
        }
    }

    public function restock(Request $request, $id)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $stock = Stock::findOrFail($id);

        $validated = $request->validate([
            'added_quantity' => 'required|integer|min:1',
        ]);

        $stock->increment('quantity', $validated['added_quantity']);
        $stock->is_low_stock = $stock->fresh()->quantity <= $stock->fresh()->limit_qty;

        ActivityLog::log('Restock Stok', "Restock {$stock->name} ({$stock->code}): +{$validated['added_quantity']} {$stock->unit}. Total: {$stock->fresh()->quantity}");

        return response()->json($stock->fresh());
    }

    public function usages(Request $request)
    {
        $query = StockUsage::with([
            'stock:id,code,name,unit',
            'machine:id,name,kota,location',
            'machineComponent:id,name',
            'technician:id,full_name',
        ]);

        if ($request->has('from_date') && $request->from_date) {
            $query->where('used_at', '>=', $request->from_date);
        }
        if ($request->has('to_date') && $request->to_date) {
            $query->where('used_at', '<=', $request->to_date);
        }
        if ($request->has('machine_id') && $request->machine_id) {
            $query->where('machine_id', $request->machine_id);
        }
        if ($request->has('stock_id') && $request->stock_id) {
            $query->where('stock_id', $request->stock_id);
        }

        $usages = $query->orderByDesc('used_at')->orderByDesc('created_at')->get();

        return response()->json($usages);
    }

    public function usageHistory($id)
    {
        $stock = Stock::findOrFail($id);

        $usages = StockUsage::with([
            'machine:id,name,kota,location',
            'machineComponent:id,name',
            'technician:id,full_name',
        ])
            ->where('stock_id', $id)
            ->orderByDesc('used_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'stock' => $stock,
            'usages' => $usages,
        ]);
    }
}
