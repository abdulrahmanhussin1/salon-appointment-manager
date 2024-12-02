@extends('admin.layouts.app')
@section('title')
    {{ __('Transfer In Page') }}
@endsection
@section('css')
    <style>
        /* Custom Styles for Forms */
        form label {
            font-weight: bold;
        }

        table thead th {
            text-align: center;
            vertical-align: middle;
        }

        table tbody td {
            vertical-align: middle;
        }

        .bg-light {
            background-color: #f8f9fa !important;
        }


        /* Add some spacing */
    </style>
@endsection
@section('content')
    <x-breadcrumb pageName="Transfer In">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('inventories.index') }}">{{ __('Inventories') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($product) }}">
            {{ __('Transfer In') }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}
<div class="container mt-4">
    <form id="invoice-form" method="POST" action="{{ route('inventory_transactions.transferIn') }}">
        @csrf
        <input type="hidden" name="transaction_id" id="transaction_id" value="{{ old('transaction_id', $transaction->id ?? '') }}">
<div class="row mb-3">
    <div class="col-md-6">
        <label for="invoice_date" class="form-label">Date</label>
        <input type="date" id="invoice_date" name="invoice_date" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label for="statement_type" class="form-label">Statement Type</label>
        <select id="statement_type" name="statement_type" class="form-select" required>
            <option value="purchases">Purchases</option>
            <option value="adjustment">Balance Adjustment</option>
            <option value="return">Return or Other Reason</option>
        </select>
    </div>
</div>

        <!-- Inventory Selectors -->
        <div class="row mb-3">
            <div class="col-md-6">
                <label for="source_inventory" class="form-label">Source Inventory</label>
                <select id="source_inventory" name="source_inventory" class="form-select" required>
                    <option value="" disabled selected>Select Source Inventory</option>
                    @foreach($inventories as $inventory)
                        <option value="{{ $inventory->id }}"
                            {{ old('source_inventory', $transaction->source_inventory ?? '') == $inventory->id ? 'selected' : '' }}>
                            {{ $inventory->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="destination_inventory" class="form-label">Destination Inventory</label>
                <select id="destination_inventory" name="destination_inventory" class="form-select" required>
                    <option value="" disabled selected>Select Destination Inventory</option>
                    @foreach($inventories as $inventory)
                        <option value="{{ $inventory->id }}"
                            {{ old('destination_inventory', $transaction->destination_inventory ?? '') == $inventory->id ? 'selected' : '' }}>
                            {{ $inventory->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Add Products Section -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Add Products</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered" id="products-table">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Pre-fill rows for updates -->
                        @if(isset($transaction) && $transaction->products)
                            @foreach($transaction->products as $index => $product)
                                <tr>
                                    <td>
                                        <select name="products[{{ $index }}][product_id]" class="form-select product-selector" required>
                                            <option value="" disabled>Select Product</option>
                                            @foreach($products as $item)
                                                <option value="{{ $item->id }}"
                                                    {{ $item->id == $product->product_id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number" name="products[{{ $index }}][quantity]" class="form-control quantity" value="{{ $product->quantity }}" required></td>
                                    <td><input type="number" name="products[{{ $index }}][unit_price]" class="form-control unit-price" value="{{ $product->unit_price }}" required></td>
                                    <td><input type="text" name="products[{{ $index }}][total]" class="form-control item-total" value="{{ $product->total }}" readonly></td>
                                    <td><textarea name="products[{{ $index }}][notes]" class="form-control">{{ $product->notes }}</textarea></td>
                                    <td><button type="button" class="btn btn-danger remove-row"><i class="bi-trash"></i></button></td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                <button type="button" id="add-product" class="btn btn-primary mt-3">
                    <i class="bi-plus-circle"></i> Add Product
                </button>
            </div>
        </div>

        <!-- Totals Section -->
<!-- Totals Section -->
<div class="row mt-4">
    <div class="col-md-4">
        <label for="total_before_discount" class="form-label">Total Before Discount</label>
        <input type="text" id="total_before_discount" name="total_before_discount" class="form-control" readonly>
    </div>
    <div class="col-md-4">
        <label for="discount" class="form-label">Discount</label>
        <input type="text" id="discount" name="discount" class="form-control" value="{{ old('discount', $transaction->discount ?? '') }}">
    </div>
    <div class="col-md-4">
        <label for="delivery_expense" class="form-label">Delivery Expense</label>
        <input type="text" id="delivery_expense" name="delivery_expense" class="form-control" value="{{ old('delivery_expense', $transaction->delivery_expense ?? '') }}">
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <label for="other_expenses" class="form-label">Other Expenses</label>
        <input type="text" id="other_expenses" name="other_expenses" class="form-control" value="{{ old('other_expenses', $transaction->other_expenses ?? '') }}">
    </div>
    <div class="col-md-4">
        <label for="added_value_tax" class="form-label">Added Value Tax</label>
        <input type="text" id="added_value_tax" name="added_value_tax" class="form-control" value="{{ old('added_value_tax', $transaction->added_value_tax ?? '') }}">
    </div>
    <div class="col-md-4">
        <label for="commercial_tax" class="form-label">Commercial Tax</label>
        <input type="text" id="commercial_tax" name="commercial_tax" class="form-control" value="{{ old('commercial_tax', $transaction->commercial_tax ?? '') }}">
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <label for="net_total" class="form-label">Net Total</label>
        <input type="text" id="net_total" name="net_total" class="form-control" readonly>
    </div>
</div>


        <!-- Actions Section -->
        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-success">
                <i class="bi-check-circle"></i> Save
            </button>
            {{-- <a href="{{ route('transaction.index') }}" class="btn btn-danger">
                <i class="bi-x-circle"></i> Cancel
            </a> --}}
        </div>
    </form>
</div>

    </div>
@endsection

@section('js')
<script>
$(document).ready(function () {
    let rowCounter = $("#products-table tbody tr").length;

    // Add Product Row
    $("#add-product").click(function () {
        const newRow = `
            <tr data-row-id="${rowCounter}">
                <td>
                    <select name="products[${rowCounter}][product_id]" class="form-select product-selector" required>
                        <option value="" disabled selected>Select Product</option>
                        ${getProductsOptions()}
                    </select>
                </td>
                <td><input type="number" name="products[${rowCounter}][quantity]" class="form-control quantity" required></td>
                <td><input type="number" name="products[${rowCounter}][unit_price]" class="form-control unit-price" required></td>
                <td><input type="text" name="products[${rowCounter}][total]" class="form-control item-total" readonly></td>
                <td><textarea name="products[${rowCounter}][notes]" class="form-control"></textarea></td>
                <td><button type="button" class="btn btn-danger remove-row"><i class="bi-trash"></i></button></td>
            </tr>
        `;
        $("#products-table tbody").append(newRow);
        rowCounter++;
    });

    // Remove Product Row
    $("#products-table").on("click", ".remove-row", function () {
        $(this).closest("tr").remove();
        calculateTotal();
    });

    // Calculate Row Totals and Update Form
    $("#products-table").on("input", ".quantity, .unit-price", function () {
        const row = $(this).closest("tr");
        const quantity = parseFloat(row.find(".quantity").val()) || 0;
        const unitPrice = parseFloat(row.find(".unit-price").val()) || 0;
        const total = quantity * unitPrice;
        row.find(".item-total").val(total.toFixed(2));
        calculateTotal();
    });

    // Calculate Total and Discount
// Update the calculateTotal function
function calculateTotal() {
    let total = 0;
    $(".item-total").each(function () {
        total += parseFloat($(this).val()) || 0;
    });
    $("#total_before_discount").val(total.toFixed(2));

    const discount = parseFloat($("#discount").val()) || 0;
    const deliveryExpense = parseFloat($("#delivery_expense").val()) || 0;
    const otherExpenses = parseFloat($("#other_expenses").val()) || 0;
    const addedValueTax = parseFloat($("#added_value_tax").val()) || 0;
    const commercialTax = parseFloat($("#commercial_tax").val()) || 0;

    // Calculate net total
    const netTotal = total - discount - deliveryExpense - otherExpenses - addedValueTax - commercialTax;
    $("#net_total").val(netTotal.toFixed(2));
}

// Bind input events for recalculations
$("#discount, #delivery_expense, #other_expenses, #added_value_tax, #commercial_tax").on("input", function () {
    calculateTotal();
});


    // Helper to Populate Products Options Dynamically
    function getProductsOptions() {
        let options = '';
        @foreach($products as $product)
            options += `<option value="{{ $product->id }}">{{ $product->name }}</option>`;
        @endforeach
        return options;
    }

    // Discount Input Handling
    $("#discount").on("input", function () {
        calculateTotal();
    });
});


</script>
@endsection
