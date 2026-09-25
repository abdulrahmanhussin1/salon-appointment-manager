<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminPanelSetting;
use App\Models\CustomerTransaction;
use App\Models\Inventory;
use App\Models\InventoryProduct;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionDetail;
use App\Models\Refund;
use App\Models\RefundDetail;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceDetail;
use App\Traits\HasBranchFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RealRashid\SweetAlert\Facades\Alert;
use Yajra\DataTables\DataTables;

class RefundController extends Controller
{
    use HasBranchFilter;

    /**
     * Display a listing of refunds with DataTables support.
     */
    public function index(Request $request)
    {
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        if ($request->ajax() || $request->wantsJson()) {
            $query = Refund::with(['customer', 'branch', 'createdBy', 'salesInvoice'])
                ->when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
                ->when($request->filled('start_date'), fn ($q) => $q->whereDate('refund_date', '>=', $request->start_date))
                ->when($request->filled('end_date'), fn ($q) => $q->whereDate('refund_date', '<=', $request->end_date))
                ->when($request->filled('refund_method'), fn ($q) => $q->where('refund_method', $request->refund_method))
                ->orderBy('id', 'desc');

            return DataTables::of($query)
                ->addColumn('invoice_link', function ($row) {
                    $url = route('sales_invoices.invoice', $row->sales_invoice_id);

                    return '<a href="'.$url.'" class="fw-bold text-primary">#'.$row->sales_invoice_id.'</a>';
                })
                ->addColumn('customer_name', fn ($row) => $row->customer?->name ?? '-')
                ->addColumn('branch_name', fn ($row) => $row->branch?->name ?? '-')
                ->addColumn('method_badge', function ($row) {
                    $color = match ($row->refund_method) {
                        'cash' => 'success',
                        'deposit' => 'info',
                        'card' => 'primary',
                        default => 'secondary',
                    };

                    return '<span class="badge bg-'.$color.'">'.e($row->refund_method_label).'</span>';
                })
                ->editColumn('total_refund_amount', fn ($row) => '$'.number_format($row->total_refund_amount, 2))
                ->editColumn('commission_reversed_amount', fn ($row) => '$'.number_format($row->commission_reversed_amount, 2))
                ->editColumn('refund_date', fn ($row) => $row->refund_date?->format('Y-m-d') ?? '-')
                ->addColumn('processed_by', fn ($row) => $row->createdBy?->name ?? '-')
                ->addColumn('action', function ($row) {
                    $showUrl = route('refunds.show', $row->id);

                    return '<a href="'.$showUrl.'" class="btn btn-sm btn-outline-info" title="'.__('View Receipt').'"><i class="bi bi-receipt me-1"></i>'.__('Receipt').'</a>';
                })
                ->rawColumns(['invoice_link', 'method_badge', 'action'])
                ->make(true);
        }

        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();

        return view('admin.pages.refunds.index', compact('branches', 'canSelectAll', 'effectiveBranchId'));
    }

    /**
     * Show the form for creating a new refund.
     */
    public function create(Request $request)
    {
        $invoiceId = $request->input('invoice_id');
        $invoice = null;
        $effectiveBranchId = $this->getEffectiveBranchId();

        if ($invoiceId) {
            $invoice = SalesInvoice::with([
                'customer',
                'branch',
                'salesInvoiceDetails.service',
                'salesInvoiceDetails.product',
                'salesInvoiceDetails.provider',
            ])->find($invoiceId);

            if (! $invoice) {
                Alert::error(__('Error'), __('Invoice not found.'));

                return redirect()->route('sales_invoices.index');
            }

            // Enforce cashier branch scoping
            if ($effectiveBranchId && (int) $invoice->branch_id !== (int) $effectiveBranchId) {
                abort(403, __('Unauthorized: You cannot process refunds for invoices belonging to another branch.'));
            }

            if ($invoice->status !== 'active') {
                Alert::error(__('Error'), __('Only active invoices can be refunded. Draft and voided invoices cannot be refunded.'));

                return redirect()->route('sales_invoices.invoice', $invoice->id);
            }

            if ($invoice->refund_status === 'full' || $invoice->remainingRefundableAmount() <= 0) {
                Alert::warning(__('Notice'), __('This invoice has already been fully refunded.'));

                return redirect()->route('sales_invoices.invoice', $invoice->id);
            }
        }

        $branches = $this->getAvailableBranches();
        $canSelectAll = $this->canAccessAllBranches();

        // Inventories for destination of returned products
        $inventories = Inventory::when($effectiveBranchId, fn ($q) => $q->where('branch_id', $effectiveBranchId))
            ->where('status', 'active')
            ->get();

        return view('admin.pages.refunds.create', compact('invoice', 'branches', 'canSelectAll', 'effectiveBranchId', 'inventories'));
    }

    /**
     * Fetch invoice details and refundable line items for interactive refund form.
     */
    public function getInvoiceDetails(Request $request, $id)
    {
        $effectiveBranchId = $this->getEffectiveBranchId();

        $invoice = SalesInvoice::with([
            'customer',
            'branch',
            'salesInvoiceDetails.service',
            'salesInvoiceDetails.product',
            'salesInvoiceDetails.provider',
        ])->findOrFail($id);

        // Enforce cashier branch scoping
        if ($effectiveBranchId && (int) $invoice->branch_id !== (int) $effectiveBranchId) {
            return response()->json([
                'error' => __('Unauthorized: You cannot access invoices belonging to another branch.'),
            ], 403);
        }

        if ($invoice->status !== 'active') {
            return response()->json([
                'error' => __('Only active invoices can be refunded.'),
            ], 422);
        }

        $items = $invoice->salesInvoiceDetails->map(function ($detail) {
            $unitPrice = (float) $detail->customer_price;
            $discount = (float) $detail->discount;
            $netUnitPrice = $unitPrice * (1 - ($discount / 100));
            $remainingQty = $detail->remainingRefundableQuantity();

            return [
                'id' => $detail->id,
                'type' => $detail->service_id ? 'service' : 'product',
                'name' => $detail->name(),
                'provider_name' => $detail->provider?->name ?? '-',
                'original_quantity' => (int) $detail->quantity,
                'refunded_quantity' => (int) ($detail->refunded_quantity ?? 0),
                'remaining_quantity' => $remainingQty,
                'customer_price' => $unitPrice,
                'discount' => $discount,
                'net_unit_price' => round($netUnitPrice, 2),
                'commission_amount' => (float) ($detail->commission_amount ?? 0),
                'is_fully_refunded' => $remainingQty <= 0,
            ];
        });

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_date' => $invoice->invoice_date,
                'customer_name' => $invoice->customer?->name ?? 'Walk-in',
                'customer_id' => $invoice->customer_id,
                'branch_name' => $invoice->branch?->name ?? '-',
                'branch_id' => $invoice->branch_id,
                'net_total' => (float) $invoice->net_total,
                'total_refunded' => (float) ($invoice->total_refunded ?? 0),
                'remaining_refundable_amount' => $invoice->remainingRefundableAmount(),
                'refund_status' => $invoice->refund_status ?? 'none',
            ],
            'items' => $items,
        ]);
    }

    /**
     * Store a newly created refund in storage with atomic transaction.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sales_invoice_id' => 'required|exists:sales_invoices,id',
            'refund_date' => 'required|date',
            'refund_method' => 'required|in:cash,deposit,card,bank_transfer,other',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sales_invoice_detail_id' => 'required|exists:sales_invoice_details,id',
            'items.*.quantity' => 'required|integer|min:0',
            'items.*.inventory_id' => 'nullable|exists:inventories,id',
        ]);

        $effectiveBranchId = $this->getEffectiveBranchId();

        try {
            $refund = DB::transaction(function () use ($request, $effectiveBranchId) {
                // 1. Lock invoice for update
                $invoice = SalesInvoice::where('id', $request->sales_invoice_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Check branch scoping
                if ($effectiveBranchId && (int) $invoice->branch_id !== (int) $effectiveBranchId) {
                    throw new \Exception(__('Unauthorized: Cashiers can only refund invoices for their own branch.'));
                }

                if ($invoice->status !== 'active') {
                    throw new \Exception(__('Only active invoices can be refunded. Draft or voided invoices cannot be refunded.'));
                }

                if ($invoice->refund_status === 'full') {
                    throw new \Exception(__('This invoice is already fully refunded.'));
                }

                $totalRefundAmount = 0.0;
                $totalTaxRefund = 0.0;
                $totalCommissionReversed = 0.0;
                $processedDetails = [];
                $inventoryRestorations = [];

                // 2. Validate and calculate each requested line item
                foreach ($request->items as $itemInput) {
                    $requestedQty = (int) ($itemInput['quantity'] ?? 0);
                    if ($requestedQty <= 0) {
                        continue;
                    }

                    $detail = SalesInvoiceDetail::where('id', $itemInput['sales_invoice_detail_id'])
                        ->where('sales_invoice_id', $invoice->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $remainingQty = $detail->remainingRefundableQuantity();
                    if ($requestedQty > $remainingQty) {
                        throw new \Exception(__('Cannot refund :qty units of :item. Only :rem units remain refundable.', [
                            'qty' => $requestedQty,
                            'item' => $detail->name(),
                            'rem' => $remainingQty,
                        ]));
                    }

                    $unitPrice = (float) $detail->customer_price;
                    $discountPercent = (float) $detail->discount;
                    $netUnitPrice = $unitPrice * (1 - ($discountPercent / 100));
                    $lineRefundSubtotal = round($netUnitPrice * $requestedQty, 2);

                    // Reversible commission calculation (for services)
                    $commissionReversed = 0.0;
                    if ($detail->service_id && (float) $detail->commission_amount > 0 && (int) $detail->quantity > 0) {
                        $commissionPerUnit = (float) $detail->commission_amount / (int) $detail->quantity;
                        $commissionReversed = round($commissionPerUnit * $requestedQty, 2);
                        $commissionReversed = min($commissionReversed, (float) $detail->commission_amount);

                        // Deduct reversed commission from line item
                        $detail->commission_amount = max(0.0, (float) $detail->commission_amount - $commissionReversed);
                    }

                    // Product physical inventory restoration setup
                    $inventoryRestored = false;
                    $inventoryId = null;
                    if ($detail->product_id) {
                        $inventoryId = $itemInput['inventory_id'] ?? null;
                        if (! $inventoryId) {
                            $defaultInv = Inventory::where('branch_id', $invoice->branch_id)->first();
                            $inventoryId = $defaultInv?->id;
                        }

                        if ($inventoryId) {
                            $inventoryRestored = true;
                            $inventoryRestorations[] = [
                                'inventory_id' => $inventoryId,
                                'product_id' => $detail->product_id,
                                'quantity' => $requestedQty,
                                'subtotal' => $lineRefundSubtotal,
                            ];
                        }
                    }

                    // Update detail audit counters
                    $detail->refunded_quantity = (int) ($detail->refunded_quantity ?? 0) + $requestedQty;
                    $detail->refunded_amount = (float) ($detail->refunded_amount ?? 0) + $lineRefundSubtotal;
                    $detail->save();

                    $totalRefundAmount += $lineRefundSubtotal;
                    $totalCommissionReversed += $commissionReversed;

                    $processedDetails[] = [
                        'sales_invoice_detail_id' => $detail->id,
                        'service_id' => $detail->service_id,
                        'product_id' => $detail->product_id,
                        'provider_id' => $detail->provider_id,
                        'quantity' => $requestedQty,
                        'unit_price' => $unitPrice,
                        'discount' => $discountPercent,
                        'tax' => (float) $detail->tax,
                        'subtotal' => $lineRefundSubtotal,
                        'commission_reversed' => $commissionReversed,
                        'inventory_restored' => $inventoryRestored,
                        'inventory_id' => $inventoryId,
                        'created_by' => auth()->id(),
                    ];
                }

                if (empty($processedDetails) || $totalRefundAmount <= 0) {
                    throw new \Exception(__('Please specify a refund quantity greater than 0 for at least one item.'));
                }

                // 3. Create Refund master record
                $refund = Refund::create([
                    'refund_number' => Refund::generateRefundNumber(),
                    'sales_invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'branch_id' => $invoice->branch_id,
                    'refund_date' => $request->refund_date,
                    'refund_method' => $request->refund_method,
                    'total_refund_amount' => $totalRefundAmount,
                    'tax_refund_amount' => $totalTaxRefund,
                    'commission_reversed_amount' => $totalCommissionReversed,
                    'reason' => $request->reason,
                    'notes' => $request->notes,
                    'created_by' => auth()->id(),
                ]);

                // 4. Create RefundDetails
                foreach ($processedDetails as $detailData) {
                    $detailData['refund_id'] = $refund->id;
                    RefundDetail::create($detailData);
                }

                // 5. Restore physical inventory and record sales_return transaction
                foreach ($inventoryRestorations as $restoration) {
                    $invProduct = InventoryProduct::where('inventory_id', $restoration['inventory_id'])
                        ->where('product_id', $restoration['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($invProduct) {
                        $invProduct->quantity += $restoration['quantity'];
                        $invProduct->save();
                    } else {
                        InventoryProduct::create([
                            'inventory_id' => $restoration['inventory_id'],
                            'product_id' => $restoration['product_id'],
                            'quantity' => $restoration['quantity'],
                        ]);
                    }

                    $invTransaction = InventoryTransaction::create([
                        'transaction_type' => 'sales_return',
                        'destination_inventory_id' => $restoration['inventory_id'],
                        'total_before_discount' => $restoration['subtotal'],
                        'net_total' => $restoration['subtotal'],
                    ]);

                    InventoryTransactionDetail::create([
                        'inventory_transaction_id' => $invTransaction->id,
                        'product_id' => $restoration['product_id'],
                        'quantity' => $restoration['quantity'],
                    ]);
                }

                // 6. Handle Customer Deposit credit if chosen
                if ($request->refund_method === 'deposit') {
                    CustomerTransaction::create([
                        'customer_id' => $invoice->customer_id,
                        'reference_type' => Refund::class,
                        'reference_id' => $refund->id,
                        'amount' => $totalRefundAmount,
                        'notes' => __('Refund :ref for Invoice #:inv', [
                            'ref' => $refund->refund_number,
                            'inv' => $invoice->id,
                        ]),
                        'status' => 'available',
                        'created_by' => auth()->id(),
                    ]);
                }

                // 7. Update SalesInvoice refund status and total refunded
                $invoice->total_refunded = (float) ($invoice->total_refunded ?? 0) + $totalRefundAmount;

                $remainingLinesCount = $invoice->salesInvoiceDetails()
                    ->whereRaw('refunded_quantity < quantity')
                    ->count();

                if ($remainingLinesCount === 0 || $invoice->total_refunded >= (float) $invoice->net_total) {
                    $invoice->refund_status = 'full';
                } else {
                    $invoice->refund_status = 'partial';
                }
                $invoice->save();

                return $refund;
            });

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('Refund processed successfully.'),
                    'refund_id' => $refund->id,
                    'redirect_url' => route('refunds.show', $refund->id),
                ]);
            }

            Alert::success(__('Success'), __('Refund processed successfully.'));

            return redirect()->route('refunds.show', $refund->id);
        } catch (\Exception $e) {
            Log::error('Refund creation failed: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            Alert::error(__('Error'), $e->getMessage());

            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified refund voucher / receipt.
     */
    public function show(Refund $refund)
    {
        $effectiveBranchId = $this->getEffectiveBranchId();
        if ($effectiveBranchId && (int) $refund->branch_id !== (int) $effectiveBranchId) {
            abort(403, __('Unauthorized: You cannot view refunds for another branch.'));
        }

        $refund->load([
            'salesInvoice',
            'customer',
            'branch',
            'createdBy',
            'refundDetails.service',
            'refundDetails.product',
            'refundDetails.provider',
        ]);

        $adminPanelSetting = AdminPanelSetting::first();

        return view('admin.pages.refunds.show', compact('refund', 'adminPanelSetting'));
    }
}
