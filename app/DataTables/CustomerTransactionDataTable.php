<?php

namespace App\DataTables;

use App\Models\CustomerTransaction;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;

class CustomerTransactionDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            //->addColumn('action', 'customertransaction.action')
            ->editColumn('amount', function ($model) {
                return number_format($model->amount, 2);
            })

            ->editColumn('created_at', function ($model) {
                return $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null;
            })
            ->editColumn('updated_at', function ($model) {
                return $model->updated_at ? $model->created_at->format('Y-m-d H:i:s') : null;
            })
            ->editColumn('updated_at', function ($model) {
                return $model->updated_at? $model->updated_at->format('Y-m-d H:i:s') : null;
            })
            ->addColumn('reference_type', function ($model) {
                return $model->reference_type;
            })

            ->addColumn('customer_id', function ($model) {
                return $model->customer? $model->customer->name : null;
            })

            ->addColumn('created_by', function ($model) {
                return $model->createdBy? $model->createdBy->name : null;
            })

            ->setRowId('id');

    }

    /**
     * Get the query source of dataTable.
     */
    public function query(CustomerTransaction $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('customertransaction-table')
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
            Column::make('customer_id')->addClass('text-center')->title('Customer'),
            Column::make('reference_type')->addClass('text-center')->title('Reference'),
            //Column::make('reference_id')->addClass('text-center'),,
            Column::make('amount')->addClass('text-center'),
            Column::make('notes')->addClass('text-center'),
            Column::make('created_by')->addClass('text-center'),
            Column::make('created_at')->addClass('text-center'),
            Column::make('updated_at')->addClass('text-center'),
            // Column::computed('action')
            //     ->exportable(false)
            //     ->printable(false)
            //     ->width(60)
            //     ->addClass('text-center'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'CustomerTransaction_' . date('YmdHis');
    }
}
