<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendLowStockNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LowStockDetected $event): void
    {
        $stock = $event->stock;
        $item = $stock->inventoryItem;
        $warehouse = $stock->warehouse;

        // Log the low stock event
        Log::warning('Low stock detected', [
            'item' => $item->name,
            'sku' => $item->sku,
            'warehouse' => $warehouse->name,
            'current_quantity' => $stock->available_quantity,
            'min_stock_level' => $item->min_stock_level,
        ]);

        // In a real application, you would send an email notification here
        // For now, we'll just log it as mentioned in the requirements
        // Mail::to('admin@warehouse.com')->send(new LowStockNotification($stock));
    }
}
