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
            <h3 class="card-title">Store Balance Details</h3>
            <div class="text-muted">
                As Of: {{ now()->format('d/m/Y') }} - Main Branch - Month {{ now()->format('m') }} - Year {{ now()->format('Y') }}
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="inventory_id">Branch</label>
                        <select class="form-control" id="inventory_id" name="inventory_id">
                            <option value="">ALL</option>
                            @foreach($inventories as $inventory)
                                <option value="{{ $inventory->id }}">{{ $inventory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mt-4">
                    <button class="btn btn-primary" id="filter-btn"> Filter</button>
                    <button class="btn btn-secondary" id="reset-btn">Reset</button>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="balance-table">
                    <thead>
                        <tr>
                            <th rowspan="2">Item</th>
                            <th colspan="1">Unit Cost</th>
                            <th colspan="2">Beginning</th>
                            <th colspan="2">In</th>
                            <th colspan="2">Out</th>
                            <th colspan="2">Onhand</th>
                        </tr>
                        <tr>
                            <th>L.E</th>
                            <th>QTY</th>
                            <th>L.E</th>
                            <th>QTY</th>
                            <th>L.E</th>
                            <th>QTY</th>
                            <th>L.E</th>
                            <th>QTY</th>
                            <th>L.E</th>
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
