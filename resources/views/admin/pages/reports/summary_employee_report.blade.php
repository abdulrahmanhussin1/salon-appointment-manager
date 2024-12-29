@extends('admin.layouts.app')
@section('title')
    {{ __('Employee summary Report') }}
@endsection
@section('css')
    <style>
        .dataTables_filter {
            margin-right: 10px;

        }
    </style>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Employee Performance Summary</h4>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>From Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date"
                                value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>To Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date"
                                value="{{ date('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Employee</label>
                            <select class="form-control" id="employee_filter">
                                <option value="">All Employees</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary btn-block" id="filter_button">Apply Filters</button>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Employees</h5>
                                <h3 class="card-text" id="total_employees">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Amount</h5>
                                <h3 class="card-text" id="total_amount">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Movements</h5>
                                <h3 class="card-text" id="total_movements">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Invoices</h5>
                                <h3 class="card-text" id="total_invoices">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DataTable -->
                <div class="table-responsive">
                    <table class="table table-bordered" id="employees-table">
                        <thead>
                            <tr>
                                <th>Employee Name</th>
                                <th>Services Count</th>
                                <th>Products Count</th>
                                <th>Total Movements</th>
                                <th>Invoices Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(function() {
            let table = $('#employees-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('report.employee-summary-services.data') }}',
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.employee_id = $('#employee_filter').val();
                    }
                },
                columns: [{
                        data: 'employee_name',
                        name: 'employees.name'
                    },
                    {
                        data: 'services_count',
                        name: 'services_count'
                    },
                    {
                        data: 'products_count',
                        name: 'products_count'
                    },
                    {
                        data: 'total_movements',
                        name: 'total_movements'
                    },
                    {
                        data: 'invoices_count',
                        name: 'invoices_count'
                    },
                    {
                        data: 'total_amount',
                        name: 'total_amount'
                    }
                ],
                order: [
                    [5, 'desc']
                ], // Sort by total amount by default
                dom: 'Bfrtip',
                buttons: [
                    'excel', 'pdf', 'print'
                ]
            });

            // Initial load of stats and table
            updateStats();

            $('#filter_button').click(function() {
                table.draw();
                updateStats();
            });

            function updateStats() {
                $.ajax({
                    url: '{{ route('report.employee-summary-services.stats') }}',
                    data: {
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        employee_id: $('#employee_filter').val()
                    },
                    success: function(data) {
                        $('#total_employees').text(data.total_employees.toLocaleString());
                        $('#total_amount').text(Number(data.total_amount).toLocaleString('en-US', {
                            minimumFractionDigits: 2,
                            maximumFractionDigits: 2
                        }));
                        $('#total_movements').text(data.total_movements.toLocaleString());
                        $('#total_invoices').text(data.total_invoices.toLocaleString());
                    }
                });
            }
        });
    </script>
@endsection
