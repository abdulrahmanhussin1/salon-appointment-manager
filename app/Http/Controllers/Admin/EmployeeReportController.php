<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\SalesInvoiceDetail;
use App\Models\Service;
use App\Traits\HasBranchFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeeReportController extends Controller
{
    use HasBranchFilter;

    public function index(Request $request)
    {
        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $employeesQuery = Employee::select('id', 'name')->where('status', 'active');
        if ($effectiveBranchId) {
            $employeesQuery->where('branch_id', $effectiveBranchId);
        }
        $employees = $employeesQuery->get();
        $services = Service::select('id', 'name')->where('status', 'active')->get();

        return view('admin.pages.reports.employee_report', compact('employees', 'services', 'branches', 'canSelectAll', 'effectiveBranchId'));
    }

    public function getData(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $query = SalesInvoiceDetail::with(['provider', 'salesInvoice', 'service', 'product'])
            ->select('sales_invoice_details.*')
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_details.sales_invoice_id')
            ->where('sales_invoices.status', 'active');

        if ($effectiveBranchId) {
            $query->where('sales_invoices.branch_id', $effectiveBranchId);
        }

        // Apply date filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('sales_invoices.invoice_date', [
                Carbon::parse($request->start_date)->toDateString(),
                Carbon::parse($request->end_date)->toDateString(),
            ]);
        }

        // Apply employee filter
        if ($request->filled('employee_id')) {
            $query->where('provider_id', $request->employee_id);
        }

        // Apply service filter
        if ($request->filled('service_id')) {
            $query->where('service_id', $request->service_id);
        }

        return DataTables::of($query)
            ->addColumn('employee_name', function ($row) {
                return $row->provider?->name;
            })
            ->addColumn('service_name', function ($row) {
                return $row->service ? $row->service->name : $row->product?->name;
            })
            ->addColumn('invoice_date', function ($row) {
                return $row->salesInvoice?->invoice_date;
            })
            ->addColumn('total_amount', function ($row) {
                return number_format($row->subtotal, 2);
            })
            ->addColumn('commission_amount', function ($row) {
                return number_format($row->commission_amount ?? 0, 2);
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function getEmployeeStats(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $query = SalesInvoiceDetail::with(['provider'])
            ->join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_details.sales_invoice_id')
            ->where('sales_invoices.status', 'active')
            ->groupBy('provider_id')
            ->select(
                'provider_id',
                \DB::raw('COUNT(*) as total_services'),
                \DB::raw('SUM(subtotal) as total_amount'),
                \DB::raw('SUM(commission_amount) as total_commission')
            );

        if ($effectiveBranchId) {
            $query->where('sales_invoices.branch_id', $effectiveBranchId);
        }

        // Apply date filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('sales_invoices.invoice_date', [
                Carbon::parse($request->start_date)->toDateString(),
                Carbon::parse($request->end_date)->toDateString(),
            ]);
        }

        return $query->get();
    }
}
