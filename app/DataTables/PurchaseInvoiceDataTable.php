<?php

namespace App\DataTables;

use App\Traits\AppHelper;
use App\Models\PurchaseInvoice;
use Carbon\Carbon;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class PurchaseInvoiceDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query
        ->when(request('start_date'), function($q) {
            $startDate = Carbon::parse(request('start_date'))->startOfDay()->format('Y-m-d H:i:s'); // e.g., 2024-12-29 00:00:00
            $q->where('created_at', '>=', $startDate);
        })
        ->when(request('end_date'), function($q) {
            $endDate = Carbon::parse(request('end_date'))->endOfDay()->format('Y-m-d H:i:s'); // e.g., 2024-12-29 23:59:59
            $q->where('created_at', '<=', $endDate);
        })
        ->when(request('supplier_id'), function($q){
            $q->where('supplier_id', request()->get('supplier_id'));
        })
        ->when(request('branch_id'), function($q){
            $q->where('branch_id', request()->get('branch_id'));
        })
))
            ->addColumn('action', function ($model) {
                $html = '<div class="font-sans-serif btn-reveal-trigger position-static">
                        <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2"
                        type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                        <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end py-2">';
                if (AppHelper::perUser('purchase_invoices.edit')) {
                    $html .= '<a href="' . route('purchase_invoices.edit', ['purchase_invoice' => $model]) . '" class="dropdown-item">Edit</a>';
                }
                // if (AppHelper::perUser('purchase_invoices.destroy')) {
                //     $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-purchase_invoice" data-id="' . $model->id . '" data-url="' . route('purchase_invoices.destroy', ['purchase_invoice' => $model]) . '">Delete</a></div></div>';
                // }
                return $html;
            })

            ->editColumn('status', function ($model) {
                if ($model->status == 'active') {
                    return '<i class="bi bi-check-circle-fill text-success" style="font-size:large">Active</i>';
                } elseif ($model->status == 'inactive') {
                    return '<i class="bi bi-x-circle-fill text-secondary" style="font-size:large">Inactive</i>';
            } elseif ($model->status == 'draft') {

                return '<i class="bi  bi-dash-circle-fill text-warning" style="font-size:large">Draft</i>';
            }
            })

            ->editColumn('supplier_id', function ($model) {
                return $model->supplier ? $model->supplier->name : null;
            })
            ->editColumn('invoice_date', function ($model) {
                return $model->invoice_date? $model->invoice_date : null;
            })
            ->addColumn('net_amount', function ($model) {
                return '$'. $model->total_amount - $model->invoice_discount;
            })
            ->editColumn('branch_id', function ($model) {
                return $model->branch_id ? $model->branch->name : '';
            })
            ->rawColumns(['action', 'status'])->setRowId('id');
        // Remove 'action' column during export/print
        if (request()->has('export') || request()->has('print')) {
            $datatable->removeColumn('action');
        }
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(PurchaseInvoice $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('purchase_invoice-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('<B><"d-flex w-100 py-2 align-items-center justify-content-between"lf>rtip')
            ->orderBy(0, 'desc')
            ->selectStyleSingle()
 ->buttons([
            Button::make('excel')->exportOptions([
                'columns' => ':not(:last-child)', // Exclude the last column (action)
            ]),
            Button::make('csv')->exportOptions([
                'columns' => ':not(:last-child)', // Exclude the last column (action)
            ]),
            Button::make('pdf')->exportOptions([
                'columns' => ':not(:last-child)', // Exclude the last column (action)
            ]),
            Button::make('print')->exportOptions([
                'columns' => ':not(:last-child)', // Exclude the last column (action)
            ]),
        ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->addClass('text-center'),
            Column::make('supplier_id')->addClass('text-center')->title('Supplier'),
            Column::make('invoice_number')->addClass('text-center'),
            Column::make('invoice_date')->addClass('text-center'),
            Column::make('total_amount')->addClass('text-center'),
            Column::make('invoice_discount')->addClass('text-center'),
            Column::make('net_amount')->addClass('text-center'),
            Column::make('branch_id')->addClass('text-center')->title('Branch'),
            Column::make('status')->addClass('text-center'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'PurchaseInvoice_' . date('YmdHis');
    }
}
