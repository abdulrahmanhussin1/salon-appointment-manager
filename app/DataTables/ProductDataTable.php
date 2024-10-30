<?php

namespace App\DataTables;

use App\Models\Product;
use App\Traits\AppHelper;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Editor\Editor;
use Yajra\DataTables\Html\Editor\Fields;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Storage;

class ProductDataTable extends DataTable
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
                if (AppHelper::perUser('products.edit')) {
                    $html .= '<a href="' . route('products.edit', ['product' => $model]) . '" class="dropdown-item">Edit</a>';
                }

                if (AppHelper::perUser('products.show')) {
                    $html .= '<a href="' . route('products.show', ['product' => $model]) . '" class="dropdown-item">Product Details</a>';
                }
                if (AppHelper::perUser('products.destroy')) {
                    $html .= '<div class="dropdown-divider"></div><a href="#" class="dropdown-item text-danger delete-this-product" data-id="' . $model->id . '" data-url="' . route('products.destroy', ['product' => $model]) . '">Delete</a></div></div>';
                }
                return $html;
            })

            ->editColumn('supplier_price', function ($model) {
                return $model->supplier_price ? 'L.E ' . number_format($model->supplier_price, 2) : null;
            })
            ->editColumn('customer_price', function ($model) {
                return $model->customer_price ? 'L.E ' . number_format($model->customer_price, 2) : null;
            })
            ->editColumn('code', function ($model) {
                return $model->code ?? null;
            })
            ->editColumn('image', function ($model) {
                if ($model->image && Storage::exists($model->image)) {
                    return '<img src="' . asset('storage/' . $model->image) . '" alt="' . $model->name . '" style="max-width: 50px; max-height: 75px;">';
                }
                return '<img src="' . asset('admin-assets/assets/img/OIP.jpeg') . '" alt="' . $model->name . '" style="max-width: 50px; max-height: 75px;">';
            })

            ->editColumn('category_id', function ($model) {
                return $model->productCategory ? $model->productCategory->name : null;
            })
            ->editColumn('supplier_id', function ($model) {
                return $model->supplier ? $model->supplier->name : null;
            })
            ->editColumn('unit_id', function ($model) {
                return $model->unit ? $model->unit->name : null;
            })


            ->editColumn('status', function ($model) {
                if ($model->status == 'active') {
                    return '<i class="bi bi-circle-fill mx-2 text-success"></i>' . ucfirst($model->status);
                } elseif ($model->status == 'inactive') {
                    return '<i class="bi bi-circle-fill mx-2 text-secondary"></i>' . ucfirst($model->status);
                }
            })

            ->editColumn('description', function ($model) {
                return $model->description ? $model->description : null;
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

            ->rawColumns(['action', 'status', 'image'])->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Product $model): QueryBuilder
    {
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('product-table')
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
            Column::make('code')->addClass('text-center'),

            Column::make('supplier_price')->addClass('text-center')->title('Purchasing Price'),
            Column::make('customer_price')->addClass('text-center')->title('Selling Price'),
            Column::make('category_id')->addClass('text-center')->title('Category'),
            Column::make('supplier_id')->addClass('text-center')->title('Supplier'),
            Column::make('unit_id')->addClass('text-center')->title('Unit'),
            Column::make('status')->addClass('text-center'),
            Column::make('description')->addClass('text-center'),

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
        return 'Product_' . date('YmdHis');
    }
}
