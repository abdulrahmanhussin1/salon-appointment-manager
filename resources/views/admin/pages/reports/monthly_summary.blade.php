@extends('admin.layouts.app')
@section('title')
    {{ __('Monthly Summary Report') }}
@endsection

@section('css')
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <style>
        .dataTables_wrapper .dt-buttons {
            float: right;
            margin-bottom: 15px;
        }
        .table thead th {
            white-space: nowrap;
        }
        .net-income-row {
            background-color: #f8f9fa !important;
            font-weight: bold;
        }
        .total-row {
            background-color: #e9ecef !important;
            font-weight: bold;
        }
    </style>
@endsection

@section('content')
    <div class="card">
        <div class="card-body m-3">
            <div class="row mb-4 align-items-center">
                <div class="col-md-6">
                    <h1 class="h3 mb-0">Monthly Report <span id="year-display">{{ date('Y') }}</span></h1>
                </div>
                <div class="col-md-6 text-md-end">
                    <select id="year-filter" class="form-select w-auto d-inline-block me-2">
                        @for ($y = date('Y'); $y >= 2020; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="monthly-report-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Services</th>
                            <th>Products</th>
                            <th>Expenses</th>
                            <th>Net Income</th>
                            <th>Purchases</th>
                            <th>Employees</th>
                            <th>New Customers</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="total-row">
                            <th>Total</th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            let table = $('#monthly-report-table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                paging: false,
                searching: false,
                info: false,
                ajax: {
                    url: "{{ route('report.monthlySummary') }}",
                    data: function(d) {
                        d.year = $('#year-filter').val();
                    }
                },
                columns: [
                    {data: 'metric', name: 'metric'},
                    {data: 'services', name: 'services', className: 'text-end'},
                    {data: 'products', name: 'products', className: 'text-end'},
                    {data: 'expenses', name: 'expenses', className: 'text-end'},
                    {data: 'net_income', name: 'net_income', className: 'text-end'},
                    {data: 'purchases', name: 'purchases', className: 'text-end'},
                    {data: 'provider_count', name: 'provider_count', className: 'text-end'},
                    {data: 'new_customers', name: 'new_customers', className: 'text-end'}
                ],
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        className: 'btn btn-secondary btn-sm',
                        text: '<i class="fas fa-copy"></i> Copy'
                    },
                    {
                        extend: 'excel',
                        className: 'btn btn-secondary btn-sm',
                        text: '<i class="fas fa-file-excel"></i> Excel'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-secondary btn-sm',
                        text: '<i class="fas fa-file-pdf"></i> PDF'
                    },
                    {
                        extend: 'print',
                        className: 'btn btn-secondary btn-sm',
                        text: '<i class="fas fa-print"></i> Print'
                    }
                ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();

                    // Calculate totals for each numeric column
                    var columnsToSum = [1, 2, 3, 4, 5, 6, 7]; // Column indices (0-based) to sum
                    columnsToSum.forEach(function(colIndex) {
                        var total = api
                            .column(colIndex)
                            .data()
                            .reduce(function(acc, curr) {
                                // Convert string to number and handle non-numeric values
                                var value = typeof curr === 'string' ?
                                    parseFloat(curr.replace(/[^0-9.-]+/g, '')) : curr;
                                return acc + (isNaN(value) ? 0 : value);
                            }, 0);

                        // Format the total based on the column type
                        var formattedTotal;
                        if (colIndex >= 1 && colIndex <= 5) {
                            // Format as currency for financial columns
                            formattedTotal = new Intl.NumberFormat('en-US', {
                                style: 'currency',
                                currency: 'USD'
                            }).format(total);
                        } else {
                            // Format as number for count columns
                            formattedTotal = new Intl.NumberFormat('en-US').format(total);
                        }

                        // Update footer
                        $(api.column(colIndex).footer()).html(formattedTotal);
                    });
                }
            });

            $('#year-filter').change(function() {
                $('#year-display').text($(this).val());
                table.ajax.reload();
            });
        });
    </script>
@endsection
