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
                        <div class="card-title col-8"> Customer Details</div>
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

            @include('admin.pages.Sales.invoices.includes.invoice_form')

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
    <script src="{{ asset('admin-assets/assets/js/sales_invoice.js') }}"></script>

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
                    $('#deposit-input').val(parseFloat(selectedCustomer.deposit ?? 0).toFixed(2) || 0);
                } else {
                    // Clear the input field if no customer is selected
                    $('#deposit-input').val('0.00');
                }
            });
        });
    </script>
@endsection
