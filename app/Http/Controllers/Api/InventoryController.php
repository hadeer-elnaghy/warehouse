<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Models\InventoryItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class InventoryController extends Controller
{
    /**
     * Display a listing of the resource with search and filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = InventoryItem::active();

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Price range filter
        if ($request->has('min_price') || $request->has('max_price')) {
            $query->priceRange(
                $request->min_price,
                $request->max_price
            );
        }

        // Category filter
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Brand filter
        if ($request->has('brand')) {
            $query->byBrand($request->brand);
        }

        // Warehouse filter
        if ($request->has('warehouse_id')) {
            $query->whereHas('stocks', function ($q) use ($request) {
                $q->where('warehouse_id', $request->warehouse_id);
            });
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $inventory = $query->with(['stocks.warehouse'])
                          ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inventory,
            'filters' => [
                'search' => $request->search,
                'min_price' => $request->min_price,
                'max_price' => $request->max_price,
                'category' => $request->category,
                'brand' => $request->brand,
                'warehouse_id' => $request->warehouse_id,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventoryItemRequest $request): JsonResponse
    {
        $inventoryItem = InventoryItem::create($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Inventory item created successfully',
            'data' => $inventoryItem,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::with(['stocks.warehouse'])->find($id);
        
        if (!$inventoryItem) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => $inventoryItem,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreInventoryItemRequest $request, string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::find($id);
        
        if (!$inventoryItem) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
            ], 404);
        }
        
        $inventoryItem->update($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'Inventory item updated successfully',
            'data' => $inventoryItem,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $inventoryItem = InventoryItem::find($id);
        
        if (!$inventoryItem) {
            return response()->json([
                'success' => false,
                'message' => 'Inventory item not found',
            ], 404);
        }
        
        $inventoryItem->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Inventory item deleted successfully',
        ]);
    }

    /**
     * Get low stock items.
     */
    public function lowStock(): JsonResponse
    {
        $lowStockItems = InventoryItem::active()
            ->with(['stocks.warehouse'])
            ->whereHas('stocks', function ($query) {
                $query->whereRaw('stocks.available_quantity <= inventory_items.min_stock_level');
            })
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'min_stock_level' => $item->min_stock_level,
                    'total_available' => $item->total_available_quantity,
                    'warehouses' => $item->stocks->map(function ($stock) {
                        return [
                            'warehouse' => $stock->warehouse->name,
                            'available_quantity' => $stock->available_quantity,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $lowStockItems,
            'count' => $lowStockItems->count(),
        ]);
    }
}
