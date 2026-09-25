@extends('admin.layouts.app')
@section('title')
    {{ __('Daily Cash Revenues Report') }}
@endsection
@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="text-dark">{{ __('Daily Financial Report') }}</h4>
        </div>

        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text">{{ __('Branch') }}</span>
                        <select id="branch_id" class="form-select" {{ ! ($canSelectAll ?? true) ? 'disabled' : '' }}>
                            @if($canSelectAll ?? true)
                                <option value="all" {{ ($effectiveBranchId ?? null) === null ? 'selected' : '' }}>{{ __('All Branches') }}</option>
                            @endif
                            @foreach($branches ?? [] as $branch)
                                <option value="{{ $branch->id }}" {{ ($effectiveBranchId ?? null) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text">{{ __('Date Range') }}</span>
                        <input type="date" id="start_date" class="form-control">
                        <input type="date" id="end_date" class="form-control">
                        <button id="filter" class="btn btn-primary">{{ __('Filter') }}</button>
                    </div>
                </div>
            </div>

            <table class="table table-bordered table-striped" id="report-table">
                <thead>
                    <tr>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Cash') }}</th>
                        <th>{{ __('Deposits') }}</th>

                        <th>{{ __('Other Payment Methods') }}</th>
                        <th>{{ __('Expenses') }}</th>
                        <th>{{ __('Net Total') }}</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>{{ __('Total:') }}</th>
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
                        d.branch_id = $('#branch_id').val();
                    },
                    // Prevent initial ajax request
                    "init": false
                },
                // Defer the loading of data
                deferLoading: 0,
                columns: [{
                        data: 'date',
                        name: 'date'
                    },
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
                        data: 'deposits',
                        name: 'deposits',
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
                    alert("{{ __('Please select both start and end dates') }}");
                    return;
                }
                table.draw();
            });
        });
    </script>
@endsection
