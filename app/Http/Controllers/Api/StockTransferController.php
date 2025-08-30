<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockTransferRequest;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'inventoryItem']);

        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Filter by warehouse
        if ($request->has('warehouse_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('from_warehouse_id', $request->warehouse_id)
                  ->orWhere('to_warehouse_id', $request->warehouse_id);
            });
        }

        // Filter by inventory item
        if ($request->has('inventory_item_id')) {
            $query->where('inventory_item_id', $request->inventory_item_id);
        }

        $transfers = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transfers,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStockTransferRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['status'] = StockTransfer::STATUS_PENDING;

        $transfer = StockTransfer::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Stock transfer created successfully',
            'data' => $transfer->load(['fromWarehouse', 'toWarehouse', 'inventoryItem']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $transfer = StockTransfer::with(['fromWarehouse', 'toWarehouse', 'inventoryItem', 'createdBy'])
                                ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $transfer,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        // Only allow updating notes and status for pending transfers
        if ($transfer->status !== StockTransfer::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update completed or cancelled transfers',
            ], 400);
        }

        $transfer->update($request->only(['notes', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Stock transfer updated successfully',
            'data' => $transfer,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        // Only allow deletion of pending transfers
        if ($transfer->status !== StockTransfer::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete completed or cancelled transfers',
            ], 400);
        }

        $transfer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock transfer deleted successfully',
        ]);
    }

    /**
     * Execute a stock transfer.
     */
    public function execute(string $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        if (!$transfer->canExecute()) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer cannot be executed. Check stock availability.',
            ], 400);
        }

        if ($transfer->execute()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock transfer executed successfully',
                'data' => $transfer->fresh()->load(['fromWarehouse', 'toWarehouse', 'inventoryItem']),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to execute stock transfer',
        ], 500);
    }

    /**
     * Cancel a stock transfer.
     */
    public function cancel(string $id): JsonResponse
    {
        $transfer = StockTransfer::findOrFail($id);

        if ($transfer->cancel()) {
            return response()->json([
                'success' => true,
                'message' => 'Stock transfer cancelled successfully',
                'data' => $transfer,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to cancel stock transfer',
        ], 500);
    }

    /**
     * Get transfer statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_transfers' => StockTransfer::count(),
            'pending_transfers' => StockTransfer::pending()->count(),
            'completed_transfers' => StockTransfer::completed()->count(),
            'cancelled_transfers' => StockTransfer::byStatus(StockTransfer::STATUS_CANCELLED)->count(),
            'total_items_transferred' => StockTransfer::completed()->sum('quantity'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
