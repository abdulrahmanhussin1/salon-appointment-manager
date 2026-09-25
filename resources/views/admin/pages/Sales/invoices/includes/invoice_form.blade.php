{{-- Add Item Form --}}
<div class="col-12 mb-3">
    <div class="card">
        <div class="card-body row">
            <h5 class="card-title">{{ __('Add New Item') }}</h5>
            <form id="item-form">
                {{-- Item Type Selection --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('Item Type') }}</label>
                    <select id="item-type" class="form-select" required>
                        <option value="" selected disabled>{{ __('Select Type') }}</option>
                        <option value="product">{{ __('Product') }}</option>
                        <option value="service">{{ __('Service') }}</option>
                    </select>
                </div>

                {{-- Category Selection (dynamic) --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('Category') }}</label>
                    <select id="category" class="form-select" disabled required>
                        <option value="" selected disabled>{{ __('Select Category') }}</option>
                    </select>
                </div>

                {{-- Item Selection --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('Item') }}</label>
                    <select id="item" class="form-select" disabled required>
                        <option value="" selected disabled>{{ __('Select Item') }}</option>
                    </select>
                </div>

                {{-- Provider Selection --}}
                <div class="col-md-4">
                    <label class="form-label">{{ __('Provider') }}</label>
                    <select id="provider" class="form-select"  required>
                        <option value="" selected disabled>{{ __('Select Provider') }}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">{{ __('Quantity') }}</label>
                    <input type="number" id="quantity" class="form-control" min="1" value="1" required>
                </div>

                {{-- Price --}}
                <div class="col-md-2">
                    <label class="form-label">{{ __('Price') }}</label>
                    <input type="number" id="price" class="form-control" step="0.01" readonly>
                </div>

                {{-- Discount --}}
                <div class="col-md-2">
                    <label class="form-label">{{ __('Discount (%)') }}</label>
                    <input type="number" id="discount" class="form-control" min="0" max="100" value="0" step="0.01">
                </div>

                {{-- Tax --}}
                <div class="col-md-2">
                    <label class="form-label">{{ __('Tax (%)') }}</label>
                    <input type="number" id="tax" class="form-control" min="0" max="100" value="14" step="0.01">
                </div>

                <div class="col-12">
                    <button type="button" class="btn btn-primary btn-sm mt-2" id="add-item-btn"><i class="bi bi-plus-lg"></i> {{ __('Item') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Invoice Items Table --}}
<div class="col-12">
    <div class="card mb-3">
        <div class="card-body">
            <h5 class="card-title">{{ __('Invoice Items') }}</h5>
            <div class="table-responsive">
                <table id="invoice-items" class="table table-sm fs--1 table-bordered">
                    <thead>
                        <tr>
                            {{-- <th>Type</th>
                            <th>Category</th> --}}
                            <th>{{ __('Item') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Provider') }}</th>
                            <th>{{ __('Quantity') }}</th>
                            <th>{{ __('Price') }}</th>
                            <th>{{ __('Discount (%)') }}</th>
                            <th>{{ __('Tax (%)') }}</th>
                            <th>{{ __('Due') }}</th>
                            <th>{{ __('Actions') }}</th>
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
