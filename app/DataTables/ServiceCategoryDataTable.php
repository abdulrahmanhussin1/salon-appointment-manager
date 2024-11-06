<?php

namespace App\DataTables;

use App\Traits\AppHelper;
use App\Models\ServiceCategory;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ServiceCategoryDataTable extends DataTable
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
            if (AppHelper::perUser('service_categories.edit')) {
                $html .= '<a href="' . route('service_categories.edit', ['service_category' => $model]) . '" class="dropdown-item">Edit</a>';
            }
            if (AppHelper::perUser('service_categories.destroy')) {
                $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-service_category" data-id="' . $model->id . '" data-url="' . route('service_categories.destroy', ['service_category' => $model]) . '">Delete</a></div></div>';
            }
            return $html;
        })

        ->editColumn('status', function ($model) {
            if ($model->status == 'active') {
                return '<i class="bi bi-circle-fill mx-2 text-success"></i>' . ucfirst($model->status);
            } elseif ($model->status == 'inactive') {
                return '<i class="bi bi-circle-fill mx-2 text-secondary"></i>' . ucfirst($model->status);
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
        })           ->rawColumns(['action', 'status']) ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(ServiceCategory $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('servicecategory-table')
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
            Column::make('name')->addClass('text-center'),
            Column::make('description')->addClass('text-center'),
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
        return 'ServiceCategory_' . date('YmdHis');
    }
}
