@extends('admin.layouts.app')
@section('title')
    {{ _('Sales Invoice') }}
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



    <section id="invoice" class="main-pd-wrapper" style="width: 450px; margin: auto">
        <div
            style="
                  text-align: center;
                  margin: auto;
                  line-height: 1.5;
                  font-size: 14px;
                  color: #4a4a4a;
                ">
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
                    <th>SubTotal</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $total = 0;
                @endphp

                @foreach ($invoice->salesInvoiceDetails as $key => $item)
                    <tr class="invoice-items">
                        <td>{{ ++$key }}</td>
                        <td>{{ $item->name() }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td> {{ $item->customer_price }} </td>
                        <td> {{ $item->subtotal }} </td>
                    </tr>
                    @php
                        $total += $item->quantity * $item->customer_price;
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
                    <td>Total : </td>
                    <td style="text-align: right;">{{ $total }}</td>
                </tr>
                <tr>
                    <td>Total Saving In %: </td>
                    <td style="text-align: right;">32%</td>
                </tr>
            </thead>

        </table>


    </section>

    <div style="width: 450px; margin: auto" >
        <button onclick="printInvoice()" class="btn btn-success btn-sm mt-3 w-100">Print</button>
    </div>


@endsection

@section('js')
<script>
    function printInvoice() {
        const originalContents = document.body.innerHTML; // Store original content
        const invoiceContent = document.getElementById('invoice').outerHTML; // Get invoice content
        document.body.innerHTML = invoiceContent; // Replace body with invoice content
        window.print(); // Trigger print
        document.body.innerHTML = originalContents; // Restore original content
        location.reload(); // Reload the page to restore event listeners
    }
</script>
@endsection
