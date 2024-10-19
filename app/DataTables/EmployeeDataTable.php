<?php

namespace App\DataTables;

use App\Models\Employee;
use App\Traits\AppHelper;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class EmployeeDataTable extends DataTable
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
                if (AppHelper::perUser('employees.edit')) {
                    $html .= '<a href="' . route('employees.edit', ['employee' => $model]) . '" class="dropdown-item">Edit</a>';
                }
                if (AppHelper::perUser('employees.destroy')) {
                    $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-employee" data-id="' . $model->id . '" data-url="' . route('employees.destroy', ['employee' => $model]) . '">Delete</a></div></div>';
                }
                return $html;
            })

            ->editColumn('name', function ($model) {
                return $model->name ?? null;
            })

            ->editColumn('phone', function ($model) {
                return $model->phone ?? null;
            })
            ->editColumn('job_title', function ($model) {
                return $model->job_title ?? null;
            })
            ->editColumn('finger_print_code', function ($model) {
                return $model->finger_print_code ?? null;
            })
            ->editColumn('image', function ($model) {
                if ($model->image) {
                    return '<img src="' . asset('storage/' . $model->image) . '" alt="' . $model->name . '" style="max-width: 75px; max-height: 75px;">';
                }
                return '<img src="' . asset('admin-assets/assets/img/avatar.jpg') . '" alt="' . $model->name . '" style="max-width: 75px; max-height: 75px;">';
            })

            ->editColumn('employee_level_id', function ($model) {
                return $model->employeeLevel ? $model->employeeLevel->name : null;
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
            })

            ->rawColumns(['action', 'status','image'])->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Employee $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('employee-table')
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
            Column::make('image')->addClass('text-center'),
            Column::make('name')->addClass('text-center'),
            Column::make('phone')->addClass('text-center'),
            Column::make('hiring_date')->addClass('text-center')->title('Hiring Date'),
            Column::make('job_title')->addClass('text-center')->title('Job Title'),
            Column::make('finger_print_code')->addClass('text-center')->title('Finger Print Code'),
            Column::make('employee_level_id')->addClass('text-center')->title('Employee Level'),
            Column::make('status')->addClass('text-center'),
            Column::make('created_by')->addClass('text-center'),
            Column::make('created_at')->addClass('text-center'),
            Column::make('updated_at')->addClass('text-center'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center')

        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Employee_' . date('YmdHis');
    }
}
