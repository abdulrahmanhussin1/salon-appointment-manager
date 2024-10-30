<?php

namespace App\DataTables;

use App\Models\Tool;
use App\Traits\AppHelper;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class ToolDataTable extends DataTable
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
                if (AppHelper::perUser('tools.edit')) {
                    $html .= '<a href="' . route('tools.edit', ['tool' => $model]) . '" class="dropdown-item">Edit</a>';
                }
                if (AppHelper::perUser('tools.destroy')) {
                    $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-tool" data-id="' . $model->id . '" data-url="' . route('tools.destroy', ['tool' => $model]) . '">Delete</a></div></div>';
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

            ->editColumn('image', function ($model) {
                if ($model->image && Storage::exists($model->image)) {
                    return '<img src="' . asset('storage/' . $model->image) . '" alt="' . $model->name . '" style="max-width: 75px; max-height: 75px;">';
                }
                return '<img src="' . asset('admin-assets/assets/img/OIP.jpeg') . '" alt="' . $model->name . '" style="max-width: 75px; max-height: 75px;">';
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

            ->rawColumns(['action', 'status', 'image'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Tool $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('tool-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->orderBy(1)
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
            Column::make('image')->addClass('text-center'),
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
        return 'Tool_' . date('YmdHis');
    }
}
