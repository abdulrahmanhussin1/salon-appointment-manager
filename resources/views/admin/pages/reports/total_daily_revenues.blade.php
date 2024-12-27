@extends('admin.layouts.app')
@section('title')
    {{ __('Daily Cash Revenues Report') }}
@endsection
@section('content')

        <div class="card">
            <div class="card-header">
                <h4 class="text-dark">Daily Financial Report</h4>
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

                <table class="table table-bordered table-striped" id="report-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Cash</th>
                            <th>Other payment Methods</th>
                            <th>Expenses</th>
                            <th>Other</th>
                            <th>Net Total</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>Total:</th>
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

            let table = $('#report-table').DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                searching: false,
                dom: 'Bfrtip',
                ajax: {
                    url: "{{ route('report.TotalDailyRevenues') }}",
                    type: "POST",
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                    },
                    // Prevent initial ajax request
                    "init": false
                },
                // Defer the loading of data
                deferLoading: 0,
                columns: [
                    {data: 'date', name: 'date'},
                    {
                        data: 'total',
                        name: 'total',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'cash',
                        name: 'cash',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'other_payment_methods',
                        name: 'other_payment_methods',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'total_expenses',
                        name: 'expenses',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'deposits',
                        name: 'deposits',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    },
                    {
                        data: 'net_total',
                        name: 'net_total',
                        render: function(data) {
                            return parseFloat(data).toFixed(2);
                        }
                    }
                ],
                footerCallback: function(row, data, start, end, display) {
                    var api = this.api();

                    // Calculate column totals
                    [1, 2, 3, 4, 5, 6].forEach(function(index) {
                        var total = api
                            .column(index)
                            .data()
                            .reduce(function(a, b) {
                                return parseFloat(a) + parseFloat(b);
                            }, 0);

                        $(api.column(index).footer()).html(total.toFixed(2));
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
