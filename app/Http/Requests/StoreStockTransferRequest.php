<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Warehouse;
use App\Models\InventoryItem;
use App\Models\Stock;

class StoreStockTransferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Will be controlled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from_warehouse_id' => [
                'required',
                'exists:warehouses,id',
                Rule::notIn([$this->input('to_warehouse_id')]),
            ],
            'to_warehouse_id' => [
                'required',
                'exists:warehouses,id',
                Rule::notIn([$this->input('from_warehouse_id')]),
            ],
            'inventory_item_id' => 'required|exists:inventory_items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateStockAvailability($validator);
        });
    }

    /**
     * Validate that the source warehouse has enough stock.
     */
    protected function validateStockAvailability($validator)
    {
        $fromWarehouseId = $this->input('from_warehouse_id');
        $inventoryItemId = $this->input('inventory_item_id');
        $quantity = $this->input('quantity');

        $stock = Stock::where('warehouse_id', $fromWarehouseId)
                     ->where('inventory_item_id', $inventoryItemId)
                     ->first();

        if (!$stock) {
            $validator->errors()->add('inventory_item_id', 'The selected item is not available in the source warehouse.');
            return;
        }

        if ($stock->available_quantity < $quantity) {
            $validator->errors()->add('quantity', 'Insufficient stock available. Available: ' . $stock->available_quantity);
        }
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'from_warehouse_id.required' => 'Source warehouse is required.',
            'from_warehouse_id.exists' => 'Selected source warehouse does not exist.',
            'from_warehouse_id.not_in' => 'Source and destination warehouses must be different.',
            'to_warehouse_id.required' => 'Destination warehouse is required.',
            'to_warehouse_id.exists' => 'Selected destination warehouse does not exist.',
            'to_warehouse_id.not_in' => 'Source and destination warehouses must be different.',
            'inventory_item_id.required' => 'Inventory item is required.',
            'inventory_item_id.exists' => 'Selected inventory item does not exist.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 1.',
        ];
    }
}
