<?php

namespace App\DataTables;

use App\Traits\AppHelper;
use App\Models\PurchaseInvoice;
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
        return (new EloquentDataTable($query))
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
                if (AppHelper::perUser('purchase_invoices.destroy')) {
                    $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-purchase_invoice" data-id="' . $model->id . '" data-url="' . route('purchase_invoices.destroy', ['purchase_invoice' => $model]) . '">Delete</a></div></div>';
                }
                return $html;
            })

            ->editColumn('status', function ($model) {
                if ($model->status == 'active') {
                    return '<i class="bi bi-check-circle-fill text-success" style="font-size:large"></i>';
                } elseif ($model->status == 'inactive') {
                    return '<i class="bi bi-x-circle-fill text-secondary" style="font-size:large"></i>';
                }
            })

            ->editColumn('supplier_id', function ($model) {
                return $model->supplier ? $model->supplier->name : null;
            })
            ->editColumn('invoice_date', function ($model) {
                return $model->invoice_date? $model->invoice_date : null;
            })
            ->editColumn('net_amount', function ($model) {
                return '$'. $model->total_amount - $model->invoice_discount;
            })
            ->editColumn('branch_id', function ($model) {
                return '$'. $model->branch_id ? $model->branch->name : '';
            })
            ->rawColumns(['action', 'status'])->setRowId('id');

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
                Button::make('excel'),
                Button::make('csv'),
                Button::make('pdf'),
                Button::make('print'),
                // Button::make('reset'),
                // Button::make('reload')
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
