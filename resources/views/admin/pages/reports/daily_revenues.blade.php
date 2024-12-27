@extends('admin.layouts.app')
@section('title')
    {{ __('Daily Cash Revenues Report') }}
@endsection
@section('css')
    <style>
        .alert {
            font-size: 0.7rem;
            font-weight: bold;
        }

        .table-primary,
        .table-success,
        .table-danger {
            font-weight: bold;
        }
    </style>
@endsection
@section('content')
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4>Daily Cash Revenue Analysis</h4>
                <div>
                    <button class="btn btn-sm btn-dark" id="printButton">Print</button>
                    <button type="submit" class="btn btn-sm btn-primary ">Refresh</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Filters Section -->
                <form id="dateFilterForm" method="GET" action="{{ route('report.daily_revenues') }}">
                    <div class="row mb-4">
                        <div class="col-3">
                            <label for="from_date">From Date</label>
                            <input type="date" id="from_date" name="from_date" class="form-control">
                        </div>
                        <div class="col-3">
                            <label for="to_date">To Date</label>
                            <input type="date" id="to_date" name="to_date" class="form-control">
                        </div>
                    </div>
                </form>

                <!-- Other content goes here -->

                <div id="printableArea">
                    <div class="card-title text-center">
                        <h5>Daily Cash Revenue Analysis</h5>
                    </div>
                    <!-- Selected Date Range Section -->
                    <div id="selectedDateRange" class="mb-4"></div>

                    <!-- Report Table -->
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Details</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody id="reportTableBody">
                            <tr>
    <td>Total Services Revenue</td>
    <td class="text-success" id="total-services">0.00</td>
</tr>
<tr>
    <td>Total Products Revenue</td>
    <td class="text-success" id="total-products">0.00</td>
</tr>
<tr>
    <td>Total Direct Sales </td>
    <td class="text-success" id="total-sales">0.00</td>
</tr>
<tr>
    <td>Total Tax Sales </td>
    <td class="text-success" id="total-tax-sales">0.00</td>
</tr>
<tr class="table-primary">
    <td><strong>Total Daily Revenue</strong></td>
    <td><strong class="text-primary" id="total-revenue">0.00</strong></td>
</tr>
<tr>
    <td>Cash Payments on Customers</td>
    <td id="total-cash-payments">0.00</td>
</tr>
<tr class="table-success">
    <td><strong>Total Assumed Cash Income</strong></td>
    <td><strong class="text-success" id="total-cash">0.00</strong></td>
</tr>
<tr class="table-danger">
    <td>Total Other Expenses</td>
    <td><strong class="text-danger" id="total-expenses">0.00</strong></td>
</tr>

                        </tbody>
                    </table>

                    <!-- Summary Section -->
                    <div class="mt-4">
                        <div class="row">
                            <div class="col-3">
                                <div class="">
                                    <strong>Net Income:</strong>
                                    <p id="net-income">0.00</p>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class=" ">
                                    <strong>Cash Payments Deduction:</strong>
                                    <p id="cash-deduction">0.00</p>
                                </div>
                            </div>
                            {{-- <div class="col-3">
                                <div class=" ">
                                    <strong>Credit Card Payments Deduction:</strong>
                                    <p id="credit-card-deduction">0.00</p>
                                </div>
                            </div> --}}
                            <div class="col-3">
                                <div class="">
                                    <strong>Other Company Receipt Deduction:</strong>
                                    <p id="other-deduction">0.00</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        // Custom validation method to check if To Date is after From Date
        $.validator.addMethod('dateAfter', function(value, element, param) {
            let fromDate = $('#from_date').val();
            return new Date(value) >= new Date(fromDate);
        }, "The To Date cannot be before the From Date.");

        // Form validation using jQuery validate
        $('#dateFilterForm').validate({
            rules: {
                from_date: {
                    required: true,
                    date: true
                },
                to_date: {
                    required: true,
                    date: true,
                    dateAfter: true // Custom rule to ensure to_date is after from_date
                }
            },
            messages: {
                from_date: {
                    required: "Please select a From Date.",
                    date: "Please enter a valid date."
                },
                to_date: {
                    required: "Please select a To Date.",
                    date: "Please enter a valid date.",
                    dateAfter: "The To Date cannot be before the From Date."
                }
            },
            errorClass: "error text-danger fs--1",
            errorElement: "span",
            highlight: function(element, errorClass, validClass) {
                $(element).addClass(errorClass).removeClass(validClass);
                $(element.form).find("label[for=" + element.id + "]").addClass(errorClass);
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass(errorClass).addClass(validClass);
                $(element.form).find("label[for=" + element.id + "]").removeClass(errorClass);
            },
            submitHandler: function(form) {
                // If the form is valid, trigger the AJAX request
                let fromDate = $('#from_date').val();
                let toDate = $('#to_date').val();

                // Send AJAX request to backend
                $.ajax({
                    url: "{{ route('report.daily_revenues') }}", // Adjust the route if needed
                    method: "GET",
                    data: {
                        from_date: fromDate,
                        to_date: toDate
                    },
                    success: function(response) {

                        if (response) {
    // Update the selected date range above the table
    $('#selectedDateRange').html(
        `<div class="alert alert-info text-center">
            <strong>Selected Date Range:</strong> ${fromDate} to ${toDate}
        </div>`
    );

    // Ensure response data exists and update the table cells
    $('#total-services').text(parseFloat(response.total_services_revenue || 0).toFixed(2));
    $('#total-products').text(parseFloat(response.total_products_revenue || 0).toFixed(2));
    $('#total-sales').text(parseFloat(response.total_sales || 0).toFixed(2));
    $('#total-tax-sales').text(parseFloat(response.total_taxes || 0).toFixed(2));
    $('#total-revenue').text(parseFloat(response.total_sales_after_tax || 0).toFixed(2)); // Adjusted to show total sales as example
    $('#total-cash-payments').text(parseFloat(response.total_customer_deposits || 0).toFixed(2));
    $('#total-cash').text(parseFloat(response.total_sales || 0).toFixed(2)); // Adjusted to show total sales as example
    $('#total-expenses').text(parseFloat(response.total_other_expenses || 0).toFixed(2));
    $('#total-cash-payments').text(parseFloat(response.total_customer_deposits || 0).toFixed(2));
} else {
    alert('Failed to retrieve data. Please try again.');
}


                    },
                });

                return false; // Prevent form submission
            }
        });

        // Trigger AJAX form submission when date fields change
        $('#from_date, #to_date').on('change', function() {
            if ($('#dateFilterForm').valid()) {
                $('#dateFilterForm').submit();
            }
        });

        // Print button functionality
        $('#printButton').on('click', function() {
            const printContents = $('#printableArea').html();
            const originalContents = $('body').html();

            $('body').html(printContents);
            window.print();
            $('body').html(originalContents);
            location.reload();
        });

        // Refresh button functionality (reload the page)
        $('.btn-primary').on('click', function() {
            location.reload(); // Reload the page
        });
    });
</script>
@endsection
