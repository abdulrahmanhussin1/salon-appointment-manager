@extends('admin.layouts.app')
@section('title')
    {{ _('Sales Invoice') }}
@endsection
@section('css')
    <style>
        .table {
            table-layout: fixed;
            /* Enforces fixed table layout */
            width: 100%;
            /* Ensures the table takes the full width */
        }

        .table td {
            width: 33.33%;
            /* Each cell gets an equal share of the width */
            text-align: center;
            /* Centers the content */
            word-wrap: break-word;
            /* Wraps text if it overflows */
        }

        .table h5 {
            margin: 0;
            /* Removes default margin for headings */
        }

        .table p {
            margin: 0;
            /* Removes default margin for paragraphs */
        }

        th {
            font-size: 13px
        }
    </style>
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Sales Invoice">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('sales_invoices.index') }}">{{ __('Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($invoice) }}">
            {{ isset($invoice) ? __('Edit Invoice #') . $invoice->id : __('Create New Invoice') }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <div class="row">
        <div class="col-8">
            <div class=" me-2 mb-3">
                {{-- Customer Details --}}
                <div class="card mb-3">
                    <div class="ms-2 row">
                        <div class="card-title col-8">  Customer Details</div>
                        <div class="col-4 text-end mt-3 pe-4">
                            @if (App\Traits\AppHelper::perUSer('customers.create'))
                                <x-modal-button title="Customer" target="customerModal"><i
                                        class="bi bi-plus-lg me-2"></i></x-modal-button>
                            @endif
                        </div>
                    </div>
                    @include('admin.layouts.alerts')
                    <form action="{{ route('sales_invoices.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 ">
                                    <x-form-select name="branch_id" id="branch_id" label='Branch' required>
                                        @foreach ($branches as $branch)
                                            <option @if (old('branch_id') == $branch->id) selected="selected" @endif
                                                value="{{ $branch->id }}">
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </x-form-select>
                                </div>
                                <div class="col-6">
                                    <x-form-select name='status' id="status" label="status" required>
                                        <option @if (old('status') == 'active') selected @endif value="active">
                                            {{ __('Active') }}</option>
                                        <option @if (old('status') == 'inactive') selected @endif value="inactive">
                                            {{ __('Inactive') }}</option>
                                        <option @if (old('status') == 'draft') selected @endif value="draft">
                                            {{ __('Draft') }}</option>
                                    </x-form-select>
                                </div>
                                <div class="col-6 mb-3">
                                    <x-form-select name="customer_id" id="customer_id" label='Customers' required>
                                        <option value="">{{ __('Select one Customer') }}</option>
                                        @foreach ($customers as $customer)
                                            <option @if (isset($invoice) && ($invoice->customer_id == $customer->id || old('customer_id') == $customer->id)) selected="selected" @endif
                                                @if (!isset($invoice) && Auth::user()->employee?->customer_id == $customer->id) selected="selected" @endif
                                                value="{{ $customer->id }}">
                                                {{ $customer->name }} - {{ $customer->phone }}
                                            </option>
                                        @endforeach
                                    </x-form-select>
                                </div>



                                <div class="col-6 mb-3">
                                    <label for="invoice_date" class="form-label">Invoice Date:</label>
                                    <input type="date" id="invoice_date" name="invoice_date"
                                        class="form-control form-control-sm @error('invoice_date') is-invalid @enderror"
                                        value="{{ old('invoice_date', date('Y-m-d')) }}">
                                </div>

                                <hr>
                                <table class="table table-sm col-12 table-bordered">
                                    <tr>
                                        <td id="customer-since" class="text-center">
                                            <h5>Customer Since</h5>
                                            <p></p>
                                        </td>
                                        <td id="last-visit" class="text-center">
                                            <h5>Last Visit</h5>
                                            <p></p>
                                        </td>
                                        <td id="dob" class="text-center">
                                            <h5>Birthday</h5>
                                            <p></p>
                                        </td>
                                        <td id="is_vip" class="text-center">
                                            <h5>VIP</h5>
                                            <p></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                </div>
            </div>

            {{-- Invoice Items --}}
            <div class="col-12">
                <div class="card mb-3">
                    <div class="ms-2">
                        <div class="card-title">Invoice Items</div>
                    </div>
                    <div class="card-body">
                        <table id="invoice-items" class="table table-sm fs--1 table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 15%;">Type</th>
                                    <th style="width: 20%;">Item</th>
                                    <th style="width: 15%;">Code</th>
                                    <th style="width: 15%;">Provider</th>
                                    <th style="width: 10%;">Quantity</th>
                                    <th style="width: 10%;">Price</th>
                                    <th style="width: 10%;">Discount (%)</th>
                                    <th style="width: 10%;">Tax (%)</th>
                                    <th style="width: 15%;">Due</th>
                                    <th style="width: 7%;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Rows will be added dynamically -->
                            </tbody>
                        </table>

                        <button class="btn btn-sm btn-primary" type="button" id="add-item"><i class="bi bi-plus-lg"></i>
                            Item</button>
                    </div>
                </div>
            </div>

        </div>
        {{-- Payment Summary --}}
        <div class="col-4 ">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Payment Summary</div>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Services</th>
                            <td id="services-total" class="text-end">$0.00</td>
                        </tr>
                        <tr>
                            <th>Products</th>
                            <td id="products-total" class="text-end">$0.00</td>
                        </tr>
                        <tr>
                            <th>Discount</th>
                            <td id="discount-total" class="text-end">- $0.00</td>
                        </tr>
                        <tr>
                            <th>Tax</th>
                            <td id="tax-total" class="text-end">$0.00</td>
                        </tr>

                        <tr>
                            <th>Grand Total</th>
                            <td id="grand-total" class="text-end">$0.00</td>
                        </tr>
                        <tr>
                            <th>Deposit</th>
                            <td>
                                <input type="text" id="deposit-input" class="form-control form-control-sm text-end"
                                    readonly value="0.00" oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')">
                            </td>
                        </tr>
                        <tr>
                            <th>Net Total</th>
                            <td id="net-total" class="text-end">$0.00</td>
                        </tr>
                        <tr>
                            <th style="width: 10%; text-align: left;">Payment Method</th>
                            <td style="width: 80%;" class="text-end align-middle">
                                <div class="d-flex gap-1">
                                    <select id="payment_method_id" name="payment_method_id"
                                        class="form-select form-select-sm w-100">
                                        @foreach ($paymentMethods as $paymentMethod)
                                            <option value="{{ $paymentMethod->id }}">{{ $paymentMethod->name }}</option>
                                        @endforeach
                                    </select>
                                    <input type="text" id="payment-method-value"
                                        class="form-control form-control-sm text-end w-100" value="0.00"
                                        oninput="this.value = this.value.replace(/[^0-9.+-]/g, '')">
                                </div>
                            </td>
                        </tr>


                        <tr>
                            <th>Cash</th>
                            <td class="text-end">
                                <input type="text" id="cash-value" class="form-control form-control-sm text-end"
                                    value="0.00" oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')">

                            </td>
                        </tr>
                        {{-- <tr>
                            <th>Balance</th>
                            <td id="balance-value">$0.00</td>
                        </tr> --}}
                    </table>
                </div>
                <div class="card-footer text-end">
                    <button class="btn btn-sm btn-success" id="checkout">Checkout</button>
                    {{-- <button onclick="window.print()" class="btn btn-primary ms-3">Print Only</button> --}}

                </div>
                </form>
            </div>
        </div>
    </div>


    <x-modal id="customerModal" title="Create Customer">
        <form action="{{ route('customers.store') }}" method="POST" id="customerForm" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="row">
                    <div class="col-2">
                        <x-form-select name='salutation' id="salutation" label="salutation">
                            <option @if (old('salutation') == 'Mr') selected @endif value="Mr">
                                {{ __('Mr') }}</option>
                            <option @if (old('salutation') == 'Ms') selected @endif value="Ms">
                                {{ __('Ms') }}</option>
                            <option @if (old('salutation') == 'Mrs') selected @endif value="Mrs">
                                {{ __('Mrs') }}</option>
                            <option @if (old('salutation') == 'Dr') selected @endif value="Dr">
                                {{ __('Dr') }}</option>
                            <option @if (old('salutation') == 'Eng') selected @endif value="Eng">
                                {{ __('Eng') }}</option>

                        </x-form-select>
                    </div>
                    <div class="col-8">
                        <x-input type='text' value="{{ old('name') }}" label="Name" name='name'
                            placeholder='Customer Name' id="name" oninput="" required />
                    </div>

                    <div class="col-2">
                        <div class="form-check form-switch mt-4 mb-0 pt-2">
                            <!-- Hidden input to handle unchecked state -->
                            <input type="hidden" name="is_vip" value="0">
                            <!-- Checkbox input -->
                            <input class="form-check-input" type="checkbox" role="switch" value="1"
                                name="is_vip" id="flexSwitchCheckDefault" {{ old('is_vip') ? 'checked' : '' }}>
                            <label class="form-check-label" for="flexSwitchCheckDefault">{{ __('VIP') }}</label>
                        </div>
                    </div>
                </div>
                <x-input type="email" value="{{ old('email') }}" label="Email" name='email'
                    placeholder='Example@gmail.com' id="email" oninput="{{ null }}" />
                <x-input type="text" value="{{ old('phone') }}" label="phone" id="phone" name='phone'
                    placeholder="phone  Ex: 010xxxxxxxxx" oninput="this.value = this.value.replace(/[^0-9+]/g, '')"
                    required />


                <div class="col-12">
                    <label class="form-label" for="dob">{{ __('Date Of Birth') }}</label>
                    <input type="date" name="dob" class="form-control  @error('dob') is-invalid @enderror"
                        id="dob" value="{{ isset($employee) ? $employee->dob : old('dob') }}">
                    @error('dob')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <x-form-description value="{{ old('address') }}" label="address" name='address'
                    placeholder='Customer Address' />
                <x-form-description value="{{ old('notes') }}" label="notes" name='notes' placeholder='Notes' />
                <div class="row">
                    <div class="col-6">
                        <label class="form-label" for="customer-deposit">{{ __('Deposit') }}</label>
                        <input type="text" id="customer-deposit" name="deposit"
                            class="form-control form-control-sm text-end" value="{{ $customer->deposit ?? 0 }}"
                            oninput="this.value = this.value.replace(/[^0-9+-/]/g, '')">
                    </div>
                    <div class="col-6">
                        <x-form-select name='gender' id="gender" label="gender" required>
                            <option @if (old('gender') == 'male') selected @endif value="male">
                                {{ __('Male') }}</option>
                            <option @if (old('gender') == 'female') selected @endif value="female">
                                {{ __('Female') }}</option>
                        </x-form-select>
                    </div>
                    <div class="col-12">
                        <x-form-select name='added_from' id="added_from" label="added from">
                            <option @if (old('added_from') == 'direct') selected @endif value="direct">
                                {{ __('Direct') }}</option>
                            <option @if (old('added_from') == 'online') selected @endif value="online">
                                {{ __('Online') }}</option>
                            <option @if (old('added_from') == 'advertisement') selected @endif value="advertisement">
                                {{ __('Advertisement') }}</option>
                            <option @if (old('added_from') == 'referral') selected @endif value="referral">
                                {{ __('Referral') }}</option>
                            <option @if (old('added_from') == 'walk_in') selected @endif value="walk_in">
                                {{ __('walk_in') }}</option>

                        </x-form-select>
                    </div>
                </div>

            </div>
            <x-modal-footer />
        </form>
    </x-modal>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            // Store products, services, and employees data
            const products = @json($products);
            const services = @json($services);
            const employees = @json($employees);

            // Add a new item row on click
            $("#add-item").on("click", addItemRow);

            // Handle item deletion
            $("#invoice-items").on("click", ".remove-item", function() {
                $(this).closest("tr").remove();
                updateInvoice();
            });

            // Delegate events for dynamically created elements
            $("#invoice-items")
                .on("change", ".item-type", handleTypeChange)
                .on("change", ".item-selector", handleItemChange)
                .on("input", ".item-qty, .item-price, .item-discount, .item-tax", updateInvoice);

            // Handle changes in deposit, payment method, and cash values
            $("#deposit-input, #payment-method-value, #cash-value").on("input", updateInvoice);

            // Handle customer selection change
            $("#customer_id").on("change", function() {
                const selectedCustomer = $(this).find(":selected");
                const depositValue = selectedCustomer.data("deposit") || 0;
                $("#deposit-input").val(depositValue.toFixed(2));
                updateInvoice();
            });

            // Rest of your existing functions (addItemRow, handleTypeChange, loadItemsForType, handleItemChange) remain the same...

            // Send data to the server using AJAX on checkout button click
            $("#checkout").on("click", function(e) {
                e.preventDefault();

                // Collect data for submission
                const customerId = $("#customer_id").val();
                const deposit = parseFloat($("#deposit-input").val());
                const paymentMethodId = $("#payment_method_id").val();
                const paymentMethodValue = parseFloat($("#payment-method-value").val()) || 0;
                const cashPayment = parseFloat($("#cash-value").val());
                const invoiceDate = $("#invoice_date").val();
                const branchId = $("#branch_id").val();
                const status = $("#status").val();
                const items = [];

                $("#invoice-items tbody tr").each(function() {
                    const $row = $(this);
                    items.push({
                        type: $row.find(".item-type").val(),
                        item_id: $row.find(".item-selector").val(),
                        code: $row.find(".item-code").val(),
                        provider_id: $row.find(".provider-selector").val(),
                        quantity: parseFloat($row.find(".item-qty").val()) || 0,
                        price: parseFloat($row.find(".item-price").val()) || 0,
                        discount: parseFloat($row.find(".item-discount").val()) || 0,
                        tax: parseFloat($row.find(".item-tax").val()) || 0,
                    });
                });

                // Prepare the data payload
                const data = {
                    customer_id: customerId,
                    deposit: deposit,
                    payment_method_id: paymentMethodId,
                    payment_method_value: paymentMethodValue,
                    branch_id: branchId,
                    invoice_date: invoiceDate,
                    items: items,
                    status: status,
                    cash_payment: cashPayment,
                };

                // Send the data using AJAX
                $.ajax({
                    url: "{{ route('sales_invoices.store') }}",
                    type: "POST",
                    headers: {
                        "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                    },
                    contentType: "application/json",
                    data: JSON.stringify(data),
                    success: function(response) {

                        Swal.fire({
                            icon: 'success',
                            title: 'Created Successfully',
                            text: 'Your invoice has been created.',
                            showCancelButton: true,
                            confirmButtonText: 'Print Invoice', // Label for the primary action button
                            cancelButtonText: 'Create Another Invoice', // Label for the secondary action button
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // console.log('invoice_id=' + response.invoice_id , response );
                                // If the user clicks "Print Invoice"

                                // window.location.href =

                                window.open(`invoice/${response.invoice_id}`,
                                    '_blank'); // Replace with your print URL
                            } else if (result.dismiss === Swal.DismissReason.cancel) {
                                // If the user clicks "Add Another Invoice"
                                window.location.href =
                                    "{{ route('sales_invoices.create') }}";
                            }else{
                                window.location.href =
                                    "{{ route('sales_invoices.create') }}";
                            }
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error:", error);

                        // Check if there are validation errors
                        if (xhr.status ===
                            422
                        ) { // 422 is the HTTP status for unprocessable entity, often used for validation errors
                            const validationErrors = xhr.responseJSON.errors;
                            let errorMessages = '';

                            // Loop through the validation errors and construct a message
                            for (const field in validationErrors) {
                                if (validationErrors.hasOwnProperty(field)) {
                                    errorMessages +=
                                        `${validationErrors[field].join(', ')}\n`; // Concatenate error messages
                                }
                            }

                            // Show SweetAlert with the validation errors
                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Errors',
                                text: errorMessages ||
                                    'Please correct the errors and try again.',
                            });
                        } else {
                            // For other types of errors (e.g., server issues)
                            Swal.fire({
                                icon: 'error',
                                title: 'Something went wrong!',
                                text: error ||
                                    'An unexpected error occurred. Please try again.',
                            });
                        }
                    }
                });
            });

            function addItemRow() {
                const newRow = `
            <tr>
                <td>
                    <select name="type" class="form-select form-select-sm item-type">
                        <option value="service" selected>Service</option>
                        <option value="product">Product</option>
                    </select>
                </td>
                <td>
                    <select name="item" id="item" class="item-selector" required>
                        <option value="" selected disabled>Item</option>
                    </select>
                </td>
                <td><input type="text" name="code" class="form-control form-control-sm item-code" placeholder="Code" readonly></td>
                <td><select name="provider" class="form-select form-select-sm provider-selector" required><option value="" selected disabled>Provider</option></select></td>
                <td><input type="number" name="quantity" class="form-control form-control-sm item-qty" value="1" min="1"></td>
                <td><input type="number" name="price" class="form-control form-control-sm item-price" value="0" min="0" step="0.01" readonly></td>
                <td><input type="number" name="discount" class="form-control form-control-sm item-discount" value="0" min="0" max="100" step="0.01"></td>
                <td><input type="number" name="tax" class="form-control form-control-sm item-tax" value="0" min="0" max="100" step="0.01"></td>
                <td class="item-due">$0.00</td>
                <td><button class="btn btn-sm btn-danger remove-item"><i class="bi bi-trash"></i></button></td>
            </tr>`;

                $("#invoice-items tbody").append(newRow);

                // Load services into the item selector by default
                loadItemsForType($("#invoice-items tbody tr:last-child .item-type"));
            }

            function handleTypeChange() {
                const $row = $(this).closest("tr");
                loadItemsForType($(this));
            }

            function loadItemsForType($typeSelector) {
                const type = $typeSelector.val();
                const $itemSelector = $typeSelector.closest("tr").find(".item-selector");

                $itemSelector.prop("disabled", false).html('<option value="" selected disabled>Item</option>');

                if (type === "product") {
                    products.forEach((product) => {
                        $itemSelector.append(new Option(product.name, product.id));
                    });
                } else if (type === "service") {
                    services.forEach((service) => {
                        $itemSelector.append(new Option(service.name, service.id));
                    });
                }
            }

            function handleItemChange() {
                const $row = $(this).closest("tr");
                const type = $row.find(".item-type").val();
                const itemId = $(this).val();
                const $itemCode = $row.find(".item-code");
                const $itemPrice = $row.find(".item-price");
                const $providerSelector = $row.find(".provider-selector");

                const itemList = type === "product" ? products : services;

                const selectedItem = itemList.find((item) => item.id == itemId);

                if (selectedItem) {
                    console.log(selectedItem);

                    // Set the item code
                    $itemCode.val(selectedItem.code || selectedItem.id);

                    // Validate and set the price based on the price_can_change flag
                    $itemPrice.val(selectedItem.price || 0);

                    if (selectedItem.price_can_change) {
                        $itemPrice.prop("readonly",
                            false); // Make the price input editable if price_can_change is true
                    } else {
                        $itemPrice.prop("readonly",
                            true); // Keep the price input readonly if price_can_change is false
                    }
                }

                // Populate providers for services if applicable
                $.ajax({
                    url: "{{ route('sales_invoices.getRelatedEmployees') }}",
                    method: "GET",
                    data: {
                        item_type: type,
                        item_id: itemId
                    },
                    success: function(data) {
                        $providerSelector.html('<option value="" selected disabled>Provider</option>');
                        data.forEach((employee) => {
                            $providerSelector.append(new Option(employee.name, employee.id));
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error(`Error fetching providers: ${status} - ${error}`);
                    },
                });

                updateInvoice();
            }

            function updateInvoice() {
                const $rows = $("#invoice-items tbody tr");
                let servicesTotal = 0,
                    productsTotal = 0,
                    discountTotal = 0,
                    taxTotal = 0;

                $rows.each(function() {
                    const $row = $(this);
                    const type = $row.find(".item-type").val();
                    const qty = parseFloat($row.find(".item-qty").val()) || 0;
                    let price = parseFloat($row.find(".item-price").val()) || 0;
                    const discountPercent = parseFloat($row.find(".item-discount").val()) || 0;
                    const taxPercent = parseFloat($row.find(".item-tax").val()) || 0;

                    const selectedItemId = $row.find(".item-selector").val();
                    const itemList = type === "product" ? products : services;
                    const selectedItem = itemList.find((item) => item.id == selectedItemId);

                    if (selectedItem) {
                        const originalPrice = parseFloat(selectedItem.price) || 0;

                        // Calculate the total for this row
                        const grossTotal = qty * price;
                        const discount = (grossTotal * discountPercent) / 100;
                        const tax = ((grossTotal - discount) * taxPercent) / 100;
                        const due = grossTotal - discount + tax;

                        $row.find(".item-due").text(`$${due.toFixed(2)}`);

                        // Calculate totals
                        if (type === "service") servicesTotal += grossTotal;
                        if (type === "product") productsTotal += grossTotal;
                        discountTotal += discount;
                        taxTotal += tax;
                    }
                });

                const deposit = parseFloat($("#deposit-input").val()) || 0;
                const paymentMethodValue = parseFloat($("#payment-method-value").val()) || 0;
                const cashValue = parseFloat($("#cash-value").val()) || 0;

                const grandTotal = servicesTotal + productsTotal - discountTotal + taxTotal;
                const netTotal = grandTotal - deposit;
                const balance = netTotal - paymentMethodValue - cashValue;

                // Update all totals
                $("#services-total").text(`$${servicesTotal.toFixed(2)}`);
                $("#products-total").text(`$${productsTotal.toFixed(2)}`);
                $("#discount-total").text(`- $${discountTotal.toFixed(2)}`);
                $("#tax-total").text(`$${taxTotal.toFixed(2)}`);
                $("#grand-total").text(`$${grandTotal.toFixed(2)}`);
                $("#net-total").text(`$${netTotal.toFixed(2)}`);

                // Add balance row if it doesn't exist
                if ($("#balance-row").length === 0) {
                    const balanceRow = `
                <tr id="balance-row">
                    <th>Balance</th>
                    <td id="balance-total" class="text-end">$${balance.toFixed(2)}</td>
                </tr>`;
                    $("#cash-value").closest("tr").after(balanceRow);
                } else {
                    $("#balance-total").text(`$${balance.toFixed(2)}`);
                }
            }

            // Rest of your existing code (checkout button handler) remains the same...
        });
    </script>



    <script>
        $(document).ready(function() {
            $("#salutation,#gender,#added_from").select2({
                dropdownParent: $("#customerForm")
            });



            $('#customerForm').submit(function(e) {
                e.preventDefault();

                var form = $(this);
                var url = form.attr('action');
                var method = form.attr('method');

                $.ajax({
                    url: url,
                    type: method,
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            // Handle successful creation or update
                            if (method === 'POST') {
                                // Create a new option for the newly created customer
                                var newCustomerOption = $('<option>')
                                    .val(response.customer_id)
                                    .text(response.customer_name + ' - ' + response
                                        .customer_phone);
                                $('#customer_id').append(newCustomerOption);

                                // Select the newly created customer
                                $('#customer_id').val(response.customer_id);
                            } else {
                                // Update the existing customer option
                                $('#customer_id option[value="' + response.customer_id + '"]')
                                    .text(response.customer_name + ' - ' + response
                                        .customer_phone);
                            }

                            // Close the modal
                            $('#customerModal').modal('hide');

                            // Display a success message or perform other actions
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'Customer saved successfully!'
                            });
                            $('#customerForm')[0].reset();

                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error saving customer: ' + response.message
                            });
                        }
                    },
                    error: function() {
                        // Handle AJAX request errors
                        alert('An error occurred while saving the customer.');
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            // Assuming all customer data is available in a variable
            const customers = @json($customers);

            // Event listener for customer selection
            $('#customer_id').change(function() {
                const customerId = $(this).val();

                // Find the selected customer from the customer list
                const selectedCustomer = customers.find(customer => customer.id == customerId);

                if (selectedCustomer) {
                    // Update the customer information on the page
                    $('#customer-since p').text(selectedCustomer.created_at ? new Date(selectedCustomer
                        .created_at).toLocaleDateString() : 'N/A');
                    $('#last-visit p').text(selectedCustomer.last_service ? new Date(selectedCustomer
                        .last_service).toLocaleDateString() : 'N/A');
                    $('#dob p').text(selectedCustomer.dob ? new Date(selectedCustomer.dob)
                        .toLocaleDateString() : 'N/A');
                    $('#is_vip p').html(
                        selectedCustomer.is_vip ?
                        '<i class="bi bi-star-fill" style="color:#D38E29;font-size: x-large;"></i> Yes' :
                        '<i class="bi bi-star-fill" style="color:#D9DCE1;font-size: x-large;"></i> No'
                    );
                    $('#deposit-input').val(parseFloat(selectedCustomer.deposit).toFixed(2));
                } else {
                    // Clear the input field if no customer is selected
                    $('#deposit-input').val('0.00');
                }
            });
        });
    </script>
@endsection
