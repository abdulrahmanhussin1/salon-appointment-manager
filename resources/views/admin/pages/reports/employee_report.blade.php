@extends('admin.layouts.app')
@section('title')
    {{ __('Employee Report') }}
@endsection
@section('css')
    <style>
        .dataTables_filter {
            margin-right: 10px;

        }
    </style>
@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="card-title">Employee Services Report</h4>
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
                        <label>Service</label>
                        <select class="form-control" id="service_filter">
                            <option value="">All Services</option>
                            @foreach ($services as $service)
                                <option value="{{ $service->id }}">{{ $service->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button class="btn btn-primary btn-sm btn-block mt-4" id="filter_button">Filters</button>
                    </div>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive">
                <table class="table table-bordered" id="employees-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Service / Product</th>
                            <th>Date</th>
                            <th>Services Count</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                </table>
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
                    url: '{{ route('report.employee-services.data') }}',
                    data: function(d) {
                        d.start_date = $('#start_date').val();
                        d.end_date = $('#end_date').val();
                        d.employee_id = $('#employee_filter').val();
                        d.service_id = $('#service_filter').val(); // Add service filter

                    }
                },
                columns: [{
                        data: 'employee_name',
                        name: 'provider.name'
                    },
                    {
                        data: 'service_name',
                        name: 'service.name'
                    },
                    {
                        data: 'invoice_date',
                        name: 'salesInvoice.invoice_date'
                    },
                    {
                        data: 'quantity',
                        name: 'quantity'
                    },
                    {
                        data: 'total_amount',
                        name: 'subtotal'
                    }
                ],
                order: [
                    [2, 'desc']
                ],
                dom: 'Bflrtip',
                buttons: [
                    'excel', 'pdf', 'print'
                ]
            });

            $('#filter_button').click(function() {
                table.draw();
                updateStats();
            });

            function updateStats() {
                $.ajax({
                    url: '{{ route('report.employee-services.stats') }}',
                    data: {
                        start_date: $('#start_date').val(),
                        end_date: $('#end_date').val(),
                        employee_id: $('#employee_filter').val(),
                        service_id: $('#service_filter').val() // Add service filter

                    },
                    success: function(data) {
                        // Update summary statistics if needed
                    }
                });
            }
        });
    </script>
@endsection
