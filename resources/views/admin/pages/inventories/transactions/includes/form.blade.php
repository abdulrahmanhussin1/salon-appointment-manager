<form id="purchase-invoice-form"  @if(isset($invoice)) action="{{ route('purchase_invoices.update',['purchase_invoice'=>$invoice->id]) }}"
    @else action="{{ route('purchase_invoices.store') }}" @endif method="POST">
    @csrf
    @if(isset($invoice)) @method('PUT')


    <div class="col-3 my-3">
            <label for="invoice_number" class="form-label">InvoiceNumber</label>
            <input type="text" name="invoice_number" id="invoice_number"
                   class="form-control bg-light form-control-sm" readonly
                   value="{{ isset($invoice) ? $invoice->invoice_number : '' }}"
                   oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
        </div>
    @endif

    <!-- Supplier Selection -->
    <div class="row mb-3">
        <div class="col-4 mb-3">
            <label for="branch_id" class="form-label">Branch</label>
            <select class="form-select select2" id="branch_id" name="branch_id" required>
                <option value="">Select Branch</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}"
                        @if (isset($invoice) && $invoice->branch_id == $branch->id) selected @endif
                        @if (!isset($invoice) && Auth::user()->employee?->branch_id == $branch->id) selected @endif>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-4 mb-3">
            <label for="supplier_id" class="form-label">Supplier</label>
            <select class="form-select select2" id="supplier_id" name="supplier_id" required>
                <option value="">Select Supplier</option>
                @foreach ($suppliers as $supplier)
                    <option value="{{ $supplier->id }}" @if(isset($invoice) && $invoice->supplier_id == $supplier->id) selected @endif>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-4 mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select select2" id="status" name="status" required>
                <option value="active" @if(isset($invoice) && $invoice->status == 'active') selected @endif>Active</option>
                <option value="inactive" @if(isset($invoice) && $invoice->status == 'inactive') selected @endif>Inactive</option>
            </select>
        </div>

        <div class="col-6">
            <label for="invoice_date" class="form-label">Invoice Date</label>
            <input type="date" class="form-control form-control-sm" id="invoice_date" name="invoice_date"
                   value="{{ isset($invoice) ? $invoice->invoice_date : '' }}" required>
        </div>

        <div class="col-6">
            <label for="total_amount" class="form-label">Total Amount</label>
            <input type="text" class="form-control bg-light form-control-sm" id="total_amount" name="total_amount"
                   placeholder="0.00" readonly value="{{ isset($invoice) ? $invoice->total_amount : '' }}">
        </div>

        <div class="col-6 mt-3">
            <label for="invoice_discount" class="form-label">Invoice Discount (Amount)</label>
            <input type="text" name="invoice_discount" id="invoice_discount"
                   class="form-control bg-light form-control-sm" placeholder="0.00"
                   value="{{ isset($invoice) ? $invoice->invoice_discount : '' }}"
                   oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
        </div>

        <div class="col-6 mt-3">
            <label for="net_amount" class="form-label">Net Amount (After Discount)</label>
            <input type="text" class="form-control bg-light form-control-sm" id="net_amount"
                   name="net_amount" placeholder="0.00" readonly value="{{ isset($invoice) ? $invoice->net_amount : '' }}">
        </div>

        <div class="form-group mt-3">
            <label for="invoice_notes">Notes</label>
            <textarea name="invoice_notes" id="invoice_notes" class="form-control">{{ isset($invoice) ? $invoice->notes : '' }}</textarea>
        </div>
    </div>
    <hr>

    <!-- Products Table -->
    <div class="card-title">
        <h5>Invoice Details</h5>
    </div>
    <table class="table table-bordered table-striped" id="details-table">
        <thead class="table-light">
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Supplier Price</th>
                <th>Discount (%)</th>
                <th>Subtotal</th>
                <th>Notes</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($invoice) && $invoice->details)
                @foreach($invoice->details as $key => $detail)
                    <tr data-row-id="{{ $key }}">
                        <td>
                            <select name="details[{{ $key }}][product_id]" class="form-control select2 bg-white" required>
                                <option value="">Select Product</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" @if($product->id == $detail->product_id) selected @endif>
                                        {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input type="text" name="details[{{ $key }}][quantity]" value="{{ $detail->quantity }}" class="form-control quantity" required></td>
                        <td><input type="text" name="details[{{ $key }}][supplier_price]" value="{{ $detail->supplier_price }}" class="form-control price" required></td>
                        <td><input type="text" name="details[{{ $key }}][discount]" value="{{ $detail->discount }}" class="form-control discount"></td>
                        <td><input type="text" name="details[{{ $key }}][subtotal]" value="{{ $detail->subtotal }}" class="form-control subtotal" readonly></td>
                        <td><textarea name="details[{{ $key }}][notes]" class="form-control">{{ $detail->notes }}</textarea></td>
                        <td><button type="button" class="btn btn-danger removeRow"><i class="bi-trash"></i></button></td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
    <button type="button" id="addRow" class="btn btn-success btn-sm"><i class="bi bi-plus-circle me-2"></i> Product</button>

    <div class="d-flex justify-content-end mt-4">
        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> {{ isset($invoice) ? 'Update' : 'Create' }} Invoice</button>
    </div>
</form>
