@extends('admin.layouts.app')
@section('title')
    {{ __('Stock Adjustment') }}
@endsection
@section('css')
    <style>
        .type-radio-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px 18px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
        }
        .type-radio-card:hover {
            border-color: #adb5bd;
            background-color: #f8f9fa;
        }
        .type-radio-card.active-increase {
            border-color: #198754;
            background-color: #f0fdf4;
            color: #198754;
        }
        .type-radio-card.active-decrease {
            border-color: #dc3545;
            background-color: #fef2f2;
            color: #dc3545;
        }
        .stock-badge {
            font-size: 0.9rem;
            padding: 4px 8px;
        }
    </style>
@endsection
@section('content')
    <x-breadcrumb pageName="Stock Adjustment">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>
            <a href="{{ route('inventories.index') }}">{{ __('Inventories') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item active="true">
            {{ __('Stock Adjustment') }}
        </x-breadcrumb-item>
    </x-breadcrumb>

    <div class="container-fluid mt-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0 fw-bold"><i class="bi bi-sliders me-2 text-primary"></i>{{ __('Manual Stock Adjustment') }}</h4>
                <p class="text-muted small mb-0">{{ __('Record inventory corrections for physical counts, shrinkage, damage, or waste with full audit tracking.') }}</p>
            </div>
            <div>
                <a href="{{ route('inventory_transactions.history') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-clock-history me-1"></i>{{ __('Adjustment History') }}
                </a>
            </div>
        </div>

        @include('admin.layouts.alerts')

        <form id="adjustment-form" method="POST" action="{{ route('inventory_transactions.adjust') }}">
            @csrf

            <!-- Card 1: Adjustment Details -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-info-circle me-2"></i>{{ __('Adjustment Information') }}</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Date -->
                        <div class="col-md-4">
                            <label for="date" class="form-label fw-semibold">{{ __('Date') }} <span class="text-danger">*</span></label>
                            <input type="date" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" class="form-control" required>
                        </div>

                        <!-- Inventory -->
                        <div class="col-md-4">
                            <label for="inventory_id" class="form-label fw-semibold">{{ __('Inventory Location') }} <span class="text-danger">*</span></label>
                            <select id="inventory_id" name="inventory_id" class="form-select" required>
                                <option value="" disabled selected>{{ __('Select Inventory') }}</option>
                                @foreach($inventories as $inv)
                                    <option value="{{ $inv->id }}" {{ old('inventory_id') == $inv->id ? 'selected' : '' }}>
                                        {{ $inv->name }} ({{ $inv->branch?->name ?? __('Main') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Adjustment Reason -->
                        <div class="col-md-4">
                            <label for="adjustment_reason" class="form-label fw-semibold">{{ __('Reason Code') }} <span class="text-danger">*</span></label>
                            <select id="adjustment_reason" name="adjustment_reason" class="form-select" required>
                                <option value="" disabled selected>{{ __('Select Reason') }}</option>
                                <option value="count_correction" {{ old('adjustment_reason') == 'count_correction' ? 'selected' : '' }}>
                                    {{ __('Physical Count Correction / Audit') }}
                                </option>
                                <option value="damage" {{ old('adjustment_reason') == 'damage' ? 'selected' : '' }}>
                                    {{ __('Damaged / Broken Product') }}
                                </option>
                                <option value="waste" {{ old('adjustment_reason') == 'waste' ? 'selected' : '' }}>
                                    {{ __('Waste / Expired / Spilled') }}
                                </option>
                                <option value="theft" {{ old('adjustment_reason') == 'theft' ? 'selected' : '' }}>
                                    {{ __('Shrinkage / Unaccounted Loss') }}
                                </option>
                                <option value="other" {{ old('adjustment_reason') == 'other' ? 'selected' : '' }}>
                                    {{ __('Other (Notes required)') }}
                                </option>
                            </select>
                        </div>

                        <!-- Adjustment Type (Increase / Decrease) -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold d-block">{{ __('Adjustment Direction') }} <span class="text-danger">*</span></label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="type-radio-card w-100" id="card-increase">
                                        <input type="radio" name="adjustment_type" value="increase" class="form-check-input me-3" {{ old('adjustment_type', 'decrease') == 'increase' ? 'checked' : '' }} required>
                                        <div>
                                            <div class="fw-bold text-success"><i class="bi bi-plus-circle me-1"></i>{{ __('Stock Increase (+)') }}</div>
                                            <small class="text-muted">{{ __('Found stock, positive audit count variance, surplus items.') }}</small>
                                        </div>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="type-radio-card w-100 active-decrease" id="card-decrease">
                                        <input type="radio" name="adjustment_type" value="decrease" class="form-check-input me-3" {{ old('adjustment_type', 'decrease') == 'decrease' ? 'checked' : '' }} required>
                                        <div>
                                            <div class="fw-bold text-danger"><i class="bi bi-dash-circle me-1"></i>{{ __('Stock Decrease (-)') }}</div>
                                            <small class="text-muted">{{ __('Damaged bottles, expired stock, physical deficit, shrinkage.') }}</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="col-md-12">
                            <label for="notes" class="form-label fw-semibold">{{ __('Audit Notes & Explanation') }} <span id="notes-required-badge" class="badge bg-warning text-dark d-none">{{ __('Required for Other') }}</span></label>
                            <textarea id="notes" name="notes" rows="2" class="form-control" placeholder="{{ __('Provide additional context or audit ticket reference...') }}">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Products to Adjust -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-semibold text-dark"><i class="bi bi-box-seam me-2"></i>{{ __('Products & Quantities') }}</h6>
                    <button type="button" id="btn-add-product" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>{{ __('Add Product Line') }}
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <!-- Server-rendered product options for fast cloning and SEO -->
                        <select id="products-catalog-select" class="d-none">
                            <option value="" disabled selected>{{ __('Select Product') }}</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} {{ $p->code ? '('.$p->code.')' : '' }}</option>
                            @endforeach
                        </select>

                        <table class="table table-hover align-middle mb-0" id="products-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 250px;">{{ __('Product') }} <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 160px;">{{ __('Current Stock') }}</th>
                                    <th class="text-center" style="width: 160px;">{{ __('Adjustment Qty') }} <span class="text-danger">*</span></th>
                                    <th class="text-center" style="width: 160px;">{{ __('Resulting Stock') }}</th>
                                    <th class="text-center" style="width: 80px;">{{ __('Action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="row-empty">
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                        {{ __('No products added yet. Click "Add Product Line" to begin.') }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light text-end py-3">
                    <button type="submit" id="btn-submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-check2-circle me-1"></i>{{ __('Submit Stock Adjustment') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('script')
<script>
    $(document).ready(function() {
        let rowIdx = 0;
        const productsList = @json($products);

        // Highlight active radio card
        function updateRadioCards() {
            const val = $('input[name="adjustment_type"]:checked').val();
            if (val === 'increase') {
                $('#card-increase').addClass('active-increase');
                $('#card-decrease').removeClass('active-decrease');
            } else {
                $('#card-decrease').addClass('active-decrease');
                $('#card-increase').removeClass('active-increase');
            }
            recalcAllPreviews();
        }

        $('input[name="adjustment_type"]').on('change', function() {
            updateRadioCards();
        });
        updateRadioCards();

        // Highlight required notes for 'other'
        $('#adjustment_reason').on('change', function() {
            if ($(this).val() === 'other') {
                $('#notes-required-badge').removeClass('d-none');
                $('#notes').prop('required', true);
            } else {
                $('#notes-required-badge').addClass('d-none');
                $('#notes').prop('required', false);
            }
        });

        // Add Product Row
        function addProductRow(productId = '', quantity = 1) {
            $('#row-empty').remove();

            let optionsHtml = `<option value="" disabled ${productId ? '' : 'selected'}>{{ __('Select Product') }}</option>`;
            productsList.forEach(p => {
                const isSelected = productId == p.id ? 'selected' : '';
                optionsHtml += `<option value="${p.id}" ${isSelected}>${p.name} ${p.code ? '(' + p.code + ')' : ''}</option>`;
            });

            const tr = `
                <tr class="product-row" id="product-row-${rowIdx}">
                    <td>
                        <select name="products[${rowIdx}][product_id]" class="form-select product-select" required>
                            ${optionsHtml}
                        </select>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary current-stock-badge">—</span>
                        <input type="hidden" class="current-stock-val" value="0">
                    </td>
                    <td>
                        <input type="number" min="1" step="1" name="products[${rowIdx}][quantity]" class="form-control text-center adjust-qty-input" value="${quantity}" required>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark resulting-stock-badge">—</span>
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            $('#products-table tbody').append(tr);

            const addedRow = $(`#product-row-${rowIdx}`);
            if (productId) {
                fetchCurrentStock(addedRow);
            }

            rowIdx++;
        }

        $('#btn-add-product').on('click', function() {
            addProductRow();
        });

        // Remove row
        $(document).on('click', '.btn-remove-row', function() {
            $(this).closest('tr').remove();
            if ($('#products-table tbody tr.product-row').length === 0) {
                $('#products-table tbody').append(`
                    <tr id="row-empty">
                        <td colspan="5" class="text-center py-4 text-muted">
                            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                            {{ __('No products added yet. Click "Add Product Line" to begin.') }}
                        </td>
                    </tr>
                `);
            }
        });

        // Fetch stock when product or inventory changes
        function fetchCurrentStock(row) {
            const inventoryId = $('#inventory_id').val();
            const productId = row.find('.product-select').val();

            if (!inventoryId || !productId) {
                row.find('.current-stock-badge').text('—').removeClass('bg-info bg-warning bg-success').addClass('bg-secondary');
                row.find('.current-stock-val').val(0);
                recalcRowPreview(row);
                return;
            }

            $.ajax({
                url: "{{ route('inventory_transactions.check_stock') }}",
                type: 'GET',
                data: {
                    inventory_id: inventoryId,
                    product_id: productId
                },
                success: function(res) {
                    const qty = res.quantity !== undefined ? parseInt(res.quantity) : 0;
                    row.find('.current-stock-val').val(qty);
                    row.find('.current-stock-badge')
                        .text(qty + ' in stock')
                        .removeClass('bg-secondary bg-danger bg-success')
                        .addClass(qty > 0 ? 'bg-info text-dark' : 'bg-secondary');
                    recalcRowPreview(row);
                },
                error: function() {
                    row.find('.current-stock-badge').text('0 in stock').addClass('bg-secondary');
                    row.find('.current-stock-val').val(0);
                    recalcRowPreview(row);
                }
            });
        }

        $(document).on('change', '.product-select', function() {
            const row = $(this).closest('tr');
            fetchCurrentStock(row);
        });

        $('#inventory_id').on('change', function() {
            $('#products-table tbody tr.product-row').each(function() {
                fetchCurrentStock($(this));
            });
        });

        // Recalculate preview
        function recalcRowPreview(row) {
            const current = parseInt(row.find('.current-stock-val').val()) || 0;
            const adjustQty = parseInt(row.find('.adjust-qty-input').val()) || 0;
            const isIncrease = $('input[name="adjustment_type"]:checked').val() === 'increase';

            const resulting = isIncrease ? (current + adjustQty) : (current - adjustQty);
            const badge = row.find('.resulting-stock-badge');

            badge.text(resulting);
            badge.removeClass('bg-danger bg-success bg-light text-dark text-white');

            if (resulting < 0) {
                badge.addClass('bg-danger text-white');
                badge.text(resulting + ' (Insufficient!)');
            } else if (isIncrease) {
                badge.addClass('bg-success text-white');
            } else {
                badge.addClass('bg-primary text-white');
            }
        }

        function recalcAllPreviews() {
            $('#products-table tbody tr.product-row').each(function() {
                recalcRowPreview($(this));
            });
        }

        $(document).on('input change', '.adjust-qty-input', function() {
            recalcRowPreview($(this).closest('tr'));
        });

        // Client-side validation pre-check
        $('#adjustment-form').on('submit', function(e) {
            const rowCount = $('#products-table tbody tr.product-row').length;
            if (rowCount === 0) {
                e.preventDefault();
                alert("{{ __('Please add at least one product line item to adjust.') }}");
                return false;
            }

            const isIncrease = $('input[name="adjustment_type"]:checked').val() === 'increase';
            let hasNegative = false;

            if (!isIncrease) {
                $('#products-table tbody tr.product-row').each(function() {
                    const current = parseInt($(this).find('.current-stock-val').val()) || 0;
                    const adjustQty = parseInt($(this).find('.adjust-qty-input').val()) || 0;
                    if (current - adjustQty < 0) {
                        hasNegative = true;
                    }
                });

                if (hasNegative) {
                    e.preventDefault();
                    alert("{{ __('One or more products have deduction quantities exceeding available stock. Please reduce deduction quantity.') }}");
                    return false;
                }
            }

            $('#btn-submit').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>{{ __('Saving Adjustment...') }}');
        });

        // Add 1 default row on load
        addProductRow();
    });
</script>
@endsection
