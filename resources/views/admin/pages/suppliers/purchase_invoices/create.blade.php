@extends('admin.layouts.app')
@section('title')
    {{ __('New Purchase Invoice ') }}
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
    <x-breadcrumb pageName="Purchase Invoice">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('purchase_invoices.index') }}">{{ __('Purchase Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($product) }}">
            {{ __('Create New Invoice') }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}
    <div class="card">
        <div class="card-header text-dark">
            <h4 class="mb-0">Create Purchase Invoice</h4>
        </div>
        <div class="card-body">
            <form id="purchase-invoice-form">
                @csrf

                <!-- Supplier Selection -->
                <div class="row mb-3">
                    <div class="col-4 mb-3">
                        <label for="branch_id" class="form-label">Branch</label>
                        <select class="form-select select2" id="branch_id" name="branch_id" required>
                            <option value="">Select Branch</option>
                            @foreach ($branches as $branch)
                                <option @if (!empty(Auth::user()->employee) && Auth::user()->employee->branch_id == $branch->id) selected @endif value="{{ $branch->id }}">
                                    {{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-4 mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select class="form-select select2" id="supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select select2" id="status" name="status" required>
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                        </select>
                    </div>

                    <div class="col-6">
                        <label for="invoice_date" class="form-label">Invoice Date</label>
                        <input type="date" class="form-control form-control-sm" id="invoice_date" name="invoice_date"
                            required>
                    </div>
                    <div class="col-6 ">
                        <label for="total_amount" class="form-label">Total Amount</label>
                        <input type="text" class="form-control bg-light form-control-sm" id="total_amount"
                            name="total_amount" placeholder="0.00" readonly
                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                    </div>
                    <!-- Discount and Notes for Entire Invoice -->
                    <div class="col-6 mt-3">
                        <label for="invoice_discount" class="form-label">Invoice Discount (Amount)</label>
                        <input type="text" name="invoice_discount" id="invoice_discount" 
                            class="form-control bg-light form-control-sm" placeholder="0.00"
                            oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                    </div>

                    <div class="col-6 mt-3">
                        <label for="net_amount" class="form-label">Net Amount (After Discount)</label>
                        <input type="text" class="form-control bg-light form-control-sm" id="net_amount"
                            name="net_amount" placeholder="0.00" readonly>
                    </div>

                    <div class="form-group mt-3">
                        <label for="invoice_notes">Notes</label>
                        <textarea name="invoice_notes" id="invoice_notes" class="form-control"></textarea>
                    </div>
                </div>
                <hr>
                <!-- Products Table -->
                <div class="card-title">
                    <h5>Invoice Details</h5>
                </div>
                <table class="table table-bordered table-striped" id="details-table">
                    <thead class="table-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>

                            <th>Supplier Price</th>
                            <th>Discount (%)</th>

                            <th>Subtotal</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Rows will be added dynamically -->
                    </tbody>
                </table>
                <button type="button" id="addRow" class="btn btn-success btn-sm">
                    <i class="bi bi-plus-circle me-2"></i> Product
                </button>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            let rowCounter = 0;
            const products = @json($products); // List of products passed from the controller
            function initSelect2() {
                $('.select2').select2({
                    width: '100%' // Ensure Select2 takes up the full width
                });
            }

            initSelect2();
            // Add a new row to the invoice details table
            $('#addRow').click(function() {
                rowCounter++;
                const productOptions = products.map(product => {
                    return `<option value="${product.id}">${product.name}</option>`;
                }).join('');

                const row = `
                <tr data-row-id="${rowCounter}">
                    <td>
                        <select name="details[${rowCounter}][product_id]" class="form-control select2 bg-white"  required>
                            <option value="">Select Product</option>
                            ${productOptions} <!-- Dynamically populated product options -->
                        </select>
                    </td>

                    <td>
                        <input type="text" name="details[${rowCounter}][quantity]" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" placeholder="0" class="form-control quantity" required>
                    </td>

                    <td>
                        <input type="text" name="details[${rowCounter}][supplier_price]" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" placeholder="0.00" class="form-control price" required>
                    </td>

                    <td>
                        <input type="text" name="details[${rowCounter}][discount]" value="0" oninput="this.value = this.value.replace(/[^0-9.]/g, '')" placeholder="0%"  class="form-control discount">
                    </td>
                    <td>
                        <input type="number" name="details[${rowCounter}][subtotal]" class="form-control subtotal" placeholder="0.00"  readonly>
                    </td>

                    <td>
                        <textarea name="details[${rowCounter}][notes]" class="form-control"></textarea>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger removeRow"><i class="bi-trash"></i></button>
                    </td>
                </tr>
            `;
                $('#details-table tbody').append(row);
                initSelect2();
            });

            // Calculate subtotal when quantity or price changes
            $(document).on('input', '.price, .quantity, .discount', function() {
                const row = $(this).closest('tr');
                const price = parseFloat(row.find('.price').val()) || 0;
                const quantity = parseFloat(row.find('.quantity').val()) || 0;
                const discount = parseFloat(row.find('.discount').val()) || 0;

                const subtotal = price * quantity;
                const discountedSubtotal = subtotal - (subtotal * (discount / 100));

                row.find('.subtotal').val(discountedSubtotal.toFixed(2));
                updateTotalAmount();
            });

            // Remove row
            $(document).on('click', '.removeRow', function() {
                $(this).closest('tr').remove();
                updateTotalAmount();
            });

            // Update the total amount based on all rows and apply invoice-wide discount
            function updateTotalAmount() {
                let totalAmount = 0;
                $('#details-table tbody tr').each(function() {
                    const subtotal = parseFloat($(this).find('.subtotal').val()) || 0;
                    totalAmount += subtotal;
                });

                // Apply invoice-wide discount
                const invoiceDiscount = parseFloat($('#invoice_discount').val()) || 0;
                const netAmount = totalAmount - invoiceDiscount;

                $('#total_amount').val(totalAmount.toFixed(2));
                $('#net_amount').val(netAmount.toFixed(2));
            }

            // When invoice-wide discount changes
            $('#purchase-invoice-form').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serialize();
                $.ajax({
                    url: "{{ route('purchase_invoices.store') }}",
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: 'Invoice submitted successfully!',
                        }).then(() => {
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred. Please try again.',
                        });
                        console.error(xhr.responseText);
                    }
                });
            });
        });
    </script>
@endsection
