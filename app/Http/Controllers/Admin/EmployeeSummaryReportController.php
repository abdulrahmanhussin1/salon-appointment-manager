<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Models\SalesInvoiceDetail;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Yajra\DataTables\Facades\DataTables;

class EmployeeSummaryReportController extends Controller
{
    public function index()
    {
        $employees = Employee::where('status', 'active')->get();
        return view('reports.employee-services-grouped', compact('employees'));
    }

    public function getData(Request $request)
    {
        $query = DB::table('sales_invoice_details')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_details.sales_invoice_id')
            ->join('employees', 'employees.id', '=', 'sales_invoice_details.provider_id')
            ->where('sales_invoices.status', 'active')
            ->select([
                'employees.id',
                'employees.name as employee_name',
                DB::raw('COUNT(DISTINCT CASE WHEN service_id IS NOT NULL THEN sales_invoice_details.id END) as services_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN product_id IS NOT NULL THEN sales_invoice_details.id END) as products_count'),
                DB::raw('COUNT(sales_invoice_details.id) as total_movements'),
                DB::raw('SUM(sales_invoice_details.subtotal) as total_amount'),
                DB::raw('COUNT(DISTINCT sales_invoice_details.sales_invoice_id) as invoices_count')
            ])
            ->groupBy('employees.id', 'employees.name');

        // Apply date filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('sales_invoices.invoice_date', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // Apply employee filter
        if ($request->filled('employee_id')) {
            $query->where('sales_invoice_details.provider_id', $request->employee_id);
        }

        return DataTables::of($query)
            ->addColumn('services_count', function ($row) {
                return number_format($row->services_count);
            })
            ->addColumn('products_count', function ($row) {
                return number_format($row->products_count);
            })
            ->addColumn('total_movements', function ($row) {
                return number_format($row->total_movements);
            })
            ->addColumn('total_amount', function ($row) {
                return number_format($row->total_amount, 2);
            })
            ->addColumn('invoices_count', function ($row) {
                return number_format($row->invoices_count);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getStats(Request $request)
    {
        $query = DB::table('sales_invoice_details')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_details.sales_invoice_id')
            ->where('sales_invoices.status', 'active');

        // Apply date filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('sales_invoices.invoice_date', [
                Carbon::parse($request->start_date)->startOfDay(),
                Carbon::parse($request->end_date)->endOfDay()
            ]);
        }

        // Apply employee filter
        if ($request->filled('employee_id')) {
            $query->where('sales_invoice_details.provider_id', $request->employee_id);
        }

        $stats = $query->select([
            DB::raw('COUNT(DISTINCT provider_id) as total_employees'),
            DB::raw('SUM(subtotal) as total_amount'),
            DB::raw('COUNT(*) as total_movements'),
            DB::raw('COUNT(DISTINCT sales_invoice_id) as total_invoices')
        ])->first();

        // Convert null values to 0
        $stats = [
            'total_employees' => $stats->total_employees ?? 0,
            'total_amount' => $stats->total_amount ?? 0,
            'total_movements' => $stats->total_movements ?? 0,
            'total_invoices' => $stats->total_invoices ?? 0
        ];

        return response()->json($stats);
    }
}
