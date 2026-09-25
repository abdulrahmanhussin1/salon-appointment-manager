@extends('admin.layouts.app')
@section('title')
    {{ __('Issue Refund / Product Return') }}
@endsection

@section('css')
    <style>
        .invoice-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid #0d6efd;
        }
        .refund-summary-card {
            background: #fdfdfe;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .item-row:hover {
            background-color: #f8f9fc;
        }
    </style>
@endsection

@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Issue Refund">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('sales_invoices.index') }}">{{ __('Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('refunds.index') }}">{{ __('Refunds') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Issue Refund') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="row">
            <div class="col-12 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="mb-0 text-danger"><i class="bi bi-arrow-return-left me-2"></i>{{ __('Issue Refund / Product Return') }}</h4>
                        <p class="text-muted small mb-0">{{ __('Process partial or full returns, restock inventory, and reverse employee commissions') }}</p>
                    </div>
                    <a href="{{ route('refunds.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left me-1"></i> {{ __('Back to Refunds') }}
                    </a>
                </div>
            </div>
        </div>

        @include('admin.layouts.alerts')

        {{-- Invoice Lookup if not preloaded --}}
        @if (!$invoice)
            <div class="card shadow-sm border-0 mb-4" id="lookupCard">
                <div class="card-body py-4">
                    <h5 class="card-title mb-3"><i class="bi bi-search me-2"></i>{{ __('Lookup Sales Invoice') }}</h5>
                    <div class="row g-3 align-items-center">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">{{ __('Enter Invoice #') }}</label>
                            <input type="number" id="invoiceIdInput" class="form-control" placeholder="{{ __('e.g. 102') }}" min="1">
                        </div>
                        <div class="col-md-2 mt-md-4">
                            <button type="button" id="btnLookupInvoice" class="btn btn-primary w-100">
                                <i class="bi bi-search me-1"></i> {{ __('Load') }}
                            </button>
                        </div>
                        <div class="col-md-6 mt-md-4">
                            <span id="lookupStatus" class="text-muted small"></span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Main Refund Form --}}
        <form id="refundForm" method="POST" action="{{ route('refunds.store') }}" style="{{ !$invoice ? 'display: none;' : '' }}">
            @csrf
            <input type="hidden" name="sales_invoice_id" id="hiddenInvoiceId" value="{{ $invoice?->id ?? '' }}">

            {{-- Invoice Overview Card --}}
            <div class="card invoice-card shadow-sm border-0 mb-4">
                <div class="card-body p-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <span class="text-muted small d-block">{{ __('Invoice Number') }}</span>
                            <span class="fw-bold fs-6" id="dispInvoiceId">#{{ $invoice?->id ?? '-' }}</span>
                            @if ($invoice)
                                <a href="{{ route('sales_invoices.invoice', $invoice->id) }}" target="_blank" class="ms-1 small text-primary" title="{{ __('View Invoice') }}">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">{{ __('Customer') }}</span>
                            <span class="fw-bold" id="dispCustomer">{{ $invoice?->customer?->name ?? __('Walk-in') }}</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">{{ __('Branch') }}</span>
                            <span class="fw-bold" id="dispBranch">{{ $invoice?->branch?->name ?? '-' }}</span>
                        </div>
                        <div class="col-md-3">
                            <span class="text-muted small d-block">{{ __('Net Total / Remaining') }}</span>
                            <span class="fw-bold text-success" id="dispNetTotal">${{ number_format($invoice?->net_total ?? 0, 2) }}</span>
                            <span class="text-muted small"> / </span>
                            <span class="fw-bold text-primary" id="dispRemaining">${{ number_format($invoice?->remainingRefundableAmount() ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                {{-- Left: Items Selection Table --}}
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-list-check me-2"></i>{{ __('Refundable Line Items') }}</h6>
                            <button type="button" id="btnRefundAll" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-arrow-repeat me-1"></i> {{ __('Refund All Max') }}
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="itemsTable">
                                    <thead class="table-light fs--1">
                                        <tr>
                                            <th>{{ __('Item') }}</th>
                                            <th class="text-center">{{ __('Original') }}</th>
                                            <th class="text-center">{{ __('Refunded') }}</th>
                                            <th class="text-center">{{ __('Available') }}</th>
                                            <th class="text-end">{{ __('Price') }}</th>
                                            <th style="width: 130px;" class="text-center">{{ __('Refund Qty') }}</th>
                                            <th class="text-end">{{ __('Refund Total') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody">
                                        @if ($invoice)
                                            @foreach ($invoice->salesInvoiceDetails as $idx => $detail)
                                                @php
                                                    $remQty = $detail->remainingRefundableQuantity();
                                                    $isService = !empty($detail->service_id);
                                                    $netPrice = (float) $detail->customer_price * (1 - ((float) $detail->discount / 100));
                                                @endphp
                                                <tr class="item-row {{ $remQty <= 0 ? 'table-light text-muted' : '' }}" data-idx="{{ $idx }}">
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if ($isService)
                                                                <span class="badge bg-info-subtle text-info me-2 p-2"><i class="bi bi-scissors"></i></span>
                                                            @else
                                                                <span class="badge bg-success-subtle text-success me-2 p-2"><i class="bi bi-box-seam"></i></span>
                                                            @endif
                                                            <div>
                                                                <span class="fw-bold d-block">{{ $detail->name() }}</span>
                                                                @if ($isService && $detail->provider)
                                                                    <span class="text-muted small"><i class="bi bi-person me-1"></i>{{ $detail->provider->name }}</span>
                                                                @endif
                                                                @if (!$isService && $remQty > 0)
                                                                    <div class="mt-1">
                                                                        <select name="items[{{ $idx }}][inventory_id]" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">
                                                                            @foreach ($inventories as $inv)
                                                                                <option value="{{ $inv->id }}">{{ __('Restock to:') }} {{ $inv->name }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <input type="hidden" name="items[{{ $idx }}][sales_invoice_detail_id]" value="{{ $detail->id }}">
                                                    </td>
                                                    <td class="text-center">{{ $detail->quantity }}</td>
                                                    <td class="text-center text-muted">{{ $detail->refunded_quantity ?? 0 }}</td>
                                                    <td class="text-center fw-bold {{ $remQty > 0 ? 'text-primary' : 'text-muted' }}">
                                                        {{ $remQty }}
                                                    </td>
                                                    <td class="text-end">
                                                        ${{ number_format($netPrice, 2) }}
                                                        @if ($detail->discount > 0)
                                                            <div class="text-muted small">-{{ $detail->discount }}%</div>
                                                        @endif
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($remQty > 0)
                                                            <input type="number"
                                                                name="items[{{ $idx }}][quantity]"
                                                                class="form-control form-control-sm text-center item-qty-input"
                                                                value="0"
                                                                min="0"
                                                                max="{{ $remQty }}"
                                                                data-price="{{ $netPrice }}"
                                                                data-is-service="{{ $isService ? '1' : '0' }}"
                                                                data-commission-unit="{{ $detail->quantity > 0 ? ($detail->commission_amount / $detail->quantity) : 0 }}"
                                                                data-max="{{ $remQty }}">
                                                        @else
                                                            <span class="badge bg-secondary">{{ __('Fully Refunded') }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end fw-bold line-refund-total">
                                                        __('$') 0.00
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Refund Settings & Summary --}}
                <div class="col-lg-4">
                    <div class="card refund-summary-card shadow-sm mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-gear me-2"></i>{{ __('Refund Parameters') }}</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">{{ __('Refund Date') }} <span class="text-danger">*</span></label>
                                <input type="date" name="refund_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">{{ __('Refund Method') }} <span class="text-danger">*</span></label>
                                <select name="refund_method" id="selectRefundMethod" class="form-select form-select-sm" required>
                                    <option value="cash">{{ __('Cash (Paid Out)') }}</option>
                                    <option value="deposit">{{ __('Customer Deposit Credit (Account Balance)') }}</option>
                                    <option value="card">{{ __('Original Card / Terminal Reversal') }}</option>
                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                    <option value="other">{{ __('Other') }}</option>
                                </select>
                                <div id="depositNotice" class="alert alert-info py-2 px-3 mt-2 small" style="display: none;">
                                    <i class="bi bi-info-circle me-1"></i> {{ __('Amount will be immediately credited to the customer deposit account.') }}
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">{{ __('Reason Code') }} <span class="text-danger">*</span></label>
                                <select name="reason" class="form-select form-select-sm" required>
                                    <option value="Customer Dissatisfaction">{{ __('Customer Dissatisfaction') }}</option>
                                    <option value="Damaged / Defective Product">{{ __('Damaged / Defective Product') }}</option>
                                    <option value="Service Cancellation">{{ __('Service Cancellation') }}</option>
                                    <option value="Wrong Item Sold">{{ __('Wrong Item Sold') }}</option>
                                    <option value="Pricing / Billing Adjustment">{{ __('Pricing / Billing Adjustment') }}</option>
                                    <option value="Other">{{ __('Other') }}</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">{{ __('Audit Notes') }}</label>
                                <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="{{ __('Optional details for audit log...') }}"></textarea>
                            </div>

                            <hr class="my-3">

                            {{-- Summary Block --}}
                            <div class="bg-light p-3 rounded mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">{{ __('Total Refund Amount:') }}</span>
                                    <span class="fw-bold fs-6 text-danger" id="summaryTotalRefund">__('$') 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-muted small">{{ __('Commission Reversed:') }}</span>
                                    <span class="fw-bold text-dark" id="summaryCommissionReversed">__('$') 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted small">{{ __('Restocked Products:') }}</span>
                                    <span class="fw-bold text-success" id="summaryRestockCount">0 {{ __('units') }}</span>
                                </div>
                            </div>

                            <button type="submit" id="btnSubmitRefund" class="btn btn-danger w-100 py-2 fw-bold" disabled>
                                <i class="bi bi-check-circle me-1"></i> {{ __('Process Refund') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            var inventories = @json($inventories ?? []);

            function recalculateTotals() {
                var totalRefund = 0.0;
                var totalCommReversed = 0.0;
                var totalRestock = 0;

                $('.item-qty-input').each(function() {
                    var input = $(this);
                    var qty = parseInt(input.val()) || 0;
                    var max = parseInt(input.attr('data-max')) || 0;

                    if (qty < 0) qty = 0;
                    if (qty > max) qty = max;
                    input.val(qty);

                    var price = parseFloat(input.attr('data-price')) || 0;
                    var isService = input.attr('data-is-service') === '1';
                    var commUnit = parseFloat(input.attr('data-commission-unit')) || 0;

                    var lineTotal = qty * price;
                    input.closest('tr').find('.line-refund-total').text('$' + lineTotal.toFixed(2));

                    totalRefund += lineTotal;
                    if (isService) {
                        totalCommReversed += qty * commUnit;
                    } else {
                        totalRestock += qty;
                    }
                });

                $('#summaryTotalRefund').text('$' + totalRefund.toFixed(2));
                $('#summaryCommissionReversed').text('$' + totalCommReversed.toFixed(2));
                $('#summaryRestockCount').text(totalRestock + " {{ __('units') }}");

                if (totalRefund > 0) {
                    $('#btnSubmitRefund').removeAttr('disabled');
                } else {
                    $('#btnSubmitRefund').attr('disabled', 'disabled');
                }
            }

            $(document).on('input change', '.item-qty-input', function() {
                recalculateTotals();
            });

            $('#selectRefundMethod').on('change', function() {
                if ($(this).val() === 'deposit') {
                    $('#depositNotice').slideDown();
                } else {
                    $('#depositNotice').slideUp();
                }
            });

            $('#btnRefundAll').on('click', function() {
                $('.item-qty-input').each(function() {
                    var max = parseInt($(this).attr('data-max')) || 0;
                    $(this).val(max);
                });
                recalculateTotals();
            });

            // Invoice Lookup Handler
            $('#btnLookupInvoice').on('click', function() {
                var invoiceId = $('#invoiceIdInput').val();
                if (!invoiceId) return;

                $('#lookupStatus').html('<span class="spinner-border spinner-border-sm me-1"></span> {{ __('Loading invoice...') }}');

                $.ajax({
                    url: "{{ url('refunds/invoice-details') }}/" + invoiceId,
                    type: 'GET',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    success: function(res) {
                        $('#lookupStatus').text('');
                        renderLoadedInvoice(res);
                    },
                    error: function(xhr) {
                        var msg = xhr.responseJSON && xhr.responseJSON.error ? xhr.responseJSON.error : "{{ __('Failed to load invoice.') }}";
                        $('#lookupStatus').html('<span class="text-danger">' + msg + '</span>');
                    }
                });
            });

            function renderLoadedInvoice(data) {
                var inv = data.invoice;
                var items = data.items;

                $('#hiddenInvoiceId').val(inv.id);
                $('#dispInvoiceId').text('#' + inv.id);
                $('#dispCustomer').text(inv.customer_name);
                $('#dispBranch').text(inv.branch_name);
                $('#dispNetTotal').text('$' + inv.net_total.toFixed(2));
                $('#dispRemaining').text('$' + inv.remaining_refundable_amount.toFixed(2));

                var html = '';
                items.forEach(function(item, idx) {
                    var isService = item.type === 'service';
                    var remQty = item.remaining_quantity;
                    var rowClass = remQty <= 0 ? 'table-light text-muted' : '';

                    var invSelect = '';
                    if (!isService && remQty > 0 && inventories.length > 0) {
                        invSelect += '<div class="mt-1"><select name="items[' + idx + '][inventory_id]" class="form-select form-select-sm py-0" style="font-size: 0.75rem;">';
                        inventories.forEach(function(iv) {
                            invSelect += '<option value="' + iv.id + '">{{ __('Restock to:') }} ' + iv.name + '</option>';
                        });
                        invSelect += '</select></div>';
                    }

                    var qtyInput = remQty > 0
                        ? '<input type="number" name="items[' + idx + '][quantity]" class="form-control form-control-sm text-center item-qty-input" value="0" min="0" max="' + remQty + '" data-price="' + item.net_unit_price + '" data-is-service="' + (isService ? '1' : '0') + '" data-commission-unit="' + (item.original_quantity > 0 ? (item.commission_amount / item.original_quantity) : 0) + '" data-max="' + remQty + '">'
                        : '<span class="badge bg-secondary">{{ __('Fully Refunded') }}</span>';

                    html += '<tr class="item-row ' + rowClass + '" data-idx="' + idx + '">' +
                        '<td><div class="d-flex align-items-center">' +
                        (isService ? '<span class="badge bg-info-subtle text-info me-2 p-2"><i class="bi bi-scissors"></i></span>' : '<span class="badge bg-success-subtle text-success me-2 p-2"><i class="bi bi-box-seam"></i></span>') +
                        '<div><span class="fw-bold d-block">' + item.name + '</span>' +
                        (isService ? '<span class="text-muted small"><i class="bi bi-person me-1"></i>' + item.provider_name + '</span>' : '') +
                        invSelect +
                        '</div></div>' +
                        '<input type="hidden" name="items[' + idx + '][sales_invoice_detail_id]" value="' + item.id + '">' +
                        '</td>' +
                        '<td class="text-center">' + item.original_quantity + '</td>' +
                        '<td class="text-center text-muted">' + item.refunded_quantity + '</td>' +
                        '<td class="text-center fw-bold ' + (remQty > 0 ? 'text-primary' : 'text-muted') + '">' + remQty + '</td>' +
                        '<td class="text-end">$' + item.net_unit_price.toFixed(2) + (item.discount > 0 ? '<div class="text-muted small">-' + item.discount + '%</div>' : '') + '</td>' +
                        '<td class="text-center">' + qtyInput + '</td>' +
                        '<td class="text-end fw-bold line-refund-total">__('$') 0.00</td>' +
                        '</tr>';
                });

                $('#itemsTableBody').html(html);
                $('#refundForm').slideDown();
                recalculateTotals();
            }

            $('#refundForm').on('submit', function(e) {
                var total = parseFloat($('#summaryTotalRefund').text().replace('$', '')) || 0;
                if (total <= 0) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: "{{ __('No Items Selected') }}",
                        text: "{{ __('Please specify a refund quantity greater than 0 for at least one item.') }}"
                    });
                }
            });

            recalculateTotals();
        });
    </script>
@endsection
