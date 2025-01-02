{{-- Add Item Form --}}
<div class="col-12 mb-3">
    <div class="card">
        <div class="card-body row">
            <h5 class="card-title">Add New Item</h5>
            <form id="item-form">
                {{-- Item Type Selection --}}
                <div class="col-md-4">
                    <label class="form-label">Item Type</label>
                    <select id="item-type" class="form-select" required>
                        <option value="" selected disabled>Select Type</option>
                        <option value="product">Product</option>
                        <option value="service">Service</option>
                    </select>
                </div>

                {{-- Category Selection (dynamic) --}}
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select id="category" class="form-select" disabled required>
                        <option value="" selected disabled>Select Category</option>
                    </select>
                </div>

                {{-- Item Selection --}}
                <div class="col-md-4">
                    <label class="form-label">Item</label>
                    <select id="item" class="form-select" disabled required>
                        <option value="" selected disabled>Select Item</option>
                    </select>
                </div>

                {{-- Provider Selection --}}
                <div class="col-md-4">
                    <label class="form-label">Provider</label>
                    <select id="provider" class="form-select"  required>
                        <option value="" selected disabled>Select Provider</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input type="number" id="quantity" class="form-control" min="1" value="1" required>
                </div>

                {{-- Price --}}
                <div class="col-md-2">
                    <label class="form-label">Price</label>
                    <input type="number" id="price" class="form-control" step="0.01" readonly>
                </div>

                {{-- Discount --}}
                <div class="col-md-2">
                    <label class="form-label">Discount (%)</label>
                    <input type="number" id="discount" class="form-control" min="0" max="100" value="0" step="0.01">
                </div>

                {{-- Tax --}}
                <div class="col-md-2">
                    <label class="form-label">Tax (%)</label>
                    <input type="number" id="tax" class="form-control" min="0" max="100" value="14" step="0.01">
                </div>

                <div class="col-12">
                    <button type="button" class="btn btn-primary btn-sm mt-2" id="add-item-btn"><i class="bi bi-plus-lg"></i> Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Invoice Items Table --}}
<div class="col-12">
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">Invoice Items</h5>
            <div class="table-responsive">
                <table id="invoice-items" class="table table-sm fs--1 table-bordered">
                    <thead>
                        <tr>
                            {{-- <th>Type</th>
                            <th>Category</th> --}}
                            <th>Item</th>
                            <th>Code</th>
                            <th>Provider</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Discount (%)</th>
                            <th>Tax (%)</th>
                            <th>Due</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Items will be added here dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
