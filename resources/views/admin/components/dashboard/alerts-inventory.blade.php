<div class="row g-3 mb-4 alerts-inventory-section">

    {{-- Left: Inventory Stock Alerts (Role-Gated) --}}
    @if(\App\Traits\AppHelper::perUser('inventories.index'))
    <div class="col-12 col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                        <i class="bi bi-shield-exclamation text-danger me-2"></i>{{ __('Inventory Stock Alerts') }}
                    </h5>
                    <a href="{{ route('inventories.index') }}" class="small text-muted text-decoration-none d-flex align-items-center gap-1">
                        <span>{{ __('All Inventories') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>

                <div x-show="loading.inventory" class="text-center py-4">
                    <div class="spinner-border text-danger spinner-border-sm" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">{{ __('Checking inventory thresholds...') }}</p>
                </div>

                <div x-show="!loading.inventory" class="d-flex flex-column gap-2" style="max-height: 340px; overflow-y: auto;">
                    {{-- Out of stock items (Critical) --}}
                    <template x-for="item in data.inventory?.out_of_stock || []" :key="'oos-' + item.product_id + '-' + item.inventory_id">
                        <div class="p-2 px-3 rounded-3 border border-danger border-opacity-25 bg-danger bg-opacity-10 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <strong class="text-danger d-block small" x-text="item.product_name"></strong>
                                <small class="text-muted very-small" x-text="item.branch + ' • ' + item.category"></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-danger text-white">{{ __('0 in stock') }}</span>
                                @if(\App\Traits\AppHelper::perUser('purchase_invoices.create'))
                                <a :href="'/admin/purchase_invoices/create?product_id=' + item.product_id"
                                   class="btn btn-xs btn-danger rounded-pill px-2 py-1 shadow-2xs"
                                   title="{{ __('Create Purchase Order') }}">
                                    <i class="bi bi-cart-plus me-1"></i>{{ __('Order') }}
                                </a>
                                @endif
                            </div>
                        </div>
                    </template>

                    {{-- Low stock items (Warning) --}}
                    <template x-for="item in data.inventory?.low_stock || []" :key="'low-' + item.product_id + '-' + item.inventory_id">
                        <div class="p-2 px-3 rounded-3 border border-warning border-opacity-50 bg-warning bg-opacity-10 d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <strong class="text-dark d-block small" x-text="item.product_name"></strong>
                                <small class="text-muted very-small" x-text="item.branch + ' • ' + item.category"></small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-warning text-dark" x-text="item.current_quantity + ' ' + '{{ __('left') }}'"></span>
                                @if(\App\Traits\AppHelper::perUser('inventory_transactions.adjustView'))
                                <a :href="'/admin/inventory_transactions/adjust'"
                                   class="btn btn-xs btn-outline-warning text-dark rounded-pill px-2 py-1 shadow-2xs"
                                   title="{{ __('Adjust Stock') }}">
                                    <i class="bi bi-sliders me-1"></i>{{ __('Adjust') }}
                                </a>
                                @endif
                            </div>
                        </div>
                    </template>

                    {{-- Healthy Stock State --}}
                    <div x-show="(!data.inventory?.out_of_stock || data.inventory?.out_of_stock?.length === 0) && (!data.inventory?.low_stock || data.inventory?.low_stock?.length === 0)"
                         class="text-center py-4 text-success">
                        <i class="bi bi-check-circle-fill fs-2 mb-2 d-inline-block"></i>
                        <p class="mb-0 fw-semibold small">{{ __('All inventory stock levels are healthy') }}</p>
                        <small class="text-muted">{{ __('No products currently below minimum threshold') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Right: Action Required Command Center Alerts --}}
    <div class="col-12 {{ \App\Traits\AppHelper::perUser('inventories.index') ? 'col-lg-6' : 'col-12' }}">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                        <i class="bi bi-bell-fill text-warning me-2"></i>{{ __('Action Required') }}
                    </h5>
                    <span class="badge bg-light text-muted border" x-text="data.alerts?.length || 0">0</span>
                </div>

                <div x-show="loading.alerts" class="text-center py-4">
                    <div class="spinner-border text-warning spinner-border-sm" role="status"></div>
                </div>

                <div x-show="!loading.alerts" class="d-flex flex-column gap-2" style="max-height: 340px; overflow-y: auto;">
                    <template x-for="alert in data.alerts" :key="alert.id">
                        <div class="p-3 rounded-3 border d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2"
                             :class="{
                                 'border-danger border-opacity-50 bg-danger bg-opacity-10': alert.severity === 'critical',
                                 'border-warning border-opacity-50 bg-warning bg-opacity-10': alert.severity === 'warning',
                                 'border-info border-opacity-50 bg-info bg-opacity-10': alert.severity === 'info'
                             }">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="bi fs-6"
                                       :class="{
                                           'bi-exclamation-octagon-fill text-danger': alert.severity === 'critical',
                                           'bi-exclamation-triangle-fill text-warning': alert.severity === 'warning',
                                           'bi-info-circle-fill text-info': alert.severity === 'info'
                                       }"></i>
                                    <strong class="text-dark small" x-text="alert.title"></strong>
                                </div>
                                <p class="text-muted very-small mb-0" x-text="alert.body"></p>
                            </div>
                            <div class="text-sm-end text-nowrap">
                                <a :href="alert.action_url"
                                   class="btn btn-xs rounded-pill px-3 py-1 fw-medium shadow-2xs"
                                   :class="{
                                       'btn-danger': alert.severity === 'critical',
                                       'btn-warning text-dark': alert.severity === 'warning',
                                       'btn-outline-info': alert.severity === 'info'
                                   }"
                                   x-text="alert.action_label">
                                </a>
                            </div>
                        </div>
                    </template>

                    <div x-show="(!data.alerts || data.alerts.length === 0) && !loading.alerts"
                         class="text-center py-4 text-success">
                        <i class="bi bi-shield-check fs-2 mb-2 d-inline-block text-success"></i>
                        <p class="mb-0 fw-semibold small">{{ __('No critical operational actions required') }}</p>
                        <small class="text-muted">{{ __('All appointments, invoices, and operations are up to date') }}</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
