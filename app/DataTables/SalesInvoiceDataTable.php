<?php

namespace App\DataTables;

use App\Models\SalesInvoice;
use App\Traits\AppHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class SalesInvoiceDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param  QueryBuilder  $query  Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query
            ->when(request('start_date'), function ($q) {
                $startDate = Carbon::parse(request('start_date'))->startOfDay()->format('Y-m-d H:i:s'); // e.g., 2024-12-29 00:00:00
                $q->where('invoice_date', '>=', $startDate);
            })
            ->when(request('end_date'), function ($q) {
                $endDate = Carbon::parse(request('end_date'))->endOfDay()->format('Y-m-d H:i:s'); // e.g., 2024-12-29 23:59:59
                $q->where('invoice_date', '<=', $endDate);
            })
            ->when(request('branch_id'), function ($q) {
                $q->where('branch_id', request('branch_id'));
            })
            ->when(request('created_by'), function ($q) {
                $q->where('created_by', request('created_by'));
            })
        ))
            ->addColumn('action', function ($model) {
                $html = '<div class="font-sans-serif btn-reveal-trigger position-static">
                    <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2"
                    type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                    <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end py-2">';
                if (AppHelper::perUser('sales_invoices.create')) {
                    $html .= '<a class="dropdown-item" data-id="'.$model->id.'" href="'.route('sales_invoices.invoice', $model->id).'">'.__('Invoice').'</a>';
                }
                if ($model->status === 'active' && $model->refund_status !== 'full' && (AppHelper::perUser('refunds.create') || AppHelper::perUser('sales_invoices.create'))) {
                    $html .= '<a class="dropdown-item text-danger" href="'.route('refunds.create', ['invoice_id' => $model->id]).'"><i class="bi bi-arrow-return-left me-1"></i>'.__('Refund / Return').'</a>';
                }
                if ($model->status !== 'voided' && ($model->refund_status ?? 'none') === 'none' && AppHelper::perUser('sales_invoices.void')) {
                    $html .= '<div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="'.route('sales_invoices.invoice', $model->id).'#void">'.__('Void Invoice').'</a>';
                }
                $html .= '</div></div>';

                return $html;
            })
            ->editColumn('status', function ($model) {
                if ($model->status == 'active') {
                    if (($model->refund_status ?? 'none') === 'full') {
                        return '<span class="badge bg-danger"><i class="bi bi-arrow-return-left me-1"></i>'.__('Refunded').'</span>';
                    } elseif (($model->refund_status ?? 'none') === 'partial') {
                        return '<span class="badge bg-warning text-dark"><i class="bi bi-arrow-return-left me-1"></i>'.__('Partial Refund').'</span>';
                    }

                    return '<i class="bi bi-check-circle-fill text-success" style="font-size:large" title="'.__('Active').'"></i>';
                } elseif ($model->status == 'inactive') {
                    return '<i class="bi bi-x-circle-fill text-secondary" style="font-size:large" title="'.__('Inactive').'"></i>';
                } elseif ($model->status == 'voided') {
                    return '<span class="badge bg-danger"><i class="bi bi-slash-circle me-1"></i>'.__('Voided').'</span>';
                } else {
                    return '<span class="badge bg-warning text-dark">'.__('Draft').'</span>';
                }
            })
            ->editColumn('customer_id', function ($model) {
                return $model->customer ? $model->customer->name : null;
            })
            ->editColumn('invoice_date', function ($model) {
                return $model->invoice_date ? $model->invoice_date : null;
            })
            ->editColumn('net_amount', function ($model) {
                return '$'.$model->net_total;
            })
            ->editColumn('branch_id', function ($model) {
                return $model->branch_id ? $model->branch->name : '';
            })
            ->rawColumns(['action', 'status'])->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(SalesInvoice $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('salesinvoice-table')
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
            Column::make('id')->addClass('text-center')->title(__('ID')),
            Column::make('customer_id')->addClass('text-center')->title(__('Customer')),
            Column::make('invoice_date')->addClass('text-center')->title(__('Invoice Date')),
            Column::make('total_amount')->addClass('text-center')->title(__('Total Amount')),
            Column::make('invoice_discount')->addClass('text-center')->title(__('Discount')),
            Column::make('net_amount')->addClass('text-center')->title(__('Net Amount')),
            Column::make('branch_id')->addClass('text-center')->title(__('Branch')),
            Column::make('status')->addClass('text-center')->title(__('Status')),
            Column::computed('action')->title(__('Action'))
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
        return 'SalesInvoice_'.date('YmdHis');
    }
}
