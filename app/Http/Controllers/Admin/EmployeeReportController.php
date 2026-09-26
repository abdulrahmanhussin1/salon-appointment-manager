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

        $query = \Illuminate\Support\Facades\DB::table('sales_invoice_details as sid')
            ->join('sales_invoices as si', 'si.id', '=', 'sid.sales_invoice_id')
            ->leftJoin('employees as e', 'e.id', '=', 'sid.provider_id')
            ->leftJoin('services as s', 's.id', '=', 'sid.service_id')
            ->leftJoin('products as p', 'p.id', '=', 'sid.product_id')
            ->where('si.status', 'active')
            ->select([
                'sid.id',
                'sid.subtotal',
                'sid.commission_amount',
                'si.invoice_date',
                'e.name as employee_name',
                \Illuminate\Support\Facades\DB::raw('COALESCE(s.name, p.name) as service_name'),
            ]);

        if ($effectiveBranchId) {
            $query->where('si.branch_id', $effectiveBranchId);
        }

        // Apply date filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('si.invoice_date', [
                Carbon::parse($request->start_date)->toDateString(),
                Carbon::parse($request->end_date)->toDateString(),
            ]);
        }

        // Apply employee filter
        if ($request->filled('employee_id')) {
            $query->where('sid.provider_id', $request->employee_id);
        }

        // Apply service filter
        if ($request->filled('service_id')) {
            $query->where('sid.service_id', $request->service_id);
        }

        return DataTables::of($query)
            ->editColumn('total_amount', function ($row) {
                return number_format((float) $row->subtotal, 2);
            })
            ->editColumn('commission_amount', function ($row) {
                return number_format((float) ($row->commission_amount ?? 0), 2);
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
