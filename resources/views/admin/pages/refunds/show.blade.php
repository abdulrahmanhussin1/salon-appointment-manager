@extends('admin.layouts.app')
@section('title')
    {{ __('Refund Receipt') }} #{{ $refund->refund_number }}
@endsection

@section('css')
    <style>
        .refund-receipt-wrapper {
            max-width: 500px;
            margin: auto;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            padding: 24px;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            .refund-receipt-wrapper, .refund-receipt-wrapper * {
                visibility: visible;
            }
            .refund-receipt-wrapper {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                box-shadow: none;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
@endsection

@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Refund Receipt">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('refunds.index') }}">{{ __('Refunds') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ $refund->refund_number }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <section class="section">
        <div class="d-flex justify-content-center gap-2 mb-3 no-print">
            <a href="{{ route('refunds.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> {{ __('Back to Refunds') }}
            </a>
            <a href="{{ route('sales_invoices.invoice', $refund->sales_invoice_id) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-receipt me-1"></i> {{ __('View Sales Invoice') }}
            </a>
            <button onclick="window.print()" class="btn btn-sm btn-primary">
                <i class="bi bi-printer me-1"></i> {{ __('Print Receipt') }}
            </button>
        </div>

        <div class="refund-receipt-wrapper">
            <div class="text-center mb-4">
                @if (!empty($adminPanelSetting->system_logo) && Storage::exists($adminPanelSetting->system_logo))
                    <img src="{{ Storage::url($adminPanelSetting->system_logo) }}" alt="Logo" style="max-height: 50px;" class="mb-2">
                @endif
                <h5 class="fw-bold mb-1">{{ $adminPanelSetting->system_name ?? 'Salon Appointment Manager' }}</h5>
                <div class="badge bg-danger text-uppercase px-3 py-2 fs--1 mb-2">
                    <i class="bi bi-arrow-return-left me-1"></i> {{ __('REFUND VOUCHER') }}
                </div>
                <div class="text-muted small">
                    <strong>{{ __('Receipt #:') }}</strong> {{ $refund->refund_number }}
                </div>
            </div>

            <hr style="border-top: 1px dashed #bbb;" class="my-3">

            <div class="small mb-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Original Invoice:') }}</span>
                    <span class="fw-bold">#{{ $refund->sales_invoice_id }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Customer:') }}</span>
                    <span class="fw-bold">{{ $refund->customer?->name ?? __('Walk-in') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Branch:') }}</span>
                    <span class="fw-bold">{{ $refund->branch?->name ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Refund Date:') }}</span>
                    <span>{{ $refund->refund_date?->format('Y-m-d') }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Refund Method:') }}</span>
                    <span class="badge bg-secondary">{{ $refund->refund_method_label }}</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted">{{ __('Reason:') }}</span>
                    <span>{{ $refund->reason }}</span>
                </div>
                @if ($refund->notes)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">{{ __('Notes:') }}</span>
                        <span class="text-end fst-italic">{{ $refund->notes }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between">
                    <span class="text-muted">{{ __('Processed By:') }}</span>
                    <span>{{ $refund->createdBy?->name ?? 'System' }}</span>
                </div>
            </div>

            <hr style="border-top: 1px dashed #bbb;" class="my-3">

            <table class="table table-sm table-borderless small mb-3">
                <thead>
                    <tr class="border-bottom text-muted">
                        <th>{{ __('Item') }}</th>
                        <th class="text-center">{{ __('Qty') }}</th>
                        <th class="text-end">{{ __('Price') }}</th>
                        <th class="text-end">{{ __('Total') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($refund->refundDetails as $detail)
                        <tr>
                            <td>
                                <span class="fw-bold">{{ $detail->item_name }}</span>
                                @if ($detail->provider)
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $detail->provider->name }}</div>
                                @endif
                                @if ($detail->inventory_restored)
                                    <span class="badge bg-success-subtle text-success" style="font-size: 0.65rem;">
                                        <i class="bi bi-box-seam me-1"></i>{{ __('Restocked') }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-center">{{ $detail->quantity }}</td>
                            <td class="text-end">${{ number_format($detail->unit_price, 2) }}</td>
                            <td class="text-end fw-bold">${{ number_format($detail->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="bg-light p-3 rounded mb-3">
                <div class="d-flex justify-content-between fw-bold fs-6 text-danger">
                    <span>{{ __('TOTAL REFUNDED:') }}</span>
                    <span>${{ number_format($refund->total_refund_amount, 2) }}</span>
                </div>
                @if ($refund->commission_reversed_amount > 0)
                    <div class="d-flex justify-content-between text-muted small mt-1">
                        <span>{{ __('Commission Reversed:') }}</span>
                        <span>${{ number_format($refund->commission_reversed_amount, 2) }}</span>
                    </div>
                @endif
            </div>

            @if ($refund->refund_method === 'deposit')
                <div class="alert alert-info py-2 px-3 small text-center mb-3">
                    <i class="bi bi-wallet2 me-1"></i>
                    {{ __('Credited to customer deposit account.') }}
                </div>
            @endif

            <div class="text-center text-muted small mt-4 pt-3 border-top">
                <p class="mb-0">{{ __('Customer Return Copy') }}</p>
                <p class="mb-0" style="font-size: 0.75rem;">{{ __('Thank you for your business.') }}</p>
            </div>
        </div>
    </section>
@endsection
