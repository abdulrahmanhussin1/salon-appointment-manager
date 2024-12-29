<?php

namespace App\DataTables;

use App\Models\Expense;
use App\Traits\AppHelper;
use Carbon\Carbon;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ExpenseDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query
        // ->when(request('start_date') && request('end_date') , function($q){
        //     $startDate = request()->get('start_date');
        //     $endDate = request()->get('end_date');
        //     $startDate = Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s'); // e.g., 2024-12-29 00:00:00
        //     $endDate = Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');       // e.g., 2024-12-29 23:59:59
        //    $q->whereBetween('created_at', [$startDate, $endDate]);
        // })
        ->when(request('start_date'), function($q) {
            $startDate = Carbon::parse(request('start_date'))->startOfDay()->format('Y-m-d H:i:s'); // e.g., 2024-12-29 00:00:00
            $q->where('created_at', '>=', $startDate);
        })
        ->when(request('end_date'), function($q) {
            $endDate = Carbon::parse(request('end_date'))->endOfDay()->format('Y-m-d H:i:s'); // e.g., 2024-12-29 23:59:59
            $q->where('created_at', '<=', $endDate);
        })
        ->when(request('expense_type_id'), function($q){
            $q->where('expense_type_id', request()->get('expense_type_id'));
        })


        ))
            ->addColumn('action', function ($model) {
                $html = '<div class="font-sans-serif btn-reveal-trigger position-static">
                            <button class="btn btn-sm dropdown-toggle dropdown-caret-none transition-none btn-reveal fs--2"
                            type="button" data-bs-toggle="dropdown" data-boundary="window" aria-haspopup="true" aria-expanded="false" data-bs-reference="parent">
                            <i class="bi bi-three-dots-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end py-2">';
                if (AppHelper::perUser('expenses.edit')) {
                    $html .= '<a href="' . route('expenses.edit', ['expense' => $model]) . '" class="dropdown-item">Edit</a>';
                }
                if (AppHelper::perUser('expenses.destroy')) {
                    $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-expense" data-id="' . $model->id . '" data-url="' . route('expenses.destroy', ['expense' => $model]) . '">Delete</a></div></div>';
                }
                return $html;
            })


            ->editColumn('expense_type_id', function ($model) {
                return $model->expense_type_id ? $model->expenseType->name : 'N/A';
            })

            ->editColumn('status', function ($model) {
                if ($model->status == 'active') {
                    return '<i class="bi bi-check-circle-fill text-success" style="font-size:large"></i>';
                } elseif ($model->status == 'inactive') {
                    return '<i class="bi bi-x-circle-fill text-secondary" style="font-size:large"></i>';
                }
            })

            ->editColumn('created_at', function ($model) {
                return $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null;
            })
            ->editColumn('updated_at', function ($model) {
                return $model->updated_at ? $model->created_at->format('Y-m-d H:i:s') : null;
            })

            ->addColumn('created_by', function ($model) {
                return $model->createdBy ? $model->createdBy->name : null;
            })

            ->rawColumns(['action', 'status'])->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Expense $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('expense-table')
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
            Column::make('expense_type_id')->addClass('text-center')->title('Expense'),
            Column::make('description')->addClass('text-center'),
            Column::make('amount')->addClass('text-center'),
            Column::make('payment_method_id')->addClass('text-center')->title('Payment Method'),
            Column::make('paid_at')->addClass('text-center'),
            Column::make('paid_amount')->addClass('text-center'),
            Column::make('balance')->addClass('text-center'),
            Column::make('invoice_number')->addClass('text-center'),
            Column::make('branch_id')->addClass('text-center')->title('Branch'),
            Column::make('status')->addClass('text-center'),
            Column::make('created_by')->addClass('text-center'),
            Column::make('created_at')->addClass('text-center'),
            Column::make('updated_at')->addClass('text-center'),
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
        return 'Expense_' . date('YmdHis');
    }
}
