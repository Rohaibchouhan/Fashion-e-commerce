<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Services\InventoryService;
use Exception;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(protected InventoryService $inventoryService) {}

    public function index(Request $request)
    {
        $query = Inventory::with(['product.images', 'variant'])->latest();

        if ($request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'low_stock_threshold');
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$term}%")->orWhere('sku', 'like', "%{$term}%"))
                  ->orWhereHas('variant', fn ($v) => $v->where('sku', 'like', "%{$term}%"));
        }

        $inventories = $query->paginate(20)->withQueryString();

        return view('admin.inventory.index', compact('inventories'));
    }

    public function adjust(Request $request, int $variantId)
    {
        $validated = $request->validate([
            'new_quantity' => 'required|integer|min:0',
            'reason' => 'required|string|max:500',
        ]);

        try {
            $this->inventoryService->adjustStock(
                $variantId,
                $validated['new_quantity'],
                $validated['reason'],
                auth()->id()
            );

            ActivityLog::log('inventory.adjusted', "Adjusted stock for variant ID #{$variantId} to {$validated['new_quantity']}. Reason: {$validated['reason']}");

            return back()->with('success', 'Stock count successfully adjusted and movement recorded.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function movements()
    {
        $movements = InventoryMovement::with(['inventory.product', 'variant', 'user'])
            ->latest()
            ->paginate(25);

        return view('admin.inventory.movements', compact('movements'));
    }
}

