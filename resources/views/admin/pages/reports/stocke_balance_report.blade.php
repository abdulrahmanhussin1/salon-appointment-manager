@extends('admin.layouts.app')
@section('title')
    {{ __('Stock Balance Report') }}
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
            <h3 class="card-title">{{ __('Store Balance Details') }}</h3>
            <div class="text-muted">
                {{ __('As Of') }}: {{ now()->format('d/m/Y') }} - {{ __('Main Branch') }} - {{ __('Month') }} {{ now()->format('m') }} - {{ __('Year') }} {{ now()->format('Y') }}
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="branch_id">{{ __('Branch') }}</label>
                        <select class="form-control" id="branch_id" name="branch_id" {{ ! ($canSelectAll ?? true) ? 'disabled' : '' }}>
                            @if($canSelectAll ?? true)
                                <option value="all" {{ ($effectiveBranchId ?? null) === null ? 'selected' : '' }}>{{ __('All Branches') }}</option>
                            @endif
                            @foreach($branches ?? [] as $branch)
                                <option value="{{ $branch->id }}" {{ ($effectiveBranchId ?? null) == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="inventory_id">{{ __('Inventory') }}</label>
                        <select class="form-control" id="inventory_id" name="inventory_id">
                            <option value="">{{ __('All') }}</option>
                            @foreach($inventories as $inventory)
                                <option value="{{ $inventory->id }}">{{ $inventory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mt-4">
                    <button class="btn btn-primary" id="filter-btn"><i class="fa fa-filter"></i> {{ __('Filter') }}</button>
                    <button class="btn btn-secondary" id="reset-btn">{{ __('Reset') }}</button>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="balance-table">
                    <thead>
                        <tr>
                            <th rowspan="2">{{ __('Item') }}</th>
                            <th colspan="1">{{ __('Unit Cost') }}</th>
                            <th colspan="2">{{ __('Beginning') }}</th>
                            <th colspan="2">{{ __('In') }}</th>
                            <th colspan="2">{{ __('Out') }}</th>
                            <th colspan="2">{{ __('Onhand') }}</th>
                        </tr>
                        <tr>
                            <th>{{ __('L.E') }}</th>
                            <th>{{ __('QTY') }}</th>
                            <th>{{ __('L.E') }}</th>
                            <th>{{ __('QTY') }}</th>
                            <th>{{ __('L.E') }}</th>
                            <th>{{ __('QTY') }}</th>
                            <th>{{ __('L.E') }}</th>
                            <th>{{ __('QTY') }}</th>
                            <th>{{ __('L.E') }}</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th>{{ __('Total') }}:</th>
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
    </div>
</div>
@endsection
@section('js')
<script>
$(function() {
    let table = $('#balance-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('report.stock_balance_transfer') }}",
            data: function(d) {
                d.inventory_id = $('#inventory_id').val();
                d.branch_id = $('#branch_id').val();
            }
        },
        columns: [
            {data: 'name', name: 'name'},
            {
                data: 'unit_cost',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {data: 'beginning_qty'},
            {
                data: 'beginning_value',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {data: 'in_qty'},
            {
                data: 'in_value',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {data: 'out_qty'},
            {
                data: 'out_value',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {data: 'onhand_qty'},
            {
                data: 'onhand_value',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            }
        ],
        dom: 'Bflrtip',
        buttons: ['excel', 'pdf', 'print'],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove formatting to get numeric data for summation
            var intVal = function (i) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '') * 1 :
                    typeof i === 'number' ?
                        i : 0;
            };

            // Calculate totals for each column
            var columns = [1, 2, 3, 4, 5, 6, 7, 8, 9]; // Column indexes to sum
            columns.forEach(function(colIndex) {
                var total = api
                    .column(colIndex)
                    .data()
                    .reduce(function (a, b) {
                        return intVal(a) + intVal(b);
                    }, 0);

                // Format based on whether it's a quantity or value column
                var formattedTotal = colIndex % 2 === 0 ?
                    total.toLocaleString('en-US') :
                    total.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });

                $(api.column(colIndex).footer()).html(formattedTotal);
            });
        }
    });

    $('#filter-btn').click(function() {
        table.draw();
    });

    $('#reset-btn').click(function() {
        $('#inventory_id').val('');
        table.draw();
    });
});
</script>
@endsection
