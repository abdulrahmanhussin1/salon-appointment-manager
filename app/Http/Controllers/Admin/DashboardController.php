<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\Refund;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Traits\AppHelper;
use App\Traits\HasBranchFilter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use AppHelper, HasBranchFilter;

    /**
     * Cache results in production/staging while avoiding test-state pollution in testing.
     */
    protected function rememberIfProduction(string $key, int $seconds, \Closure $callback)
    {
        if (app()->environment('testing')) {
            return $callback();
        }

        return Cache::remember($key, $seconds, $callback);
    }

    /**
     * Resolve date range from request parameters.
     */
    protected function resolveDateRange(Request $request): array
    {
        $period = $request->input('period', 'today');
        $today = Carbon::today();

        switch ($period) {
            case 'yesterday':
                $from = $today->copy()->subDay()->startOfDay();
                $to = $today->copy()->subDay()->endOfDay();
                break;
            case 'this_week':
                $from = $today->copy()->startOfWeek();
                $to = $today->copy()->endOfWeek();
                break;
            case 'this_month':
                $from = $today->copy()->startOfMonth();
                $to = $today->copy()->endOfMonth();
                break;
            case 'custom':
                $from = $request->filled('from') ? Carbon::parse($request->input('from'))->startOfDay() : $today->copy()->startOfDay();
                $to = $request->filled('to') ? Carbon::parse($request->input('to'))->endOfDay() : $today->copy()->endOfDay();
                break;
            case 'today':
            default:
                $from = $today->copy()->startOfDay();
                $to = $today->copy()->endOfDay();
                $period = 'today';
                break;
        }

        return [$from, $to, $period];
    }

    /**
     * 1. Summary KPI endpoint.
     */
    public function summary(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));
        [$from, $to, $period] = $this->resolveDateRange($request);
        $user = auth()->user();
        $userId = $user?->id ?? 'guest';
        $cacheKey = "dashboard:summary:b_{$branchId}:p_{$period}:f_{$from->toDateString()}:t_{$to->toDateString()}:u_{$userId}";

        $result = $this->rememberIfProduction($cacheKey, 60, function () use ($branchId, $from, $to, $period, $user) {
            // 1. Revenue metrics from active sales invoices
            $invoiceStats = SalesInvoice::where('status', 'active')
                ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('
                COALESCE(SUM(net_total), 0) as total,
                COALESCE(SUM(paid_amount_cash), 0) as cash,
                COALESCE(SUM(payment_method_value), 0) as card,
                COUNT(*) as invoice_count,
                COUNT(DISTINCT customer_id) as customer_count,
                COALESCE(AVG(net_total), 0) as avg_ticket
            ')
            ->first();

        // Service & Product split
        $serviceRevenue = (float) SalesInvoiceDetail::whereHas('salesInvoice', function ($q) use ($from, $to, $branchId) {
            $q->where('status', 'active')
                ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
                ->when($branchId, fn ($sq) => $sq->where('branch_id', $branchId));
        })->whereNotNull('service_id')->sum('subtotal');

        $productRevenue = (float) SalesInvoiceDetail::whereHas('salesInvoice', function ($q) use ($from, $to, $branchId) {
            $q->where('status', 'active')
                ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
                ->when($branchId, fn ($sq) => $sq->where('branch_id', $branchId));
        })->whereNotNull('product_id')->sum('subtotal');

        // 2. Active expenses
        $expensesTotal = (float) Expense::where('status', 'active')
            ->whereBetween('paid_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('paid_amount');

        // Net profit
        $totalRev = (float) ($invoiceStats->total ?? 0);
        $netProfit = $totalRev - $expensesTotal;

        // 3. Refunds
        $refundStats = Refund::whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->selectRaw('COALESCE(SUM(total_refund_amount), 0) as total, COUNT(*) as count')
            ->first();

        // 4. Commissions
        $commissionsTotal = (float) SalesInvoiceDetail::whereHas('salesInvoice', function ($q) use ($from, $to, $branchId) {
            $q->where('status', 'active')
                ->whereBetween('invoice_date', [$from->toDateString(), $to->toDateString()])
                ->when($branchId, fn ($sq) => $sq->where('branch_id', $branchId));
        })->whereNotNull('service_id')->sum('commission_amount');

        // 5. Customers
        $newCustomers = Customer::whereBetween('created_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])->count();
        $totalActiveCustomers = Customer::count();

        // 6. Appointments
        $apptQuery = Appointment::whereBetween('start_date', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')]);
        if ($branchId) {
            $apptQuery->where(function ($sub) use ($branchId) {
                $sub->whereHas('provider', fn ($q) => $q->where('branch_id', $branchId))
                    ->orWhereHas('service', fn ($q) => $q->where('branch_id', $branchId));
            });
        }
        if ($user && ($user->hasRole('provider') || (! self::perUser('appointments.index') && $user->employee))) {
            $apptQuery->where('provider_id', $user->employee?->id);
        }

        $rawCounts = (clone $apptQuery)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $appointmentCounts = [
            'total' => 0,
            'requested' => (int) ($rawCounts[AppointmentStatus::REQUESTED->value] ?? $rawCounts['requested'] ?? 0),
            'confirmed' => (int) ($rawCounts[AppointmentStatus::CONFIRMED->value] ?? $rawCounts['confirmed'] ?? 0),
            'checked_in' => (int) ($rawCounts[AppointmentStatus::CHECKED_IN->value] ?? $rawCounts['checked_in'] ?? 0),
            'in_service' => (int) ($rawCounts[AppointmentStatus::IN_SERVICE->value] ?? $rawCounts['in_service'] ?? 0),
            'completed' => (int) ($rawCounts[AppointmentStatus::COMPLETED->value] ?? $rawCounts['completed'] ?? 0),
            'cancelled' => (int) ($rawCounts[AppointmentStatus::CANCELLED->value] ?? $rawCounts['cancelled'] ?? 0),
            'no_show' => (int) ($rawCounts[AppointmentStatus::NO_SHOW->value] ?? $rawCounts['no_show'] ?? 0),
        ];
        $appointmentCounts['total'] = array_sum(array_slice($appointmentCounts, 1));

        // 7. Top Employee
        $topEmployeeRow = SalesInvoiceDetail::join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_details.sales_invoice_id')
            ->join('employees', 'employees.id', '=', 'sales_invoice_details.provider_id')
            ->where('sales_invoices.status', 'active')
            ->whereBetween('sales_invoices.invoice_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('sales_invoice_details.service_id')
            ->when($branchId, fn ($q) => $q->where('sales_invoices.branch_id', $branchId))
            ->selectRaw('employees.id, employees.name, COUNT(sales_invoice_details.id) as service_count, SUM(sales_invoice_details.subtotal) as revenue')
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('revenue')
            ->first();

        // 8. Top Service
        $topServiceRow = SalesInvoiceDetail::join('sales_invoices', 'sales_invoices.id', '=', 'sales_invoice_details.sales_invoice_id')
            ->join('services', 'services.id', '=', 'sales_invoice_details.service_id')
            ->where('sales_invoices.status', 'active')
            ->whereBetween('sales_invoices.invoice_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('sales_invoice_details.service_id')
            ->when($branchId, fn ($q) => $q->where('sales_invoices.branch_id', $branchId))
            ->selectRaw('services.id, services.name, COUNT(sales_invoice_details.id) as count, SUM(sales_invoice_details.subtotal) as revenue')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('count')
            ->first();

        return [
            'data' => [
                'revenue' => [
                    'total' => round($totalRev, 2),
                    'cash' => round((float) ($invoiceStats->cash ?? 0), 2),
                    'card' => round((float) ($invoiceStats->card ?? 0), 2),
                    'services' => round($serviceRevenue, 2),
                    'products' => round($productRevenue, 2),
                    'invoice_count' => (int) ($invoiceStats->invoice_count ?? 0),
                    'customer_count' => (int) ($invoiceStats->customer_count ?? 0),
                    'avg_ticket' => round((float) ($invoiceStats->avg_ticket ?? 0), 2),
                ],
                'expenses' => [
                    'total' => round($expensesTotal, 2),
                ],
                'net_profit' => round($netProfit, 2),
                'refunds' => [
                    'total' => round((float) ($refundStats->total ?? 0), 2),
                    'count' => (int) ($refundStats->count ?? 0),
                ],
                'commissions' => [
                    'total' => round($commissionsTotal, 2),
                ],
                'customers' => [
                    'new_today' => $newCustomers,
                    'total_active' => $totalActiveCustomers,
                ],
                'appointments' => $appointmentCounts,
                'top_employee' => $topEmployeeRow ? [
                    'name' => $topEmployeeRow->name,
                    'service_count' => (int) $topEmployeeRow->service_count,
                    'revenue' => round((float) $topEmployeeRow->revenue, 2),
                ] : null,
                'top_service' => $topServiceRow ? [
                    'name' => $topServiceRow->name,
                    'count' => (int) $topServiceRow->count,
                    'revenue' => round((float) $topServiceRow->revenue, 2),
                ] : null,
            ],
            'meta' => [
                'branch_id' => $branchId,
                'period' => $period,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'generated_at' => now()->toIso8601String(),
            ],
        ];
        });

        return response()->json($result);
    }

    /**
     * 2. Revenue time series endpoint.
     */
    public function revenue(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));
        [$from, $to, $period] = $this->resolveDateRange($request);

        // If today or yesterday was requested without custom range, show 7-day trend up to date
        if (in_array($period, ['today', 'yesterday']) && ! $request->filled('from')) {
            $from = $to->copy()->subDays(6)->startOfDay();
        }

        $cacheKey = "dashboard:revenue:b_{$branchId}:f_{$from->toDateString()}:t_{$to->toDateString()}";

        $result = $this->rememberIfProduction($cacheKey, 120, function () use ($branchId, $from, $to) {
            $rows = DB::table('sales_invoice_details as sid')
                ->join('sales_invoices as si', 'sid.sales_invoice_id', '=', 'si.id')
                ->where('si.status', 'active')
                ->whereBetween('si.invoice_date', [$from->toDateString(), $to->toDateString()])
                ->when($branchId, fn ($q) => $q->where('si.branch_id', $branchId))
                ->selectRaw('
                    DATE(si.invoice_date) as day,
                    SUM(CASE WHEN sid.service_id IS NOT NULL THEN sid.subtotal ELSE 0 END) as services,
                    SUM(CASE WHEN sid.product_id IS NOT NULL THEN sid.subtotal ELSE 0 END) as products,
                    SUM(sid.subtotal) as total
                ')
                ->groupBy('day')
                ->orderBy('day')
                ->get()
                ->keyBy('day');

            $labels = [];
            $services = [];
            $products = [];
            $totals = [];

            $current = $from->copy();
            while ($current->lte($to)) {
                $dayStr = $current->toDateString();
                $labels[] = $current->format('M d');

                if (isset($rows[$dayStr])) {
                    $services[] = round((float) $rows[$dayStr]->services, 2);
                    $products[] = round((float) $rows[$dayStr]->products, 2);
                    $totals[] = round((float) $rows[$dayStr]->total, 2);
                } else {
                    $services[] = 0.0;
                    $products[] = 0.0;
                    $totals[] = 0.0;
                }

                $current->addDay();
            }

            return [
                'data' => [
                    'labels' => $labels,
                    'services' => $services,
                    'products' => $products,
                    'total' => $totals,
                ],
                'meta' => [
                    'branch_id' => $branchId,
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
            ];
        });

        return response()->json($result);
    }

    /**
     * 3. Appointments endpoint (breakdown + today's interactive list).
     */
    public function appointments(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));
        if ($request->filled('date') && ! $request->filled('period')) {
            $from = Carbon::parse($request->input('date'))->startOfDay();
            $to = $from->copy()->endOfDay();
            $period = 'custom';
        } else {
            [$from, $to, $period] = $this->resolveDateRange($request);
        }
        $user = auth()->user();

        $dayStart = $from->format('Y-m-d H:i:s');
        $dayEnd = $to->format('Y-m-d H:i:s');
        $query = Appointment::whereBetween('start_date', [$dayStart, $dayEnd]);
        if ($branchId) {
            $query->where(function ($sub) use ($branchId) {
                $sub->whereHas('provider', fn ($q) => $q->where('branch_id', $branchId))
                    ->orWhereHas('service', fn ($q) => $q->where('branch_id', $branchId));
            });
        }
        if ($user && ($user->hasRole('provider') || (! self::perUser('appointments.index') && $user->employee))) {
            $query->where('provider_id', $user->employee?->id);
        }

        $rawCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statusBreakdown = [
            'requested' => (int) ($rawCounts[AppointmentStatus::REQUESTED->value] ?? $rawCounts['requested'] ?? 0),
            'confirmed' => (int) ($rawCounts[AppointmentStatus::CONFIRMED->value] ?? $rawCounts['confirmed'] ?? 0),
            'checked_in' => (int) ($rawCounts[AppointmentStatus::CHECKED_IN->value] ?? $rawCounts['checked_in'] ?? 0),
            'in_service' => (int) ($rawCounts[AppointmentStatus::IN_SERVICE->value] ?? $rawCounts['in_service'] ?? 0),
            'completed' => (int) ($rawCounts[AppointmentStatus::COMPLETED->value] ?? $rawCounts['completed'] ?? 0),
            'cancelled' => (int) ($rawCounts[AppointmentStatus::CANCELLED->value] ?? $rawCounts['cancelled'] ?? 0),
            'no_show' => (int) ($rawCounts[AppointmentStatus::NO_SHOW->value] ?? $rawCounts['no_show'] ?? 0),
        ];

            $canEditAppts = self::perUser('appointments.edit');

            $appointments = (clone $query)
                ->with(['customer', 'provider', 'service'])
                ->orderBy('start_date', 'asc')
                ->limit(100)
                ->get()
                ->map(function (Appointment $appt) use ($canEditAppts) {
                    $statusVal = $appt->status instanceof AppointmentStatus ? $appt->status->value : (string) $appt->status;
                    $start = Carbon::parse($appt->start_date);
                    $end = $appt->end_date ? Carbon::parse($appt->end_date) : $start->copy()->addMinutes(30);

                    return [
                        'id' => $appt->id,
                        'start_time' => $start->format('H:i'),
                        'end_time' => $end->format('H:i'),
                        'customer_name' => $appt->customer?->name ?? __('Walk-in Customer'),
                        'customer_phone' => $appt->customer?->phone ?? '',
                        'service_name' => $appt->service?->name ?? __('Unknown Service'),
                        'provider_name' => $appt->provider?->name ?? __('Unassigned'),
                        'status' => $statusVal,
                        'status_label' => ucfirst(str_replace('_', ' ', $statusVal)),
                        'duration_minutes' => max(0, $start->diffInMinutes($end)),
                        'can_confirm' => $canEditAppts && $statusVal === AppointmentStatus::REQUESTED->value,
                        'can_check_in' => $canEditAppts && $statusVal === AppointmentStatus::CONFIRMED->value,
                        'can_start' => $canEditAppts && $statusVal === AppointmentStatus::CHECKED_IN->value,
                        'can_complete' => $canEditAppts && in_array($statusVal, [AppointmentStatus::CHECKED_IN->value, AppointmentStatus::IN_SERVICE->value]),
                        'can_cancel' => $canEditAppts && in_array($statusVal, [AppointmentStatus::REQUESTED->value, AppointmentStatus::CONFIRMED->value, AppointmentStatus::CHECKED_IN->value, AppointmentStatus::IN_SERVICE->value]),
                        'can_no_show' => $canEditAppts && in_array($statusVal, [AppointmentStatus::REQUESTED->value, AppointmentStatus::CONFIRMED->value, AppointmentStatus::CHECKED_IN->value]),
                    ];
                });

        return response()->json([
            'data' => [
                'status_breakdown' => $statusBreakdown,
                'appointments' => $appointments,
            ],
            'meta' => [
                'branch_id' => $branchId,
                'date' => $from->toDateString(),
                'period' => $period,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
                'total_count' => $appointments->count(),
            ],
        ]);
    }

    /**
     * 4. Staff performance endpoint.
     */
    public function staff(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));
        [$from, $to] = $this->resolveDateRange($request);

        $staff = DB::table('sales_invoice_details as sid')
            ->join('sales_invoices as si', 'sid.sales_invoice_id', '=', 'si.id')
            ->join('employees as e', 'e.id', '=', 'sid.provider_id')
            ->leftJoin('branches as b', 'b.id', '=', 'e.branch_id')
            ->where('si.status', 'active')
            ->whereBetween('si.invoice_date', [$from->toDateString(), $to->toDateString()])
            ->whereNotNull('sid.service_id')
            ->when($branchId, fn ($q) => $q->where('si.branch_id', $branchId))
            ->selectRaw('
                e.id as employee_id,
                e.name,
                COALESCE(b.name, "N/A") as branch,
                COUNT(sid.id) as service_count,
                ROUND(COALESCE(SUM(sid.subtotal), 0), 2) as revenue,
                ROUND(COALESCE(SUM(sid.commission_amount), 0), 2) as commission
            ')
            ->groupBy('e.id', 'e.name', 'b.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $staff,
            'meta' => [
                'branch_id' => $branchId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    /**
     * 5. Inventory alerts endpoint.
     */
    public function inventoryAlerts(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));

        $outOfStock = DB::table('inventory_products as ip')
            ->join('products as p', 'p.id', '=', 'ip.product_id')
            ->join('inventories as i', 'i.id', '=', 'ip.inventory_id')
            ->join('branches as b', 'b.id', '=', 'i.branch_id')
            ->leftJoin('product_categories as pc', 'pc.id', '=', 'p.category_id')
            ->where('ip.quantity', '<=', 0)
            ->when($branchId, fn ($q) => $q->where('b.id', $branchId))
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                DB::raw('COALESCE(pc.name, "Uncategorized") as category'),
                'b.name as branch',
                'ip.inventory_id',
                'ip.quantity as current_quantity'
            )
            ->limit(50)
            ->get();

        $lowStock = DB::table('inventory_products as ip')
            ->join('products as p', 'p.id', '=', 'ip.product_id')
            ->join('inventories as i', 'i.id', '=', 'ip.inventory_id')
            ->join('branches as b', 'b.id', '=', 'i.branch_id')
            ->leftJoin('product_categories as pc', 'pc.id', '=', 'p.category_id')
            ->where('ip.quantity', '>', 0)
            ->where('ip.quantity', '<=', 5)
            ->when($branchId, fn ($q) => $q->where('b.id', $branchId))
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'ip.quantity as current_quantity',
                DB::raw('5 as threshold'),
                DB::raw('COALESCE(pc.name, "Uncategorized") as category'),
                'b.name as branch',
                'ip.inventory_id'
            )
            ->limit(50)
            ->get();

        return response()->json([
            'data' => [
                'out_of_stock' => $outOfStock,
                'low_stock' => $lowStock,
                'out_of_stock_count' => $outOfStock->count(),
                'low_stock_count' => $lowStock->count(),
            ],
            'meta' => [
                'branch_id' => $branchId,
            ],
        ]);
    }

    /**
     * 6. Expenses breakdown endpoint.
     */
    public function expenses(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));
        [$from, $to] = $this->resolveDateRange($request);

        $expensesQuery = Expense::where('status', 'active')
            ->whereBetween('paid_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId));

        $total = (float) (clone $expensesQuery)->sum('paid_amount');

        $byCategory = DB::table('expenses as exp')
            ->leftJoin('expense_types as et', 'et.id', '=', 'exp.expense_type_id')
            ->where('exp.status', 'active')
            ->whereBetween('exp.paid_at', [$from->format('Y-m-d H:i:s'), $to->format('Y-m-d H:i:s')])
            ->when($branchId, fn ($q) => $q->where('exp.branch_id', $branchId))
            ->selectRaw('COALESCE(et.name, "Other") as category, SUM(exp.paid_amount) as total')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get()
            ->map(function ($item) use ($total) {
                $itemTotal = round((float) $item->total, 2);
                $pct = $total > 0 ? round(($itemTotal / $total) * 100, 1) : 0;

                return [
                    'category' => $item->category,
                    'total' => $itemTotal,
                    'percentage' => $pct,
                ];
            });

        return response()->json([
            'data' => [
                'total' => round($total, 2),
                'by_category' => $byCategory,
            ],
            'meta' => [
                'branch_id' => $branchId,
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }

    /**
     * 7. Activity feed endpoint.
     */
    public function activity(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));
        $limit = max(5, min(50, (int) $request->input('limit', 15)));

        // Invoices created
        $invoices = SalesInvoice::where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['customer'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->map(fn ($i) => [
                'type' => 'invoice_created',
                'title' => __('New Invoice Created'),
                'description' => __('Invoice #:id — :amount EGP — :customer', [
                    'id' => $i->id,
                    'amount' => number_format((float) $i->net_total, 2),
                    'customer' => $i->customer?->name ?? __('Walk-in'),
                ]),
                'url' => route('sales_invoices.show', $i->id),
                'timestamp' => $i->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'time_ago' => $i->created_at?->diffForHumans() ?? '',
            ]);

        // Voided invoices
        $voids = SalesInvoice::where('status', 'voided')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with(['customer'])
            ->whereNotNull('voided_at')
            ->latest('voided_at')
            ->limit(5)
            ->get()
            ->map(fn ($i) => [
                'type' => 'invoice_voided',
                'title' => __('Invoice Voided'),
                'description' => __('Invoice #:id voided — :customer', [
                    'id' => $i->id,
                    'customer' => $i->customer?->name ?? __('Walk-in'),
                ]),
                'url' => route('sales_invoices.show', $i->id),
                'timestamp' => Carbon::parse($i->voided_at)->toIso8601String(),
                'time_ago' => Carbon::parse($i->voided_at)->diffForHumans(),
            ]);

        // Completed appointments
        $completedAppts = Appointment::where('status', AppointmentStatus::COMPLETED->value)
            ->when($branchId, function ($q) use ($branchId) {
                $q->where(function ($sub) use ($branchId) {
                    $sub->whereHas('provider', fn ($p) => $p->where('branch_id', $branchId))
                        ->orWhereHas('service', fn ($s) => $s->where('branch_id', $branchId));
                });
            })
            ->with(['customer', 'provider', 'service'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
            ->map(fn ($a) => [
                'type' => 'appointment_completed',
                'title' => __('Appointment Completed'),
                'description' => __(':customer — :service with :provider', [
                    'customer' => $a->customer?->name ?? __('Customer'),
                    'service' => $a->service?->name ?? __('Service'),
                    'provider' => $a->provider?->name ?? __('Staff'),
                ]),
                'url' => route('appointments.show', $a->id),
                'timestamp' => $a->updated_at?->toIso8601String() ?? now()->toIso8601String(),
                'time_ago' => $a->updated_at?->diffForHumans() ?? '',
            ]);

        // Refunds
        $refunds = Refund::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($r) => [
                'type' => 'refund_issued',
                'title' => __('Refund Processed'),
                'description' => __('Refund #:number — :amount EGP', [
                    'number' => $r->refund_number,
                    'amount' => number_format((float) $r->total_refund_amount, 2),
                ]),
                'url' => route('refunds.show', $r->id),
                'timestamp' => $r->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'time_ago' => $r->created_at?->diffForHumans() ?? '',
            ]);

        // New customers
        $customers = Customer::latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'type' => 'customer_registered',
                'title' => __('New Customer Registered'),
                'description' => __(':name joined the system', ['name' => $c->name]),
                'url' => route('customers.show', $c->id),
                'timestamp' => $c->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'time_ago' => $c->created_at?->diffForHumans() ?? '',
            ]);

        // Stock adjustments
        $adjustments = InventoryTransaction::where('transaction_type', 'adjustment')
            ->when($branchId, fn ($q) => $q->whereHas('sourceInventory', fn ($sq) => $sq->where('branch_id', $branchId)))
            ->with(['createdBy'])
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->map(fn ($t) => [
                'type' => 'stock_adjusted',
                'title' => __('Stock Adjustment'),
                'description' => __('Adjustment (:type) by :user', [
                    'type' => $t->adjustment_type ?? __('Manual'),
                    'user' => $t->createdBy?->name ?? __('System'),
                ]),
                'url' => route('inventory_transactions.history'),
                'timestamp' => $t->created_at?->toIso8601String() ?? now()->toIso8601String(),
                'time_ago' => $t->created_at?->diffForHumans() ?? '',
            ]);

        $feed = $invoices->merge($voids)
            ->merge($completedAppts)
            ->merge($refunds)
            ->merge($customers)
            ->merge($adjustments)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $feed,
            'meta' => [
                'branch_id' => $branchId,
                'count' => $feed->count(),
            ],
        ]);
    }

    /**
     * 8. Operational alerts endpoint.
     */
    public function alerts(Request $request): JsonResponse
    {
        $branchId = $this->getEffectiveBranchId($request->input('branch_id'));
        $alerts = [];

        // 1. Unconfirmed upcoming appointments
        $unconfirmedQuery = Appointment::where('status', AppointmentStatus::REQUESTED->value)
            ->where('start_date', '>=', today()->startOfDay()->format('Y-m-d H:i:s'));
        if ($branchId) {
            $unconfirmedQuery->where(function ($sub) use ($branchId) {
                $sub->whereHas('provider', fn ($q) => $q->where('branch_id', $branchId))
                    ->orWhereHas('service', fn ($q) => $q->where('branch_id', $branchId));
            });
        }
        $unconfirmedCount = $unconfirmedQuery->count();
        if ($unconfirmedCount > 0) {
            $alerts[] = [
                'id' => 'alert_unconfirmed_appts',
                'type' => 'unconfirmed_appointments',
                'severity' => 'warning',
                'title' => __(':count appointments awaiting confirmation', ['count' => $unconfirmedCount]),
                'body' => __('Pending requests require staff confirmation before clients arrive.'),
                'count' => $unconfirmedCount,
                'action_label' => __('Review Appointments'),
                'action_url' => route('appointments.index'),
            ];
        }

        // 2. Out of stock inventory items
        $outOfStockCount = DB::table('inventory_products as ip')
            ->join('inventories as i', 'i.id', '=', 'ip.inventory_id')
            ->where('ip.quantity', '<=', 0)
            ->when($branchId, fn ($q) => $q->where('i.branch_id', $branchId))
            ->count();
        if ($outOfStockCount > 0) {
            $alerts[] = [
                'id' => 'alert_out_of_stock',
                'type' => 'out_of_stock',
                'severity' => 'critical',
                'title' => __(':count products currently out of stock', ['count' => $outOfStockCount]),
                'body' => __('Products with zero inventory cannot be used for service or retail.'),
                'count' => $outOfStockCount,
                'action_label' => __('View Inventory'),
                'action_url' => route('inventories.index'),
            ];
        }

        // 3. Low stock inventory items
        $lowStockCount = DB::table('inventory_products as ip')
            ->join('inventories as i', 'i.id', '=', 'ip.inventory_id')
            ->where('ip.quantity', '>', 0)
            ->where('ip.quantity', '<=', 5)
            ->when($branchId, fn ($q) => $q->where('i.branch_id', $branchId))
            ->count();
        if ($lowStockCount > 0) {
            $alerts[] = [
                'id' => 'alert_low_stock',
                'type' => 'low_stock',
                'severity' => 'warning',
                'title' => __(':count products running low on stock', ['count' => $lowStockCount]),
                'body' => __('Stock is at 5 units or below. Restocking recommended.'),
                'count' => $lowStockCount,
                'action_label' => __('Adjust or Restock'),
                'action_url' => route('inventory_transactions.history'),
            ];
        }

        // 4. Overdue draft invoices (> 2 hours old)
        $oldDraftsCount = SalesInvoice::where('status', 'draft')
            ->where('created_at', '<=', now()->subHours(2))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();
        if ($oldDraftsCount > 0) {
            $alerts[] = [
                'id' => 'alert_old_draft_invoices',
                'type' => 'draft_invoices',
                'severity' => 'warning',
                'title' => __(':count draft invoices pending activation for >2h', ['count' => $oldDraftsCount]),
                'body' => __('Draft invoices hold tentative sales and should be activated or removed.'),
                'count' => $oldDraftsCount,
                'action_label' => __('View Drafts'),
                'action_url' => route('sales_invoices.index'),
            ];
        }

        // 5. Invoices with unpaid balances
        $unpaidBalanceInvoices = SalesInvoice::where('status', 'active')
            ->where('balance_due', '>', 0)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();
        if ($unpaidBalanceInvoices > 0) {
            $alerts[] = [
                'id' => 'alert_unpaid_balances',
                'type' => 'unpaid_balance',
                'severity' => 'info',
                'title' => __(':count invoices with outstanding balance due', ['count' => $unpaidBalanceInvoices]),
                'body' => __('Active customer invoices have pending remaining payments.'),
                'count' => $unpaidBalanceInvoices,
                'action_label' => __('Check Invoices'),
                'action_url' => route('sales_invoices.index'),
            ];
        }

        // Sort alerts: critical first, then warning, then info
        $severityOrder = ['critical' => 1, 'warning' => 2, 'info' => 3];
        usort($alerts, fn ($a, $b) => ($severityOrder[$a['severity']] ?? 9) <=> ($severityOrder[$b['severity']] ?? 9));

        return response()->json([
            'data' => $alerts,
            'meta' => [
                'branch_id' => $branchId,
                'count' => count($alerts),
            ],
        ]);
    }
}
