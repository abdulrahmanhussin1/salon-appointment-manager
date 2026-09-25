@extends('admin.layouts.app')
@section('title')
    {{ __('Sales Invoice') }}
@endsection
@section('css')
    <style>
        * {
            box-sizing: border-box;
        }

        .table-bordered td,
        .table-bordered th {
            border: 1px solid #ddd;
            padding: 10px;
            word-break: break-all;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 16px;
        }

        .h4-14 h4 {
            font-size: 12px;
            margin-top: 0;
            margin-bottom: 5px;
        }

        .img {
            margin-left: "auto";
            margin-top: "auto";
            height: 30px;
        }

        pre,
        p {
            /* width: 99%; */
            /* overflow: auto; */
            /* bpicklist: 1px solid #aaa; */
            padding: 0;
            margin: 0;
        }

        table {
            font-family: arial, sans-serif;
            width: 100%;
            border-collapse: collapse;
            padding: 1px;
        }

        .hm-p p {
            text-align: left;
            padding: 1px;
            padding: 5px 4px;
        }

        td,
        th {
            text-align: left;
            padding: 8px 6px;
        }

        .table-b td,
        .table-b th {
            border: 1px solid #ddd;
        }

        th {
            /* background-color: #ddd; */
        }

        .hm-p td,
        .hm-p th {
            padding: 3px 0px;
        }

        .cropped {
            float: right;
            margin-bottom: 20px;
            height: 100px;
            /* height of container */
            overflow: hidden;
        }

        .cropped img {
            width: 400px;
            margin: 8px 0px 0px 80px;
        }

        .main-pd-wrapper {
            box-shadow: 0 0 10px #ddd;
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
        }

        .table-bordered td,
        .table-bordered th {
            border: 1px solid #ddd;
            padding: 10px;
            font-size: 14px;
        }

        .invoice-items {
            font-size: 14px;
            border-top: 1px dashed #ddd;
        }

        .invoice-items td {
            padding: 14px 0;

        }
    </style>
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Sales Invoice">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('sales_invoices.index') }}">{{ __('Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="{{ isset($invoice) }}">
            {{ isset($invoice) ? __('Invoice #') . $invoice->id : __('Create New Invoice') }}
        </x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    @if ($invoice->status === 'voided')
        <div class="alert alert-danger text-center mb-3" style="max-width: 450px; margin: auto;">
            <h4 class="alert-heading mb-1"><i class="bi bi-slash-circle me-1"></i> {{ __('THIS INVOICE IS VOIDED') }}</h4>
            <div class="small">
                <strong>{{ __('Voided At:') }}</strong> {{ $invoice->voided_at?->format('Y-m-d H:i') }}<br>
                <strong>{{ __('Voided By:') }}</strong> {{ $invoice->voidedBy?->name ?? 'System' }}<br>
                <strong>{{ __('Reason:') }}</strong> {{ $invoice->void_reason }}
            </div>
        </div>
    @endif

    <section id="invoice" class="main-pd-wrapper" style="width: 450px; margin: auto">
        <div
            style="
                  text-align: center;
                  margin: auto;
                  line-height: 1.5;
                  font-size: 14px;
                  color: #4a4a4a;
                ">
            @if ($invoice->status === 'voided')
                <div style="background-color: #f8d7da; color: #842029; padding: 6px; border-radius: 4px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase;">
                    *** {{ __('VOIDED INVOICE') }} ***
                </div>
            @endif
            <img style="max-height:50px"
                src="{{ !empty($adminPanelSetting->system_logo) ? (Storage::exists($adminPanelSetting->system_logo) ? Storage::url($adminPanelSetting->system_logo) : asset('admin-assets/assets/img/avatar.jpg')) : '' }}"
                alt="">

            <p style="font-weight: bold; color: #000; margin-top: 15px; font-size: 18px;">
                {{ $adminPanelSetting->system_name }}
            </p>
            <p style="margin: 15px auto; font-weight: bold ">
                Invoice No: {{ $invoice->id }}<br>

            </p>
            <p>
                Thank You For Visiting Us
            </p>

            <p>
                <b>Name: </b> {{ $invoice->customer->name }}
            </p>
            <hr style="border: 1px dashed rgb(131, 131, 131); margin: 25px auto">
        </div>
        <table style="width: 100%; table-layout: fixed">
            <thead>
                <tr>
                    <th style="width: 40px; padding-left: 0;">Sn.</th>
                    <th style="width: 180px;">Item Name</th>
                    <th>QTY</th>
                    <th>Price</th>
                    {{-- <th>Tax</th>
                    <th>Discount</th> --}}
                    <th>SubTotal</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = 0;
                    $discount = 0;
                @endphp

                @foreach ($invoice->salesInvoiceDetails as $key => $item)
                    <tr class="invoice-items">
                        <td>{{ ++$key }}</td>
                        <td>{{ $item->name() }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td> {{ $item->customer_price }} </td>
                        {{-- <td> {{ $item->tax }} </td>
                        <td> {{ $item->discount }} </td> --}}
                        <td> {{ $item->quantity * $item->customer_price }} </td>
                    </tr>
                    @php

                        $total += $item->quantity * $item->customer_price;
                        if ($item->discount != 0 ) {
                            $discount += $item->quantity * $item->customer_price *  ( $item->discount / 100) ;
                        }

                    @endphp
                @endforeach



            </tbody>
        </table>

        <table style="width: 100%;
              background: #fcbd024f;
              border-radius: 4px;">
            <thead>
                <tr>
                    <th>Total</th>
                    <th style="text-align: center;">Item ({{ $invoice->salesInvoiceDetails->count() }})</th>
                    <th>&nbsp;</th>
                    <th style="text-align: right;">{{ $total }}</th>

                </tr>
            </thead>

        </table>

        <table
            style="width: 100%;
              margin-top: 15px;
              border: 1px dashed #00cd00;
              border-radius: 3px;">
            <thead>
                <tr>
                    <td>Total Saving In : </td>
                    <td style="text-align: right;">{{ $invoice->invoice_discount }}</td>
                </tr>
                <tr>
                    <td>Tax: </td>
                    <td style="text-align: right;">{{ $invoice->invoice_tax }}</td>
                </tr>

                <tr>
                    <td>Total : </td>
                    <td style="text-align: right;">{{ $invoice->net_total }}</td>
                </tr>

            </thead>

        </table>


    </section>

    <div style="width: 450px; margin: auto">
        <button onclick="printInvoice()" class="btn btn-success btn-sm mt-3 w-100">Print</button>
        @if ($invoice->status !== 'voided' && \App\Traits\AppHelper::perUser('sales_invoices.void'))
            <button type="button" class="btn btn-outline-danger btn-sm mt-2 w-100" data-bs-toggle="modal" data-bs-target="#voidInvoiceModal">
                <i class="bi bi-slash-circle me-1"></i> {{ __('Void Invoice') }}
            </button>

            <!-- Void Invoice Modal -->
            <div class="modal fade" id="voidInvoiceModal" tabindex="-1" aria-labelledby="voidInvoiceModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <form method="POST" action="{{ route('sales_invoices.void', $invoice->id) }}">
                        @csrf
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title text-danger" id="voidInvoiceModalLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ __('Void Invoice') }} #{{ $invoice->id }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-start">
                                <p class="text-secondary mb-3">{{ __('Are you sure you want to void this invoice? All inventory deductions and deposit usages will be reversed. This action cannot be undone.') }}</p>
                                <div class="mb-3">
                                    <label for="void_reason" class="form-label fw-bold">{{ __('Reason for voiding') }} <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="void_reason" name="reason" rows="3" required placeholder="{{ __('Please specify the reason for voiding this invoice...') }}"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                                <button type="submit" class="btn btn-danger">{{ __('Confirm Void') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection

@section('js')
    <script>
        function printInvoice() {
            const originalContents = document.body.innerHTML; // Store original content
            const invoiceContent = document.getElementById('invoice').outerHTML; // Get invoice content

            // Create a style element for print-specific styles
            const printStyles = `
        <style>
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                    width: 800px;
                }
                #invoice {
                    font-size: 12px; /* Adjust font size as needed */
                    width: 800px; /* Ensure the width is set for the invoice */
                    margin: auto; /* Center the content */
                }
            }
        </style>
    `;

            // Set up the invoice content with styles for printing
            document.body.innerHTML = printStyles + invoiceContent;

            // Trigger print
            window.print();

            // Restore original content
            document.body.innerHTML = originalContents;

            // Reload the page to restore event listeners
            location.reload();
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (window.location.hash === '#void') {
                const voidModalEl = document.getElementById('voidInvoiceModal');
                if (voidModalEl && typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(voidModalEl);
                    modal.show();
                }
            }
        });
    </script>
@endsection
