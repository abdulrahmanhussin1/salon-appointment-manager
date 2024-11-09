<?php

namespace App\DataTables;

use App\Models\Customer;
use App\Traits\AppHelper;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class CustomerDataTable extends DataTable
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
            if (AppHelper::perUser('customers.edit')) {
                $html .= '<a href="' . route('customers.edit', ['customer' => $model]) . '" class="dropdown-item">Edit</a>';
            }
            if (AppHelper::perUser('customers.destroy')) {
                $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-customer" data-id="' . $model->id . '" data-url="' . route('customers.destroy', ['customer' => $model]) . '">Delete</a></div></div>';
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
        ->editColumn('name', function ($model) {
            return '<a href="'.route('customers.show', ['customer' => $model]).'" target="_blank">'.$model->salutation.'. '. $model->name.'</a>';
        })
        ->editColumn('email', function ($model) {
            return $model->email ?? null;
        })
        ->editColumn('phone', function ($model) {
            return $model->phone?? null;
        })
        ->editColumn('address', function ($model) {
            return $model->address?? null;
        })
        ->editColumn('dob', function ($model) {
            return $model->dob?? null;
        })
        ->editColumn('gender', function ($model) {
            return ucfirst($model->gender?? null);
        })
        ->editColumn('notes', function ($model) {
            return $model->notes?? null;
        })
        ->editColumn('is_vip', function ($model) {
            return $model->is_vip? '<i class="bi bi-star-fill" style="color:#D38E29;font-size: x-large;"></i>' :null;
        })
        ->editColumn('last_service', function ($model) {
            return $model->last_service?? null;
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

        ->rawColumns(['action', 'status','name','is_vip'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Customer $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
                    ->setTableId('customer-table')
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
            Column::make('phone')->addClass('text-center'),
            Column::make('email')->addClass('text-center'),
            Column::make('status')->addClass('text-center'),
            Column::make('address')->addClass('text-center'),
            Column::make('notes')->addClass('text-center'),
            Column::make('is_vip')->addClass('text-center')->title('Vip'),
            Column::make('dob')->addClass('text-center'),
            Column::make('gender')->addClass('text-center'),
            Column::make('last_service')->addClass('text-center'),
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
        return 'Customer_' . date('YmdHis');
    }
}
