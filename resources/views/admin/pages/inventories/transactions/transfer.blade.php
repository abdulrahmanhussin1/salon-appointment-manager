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
    <form id="invoice-form" method="POST" action="{{ route('inventory_transactions.transfer') }}">
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
            <option value="">Select One Reason</option>
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
                                                <option value="{{ $item->id }}"  data-price="{{ $item->supplierPrices->first()->supplier_price ?? 0 }}"
                                                    {{ $item->id == $product->product_id ? 'selected' : '' }}>
                                                    {{ $item->name }}
                                                </option>

                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="number"  min="1"  name="products[{{ $index }}][quantity]" class="form-control quantity" value="{{ $product->quantity }}" required></td>
                                    <td><input type="number"  min="1" name="products[{{ $index }}][unit_price]" class="form-control unit-price" value="{{ $product->unit_price }}" required></td>
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
        <input type="number" id="total_before_discount" name="total_before_discount" class="form-control" readonly>
    </div>
    <div class="col-md-4">
        <label for="discount" class="form-label">Discount</label>
        <input type="number" id="discount" name="discount" class="form-control" value="{{ old('discount', $transaction->discount ?? '') }}">
    </div>
    <div class="col-md-4">
        <label for="delivery_expense" class="form-label">Delivery Expense</label>
        <input type="number" id="delivery_expense" name="delivery_expense" class="form-control" value="{{ old('delivery_expense', $transaction->delivery_expense ?? '') }}">
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <label for="other_expenses" class="form-label">Other Expenses</label>
        <input type="number" id="other_expenses" name="other_expenses" class="form-control" value="{{ old('other_expenses', $transaction->other_expenses ?? '') }}">
    </div>
    <div class="col-md-4">
        <label for="added_value_tax" class="form-label">Added Value Tax (%)</label>
        <input type="number" id="added_value_tax" value="14" name="added_value_tax" class="form-control" value="{{ old('added_value_tax', $transaction->added_value_tax ?? '') }}">
    </div>
    <div class="col-md-4">
        <label for="commercial_tax" class="form-label">Commercial Tax (%)</label>
        <input type="number" id="commercial_tax" name="commercial_tax" class="form-control" value="{{ old('commercial_tax', $transaction->commercial_tax ?? '') }}">
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
    calculateTotal()
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
                <td><input type="number" min="1" name="products[${rowCounter}][quantity]" class="form-control quantity" required></td>
                <td><input type="number" min="1" name="products[${rowCounter}][unit_price]" class="form-control unit-price" readonly required></td>
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

    // Populate Unit Price Based on Selected Product
    $("#products-table").on("change", ".product-selector", function () {
        const row = $(this).closest("tr");
        const productId = $(this).val();

        // Get the product's price from the server-side data
        const productPrice = getProductPrice(productId);
        row.find(".unit-price").val(productPrice).attr("readonly", true);

        // Recalculate the total for this row
        row.find(".quantity").trigger("input");
    });

    // Calculate Row Totals and Update Form
    $("#products-table").on("input", ".quantity", function () {
        const row = $(this).closest("tr");
        const quantity = parseFloat(row.find(".quantity").val()) || 0;
        const unitPrice = parseFloat(row.find(".unit-price").val()) || 0;
        const total = quantity * unitPrice;
        row.find(".item-total").val(total.toFixed(2));
        calculateTotal();
    });
$("#discount, #delivery_expense, #other_expenses, #added_value_tax, #commercial_tax").on("input", function () {
    console.log("Input change detected"); // Debugging line
    calculateTotal();
});

    // Calculate Total and Discount
function calculateTotal() {
    let total = 0;
    $(".item-total").each(function () {
        total += parseFloat($(this).val()) || 0;
    });
    $("#total_before_discount").val(total.toFixed(2));

    const discount = parseFloat($("#discount").val()) || 0;
    const deliveryExpense = parseFloat($("#delivery_expense").val()) || 0;
    const otherExpenses = parseFloat($("#other_expenses").val()) || 0;
    const addedValueTax = parseFloat(($("#added_value_tax").val() * (total - discount)) / 100  ) || 0;
    const commercialTax = parseFloat(($("#commercial_tax").val() * (total - discount)) / 100  ) || 0;



// Calculate net total
const netTotal = (total - discount) + (deliveryExpense + otherExpenses + addedValueTax + commercialTax);
    $("#net_total").val(netTotal.toFixed(2));
}


    // Helper to Populate Products Options Dynamically
    function getProductsOptions() {
        let options = '';
        @foreach($products as $product)
            options += `<option value="{{ $product->id }}" data-price="{{ $product->supplierPrices->first()->supplier_price ?? 0 }}">{{ $product->name }}</option>`;
        @endforeach
        return options;
    }

    // Helper to Get Product Price
    function getProductPrice(productId) {
        let price = 0;
        $("#products-table select.product-selector option").each(function () {
            if ($(this).val() == productId) {
                price = $(this).data("price");
            }
        });
        return price;
    }

});


</script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sourceInventory = document.getElementById('source_inventory');
        const destinationInventory = document.getElementById('destination_inventory');

        // Event listener for the source inventory selection
        sourceInventory.addEventListener('change', function() {
            const selectedValue = this.value;

            // Iterate over destination inventory options and remove the selected value from it
            for (let option of destinationInventory.options) {
                if (option.value === selectedValue) {
                    option.disabled = true; // Disable the matching option
                } else {
                    option.disabled = false; // Enable other options
                }
            }
        });

        // Event listener for the destination inventory selection
        destinationInventory.addEventListener('change', function() {
            const selectedValue = this.value;

            // Iterate over source inventory options and remove the selected value from it
            for (let option of sourceInventory.options) {
                if (option.value === selectedValue) {
                    option.disabled = true; // Disable the matching option
                } else {
                    option.disabled = false; // Enable other options
                }
            }
        });
    });
</script>

@endsection
