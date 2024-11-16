@extends('admin.layouts.app')
@section('title', 'Edit Purchase Invoice')
@section('content')
    {{-- Breadcrumbs --}}
    <x-breadcrumb pageName="Edit Invoice">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('purchase_invoices.index') }}">{{ __('Purchase Invoices') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Edit Invoice') }}</x-breadcrumb-item>
    </x-breadcrumb>

    <section class="section">
        <div class="container">
            <form method="POST">
                @csrf
                @method('PUT')

                {{-- Invoice Information --}}
                <div class="card mb-3">
                    <div class="card-header">Invoice Details</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="invoice_number">Invoice Number</label>
                                <input type="text" name="invoice_number" id="invoice_number"
                                    class="form-control @error('invoice_number') is-invalid @enderror"
                                    value="{{ old('invoice_number', $invoice->invoice_number) }}" required>
                                @error('invoice_number')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="invoice_date">Invoice Date</label>
                                <input type="date" name="invoice_date" id="invoice_date"
                                    class="form-control @error('invoice_date') is-invalid @enderror"
                                    value="{{ old('invoice_date', $invoice->invoice_date) }}" required>
                                @error('invoice_date')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="supplier_id">Supplier</label>
                                <select name="supplier_id" id="supplier_id"
                                    class="form-control @error('supplier_id') is-invalid @enderror" required>
                                    <option value="">{{ __('Select Supplier') }}</option>
                                    @foreach ($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}"
                                            {{ $invoice->supplier_id == $supplier->id ? 'selected' : '' }}>
                                            {{ $supplier->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('supplier_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="branch_id">Branch</label>
                                <select name="branch_id" id="branch_id"
                                    class="form-control @error('branch_id') is-invalid @enderror">
                                    <option value="">{{ __('Select Branch') }}</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ $invoice->branch_id == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Invoice Details --}}
                <div class="card mb-3">
                    <div class="card-header">Products</div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Discount</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->details as $detail)
                                    <tr>
                                        <td>
                                            <input type="text" name="details[{{ $loop->index }}][product_id]" class="form-control"
                                                value="{{ $detail->product_id }}" readonly>
                                        </td>
                                        <td>
                                            <input type="number" name="details[{{ $loop->index }}][quantity]" class="form-control"
                                                value="{{ old("details.$loop->index.quantity", $detail->quantity) }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="details[{{ $loop->index }}][supplier_price]" step="0.01"
                                                class="form-control"
                                                value="{{ old("details.$loop->index.supplier_price", $detail->supplier_price) }}" required>
                                        </td>
                                        <td>
                                            <input type="number" name="details[{{ $loop->index }}][discount]" step="0.01"
                                                class="form-control"
                                                value="{{ old("details.$loop->index.discount", $detail->discount) }}">
                                        </td>
                                        <td>
                                            <input type="number" name="details[{{ $loop->index }}][subtotal]" step="0.01"
                                                class="form-control"
                                                value="{{ old("details.$loop->index.subtotal", $detail->subtotal) }}" readonly>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </section>
@endsection
