@extends('admin.layouts.app')
@section('title')
    {{ __('Stock Report') }}
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
            <h3 class="card-title">On Hand Stock List</h3>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="inventory_id">Main Branch</label>
                        <select class="form-control" id="inventory_id" name="inventory_id">
                            <option value="">All Inventories</option>
                            @foreach($inventories as $inventory)
                                <option value="{{ $inventory->id }}">{{ $inventory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3 mt-4">
                    <button class="btn btn-primary" id="filter-btn">Apply Filter</button>
                    <button class="btn btn-secondary" id="reset-btn">Reset</button>
                </div>
            </div>

            <!-- DataTable -->
            <div class="table-responsive">
                <table class="table table-bordered" id="stock-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th>Quantity</th>
                            <th>Value</th>
                            <th>Avg Cost Price</th>
                            <th>Can Be Sold</th>
                            <th>Remarks</th>
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
    // Initialize DataTable
    let table = $('#stock-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('report.stock_report') }}",
            data: function(d) {
                d.inventory_id = $('#inventory_id').val();
            }
        },
        columns: [
            {data: 'name', name: 'name'},
            {
                data: 'current_stock',
                name: 'current_stock',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US');
                }
            },
            {
                data: 'total_value',
                name: 'total_value',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {
                data: 'supplier_price',
                name: 'supplier_price',
                render: function(data) {
                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {
                data: 'is_target',
                name: 'is_target',
                render: function(data) {
                    return data ? '✓' : '✗';
                }
            },
            {data: 'description', name: 'description'}
        ],
        dom: 'Blfrtip',
        buttons: [
            'excel',
            'pdf',
            'print'
        ],
        footerCallback: function (row, data, start, end, display) {
            var api = this.api();

            // Remove the formatting to get numeric data for summation
            var intVal = function (i) {
                return typeof i === 'string' ?
                    i.replace(/[\$,]/g, '') * 1 :
                    typeof i === 'number' ?
                        i : 0;
            };

            // Total quantity
            let totalQuantity = api
                .column(1)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Total value
            let totalValue = api
                .column(2)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0);

            // Average cost
            let avgCost = api
                .column(3)
                .data()
                .reduce(function (a, b) {
                    return intVal(a) + intVal(b);
                }, 0) / api.column(3).data().length;

            // Update footer cells
            $(api.column(1).footer()).html(totalQuantity.toLocaleString('en-US'));
            $(api.column(2).footer()).html(totalValue.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
            $(api.column(3).footer()).html(avgCost.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }));
        }
    });

    // Filter button click handler
    $('#filter-btn').click(function() {
        table.draw();
    });

    // Reset button click handler
    $('#reset-btn').click(function() {
        $('#inventory_id').val('');
        table.draw();
    });
});
</script>
@endsection
