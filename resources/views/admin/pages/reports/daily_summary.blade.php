@extends('admin.layouts.app')
@section('title')
    {{ __('Daily Summary Report') }}
@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <h4>Daily Summary Report</h4>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">Date Range</span>
                        <input type="date" id="start_date" class="form-control">
                        <input type="date" id="end_date" class="form-control">
                        <button id="filter" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </div>

            <table class="table table-bordered table-striped" id="summary-table">
                <thead>
                    <tr>
                        <th rowspan="2">Date</th>
                        <th rowspan="2">Customers</th>
                        <th rowspan="2">Staff</th>
                        <th rowspan="2">Expenses</th>
                        <th colspan="3" class="text-center bg-light">Services</th>
                        <th colspan="2" class="text-center bg-light">Products</th>
                        <th colspan="4" class="text-center bg-warning">Purchases</th>
                        <th colspan="4" class="text-center bg-info">Sales Summary</th>
                    </tr>
                    <tr>
                        <th>Count</th>
                        <th>Sales</th>
                        <th>Commission</th>
                        <th>Count</th>
                        <th>Sales</th>
                        <th>Count</th>
                        <th>Amount</th>
                        <th>Discount</th>
                        <th>Net</th>
                        <th>Gross</th>
                        <th>Discount</th>
                        <th>Net</th>
                        <th>Avg/Customer</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Total/Average:</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
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
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            let table = $('#summary-table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                searching: false,
                dom: 'Bfrtip',
                deferLoading: 0,
                ajax: {
                    url: "{{ route('report.dailySummary') }}",
                    type: "POST",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    },
                    "init": false
                },
                columns: [
                    {data: 'date', name: 'date'},
                    {data: 'total_customers', name: 'total_customers'},
                    {data: 'total_employees', name: 'total_employees'},
                    {
                        data: 'total_expenses',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {data: 'services_count', name: 'services_count'},
                    {
                        data: 'services_sales',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'services_commissions',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {data: 'products_count', name: 'products_count'},
                    {
                        data: 'products_sales',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {data: 'purchases_count', name: 'purchases_count'},
                    {
                        data: 'purchases_amount',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'purchases_discount',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'net_purchases',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'gross_sales',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'sales_discount',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'net_sales',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'avg_customer_value',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    }
                ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();

                    // Calculate totals for numeric columns
                    [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16].forEach(function(index) {
                        var total = api.column(index)
                            .data()
                            .reduce(function(a, b) {
                                return parseFloat(a) + parseFloat(b);
                            }, 0);

                        var isAverage = [2, 16].includes(index); // Columns that should show averages
                        var value = isAverage ? (total / data.length) : total;

                        $(api.column(index).footer()).html(
                            parseFloat(value).toFixed(2)
                        );
                    });
                }
            });

            $('#filter').click(function() {
                if (!$('#start_date').val() || !$('#end_date').val()) {
                    alert('Please select both start and end dates');
                    return;
                }
                table.draw();
            });
        });
    </script>
@endsection
