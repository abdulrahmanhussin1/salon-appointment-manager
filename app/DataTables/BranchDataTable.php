<?php

namespace App\DataTables;

use App\Models\Branch;
use App\Traits\AppHelper;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class BranchDataTable extends DataTable
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
            if (AppHelper::perUser('branches.edit')) {
                $html .= '<a href="' . route('branches.edit', ['branch' => $model]) . '" class="dropdown-item">Edit</a>';
            }
            if (AppHelper::perUser('branches.destroy')) {
                $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-branch" data-id="' . $model->id . '" data-url="' . route('branches.destroy', ['branch' => $model]) . '">Delete</a></div></div>';
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
        ->editColumn('created_at', function ($model) {
            return $model->created_at ? $model->created_at->format('Y-m-d H:i:s') : null;
        })
        ->editColumn('updated_at', function ($model) {
            return $model->updated_at ? $model->created_at->format('Y-m-d H:i:s') : null;
        })

        ->editColumn('manager_id', function ($model) {
            return $model->manager ? $model->manager->name : null;
        })

        ->addColumn('created_by', function ($model) {
            return $model->createdBy ? $model->createdBy->name : null;
        })

        ->rawColumns(['action', 'status'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Branch $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('branch-table')
                    ->columns($this->getColumns())
                    ->minifiedAjax()
                    ->dom('<B><"d-flex w-100 py-2 align-items-center justify-content-between"lf>rtip')
                    ->orderBy(0,'desc')
                    ->selectStyleSingle()
                    ->buttons([
                        Button::make('excel'),
                        Button::make('csv'),
                        Button::make('pdf'),
                        Button::make('print'),
                        //Button::make('reset'),
                        //Button::make('reload')
                    ]);
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->addClass('text-center'),
            Column::make('name')->addClass('text-center'),
            Column::make('manager_id')->addClass('text-center')->title('Manager'),
            Column::make('phone')->addClass('text-center'),
            Column::make('email')->addClass('text-center'),
            Column::make('address')->addClass('text-center'),
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
        return 'Branch_' . date('YmdHis');
    }
}
