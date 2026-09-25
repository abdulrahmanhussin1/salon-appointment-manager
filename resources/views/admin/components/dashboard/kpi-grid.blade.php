<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 mb-4 kpi-grid">

    {{-- KPI 1: Total Revenue (Owner/Admin/Manager/Accountant) --}}
    @if(\App\Traits\AppHelper::perUser('reports.index'))
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Total Revenue') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(65, 84, 241, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-wallet2 text-primary fs-5"></i>
                    </div>
                </div>

                {{-- Loading Skeleton --}}
                <div x-show="loading.summary" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 75%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 45%;"></div>
                </div>

                {{-- Value Content --}}
                <div x-show="!loading.summary">
                    <h4 class="fw-bold mb-1 text-dark" x-text="formatCurrency(data.summary?.revenue?.total)">0.00 EGP</h4>
                    <p class="text-muted small mb-2">
                        <span x-text="data.summary?.revenue?.invoice_count || 0">0</span> {{ __('invoices') }}
                        • {{ __('Avg') }} <span x-text="formatCurrency(data.summary?.revenue?.avg_ticket)">0 EGP</span>
                    </p>
                    <a href="{{ route('report.daily_revenues') }}" class="small text-primary text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('View Daily Report') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-primary" style="height: 3px;"></div>
        </div>
    </div>
    @endif

    {{-- KPI 2: Cash Sales --}}
    @if(\App\Traits\AppHelper::perUser('reports.index') || auth()->user()->hasRole('cashier'))
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Cash Sales') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(46, 202, 106, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-cash-stack text-success fs-5"></i>
                    </div>
                </div>

                <div x-show="loading.summary" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 70%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 40%;"></div>
                </div>

                <div x-show="!loading.summary">
                    <h4 class="fw-bold mb-1 text-dark" x-text="formatCurrency(data.summary?.revenue?.cash)">0.00 EGP</h4>
                    <p class="text-muted small mb-2">
                        {{ __('Cash in Drawer') }}
                    </p>
                    <a href="{{ route('sales_invoices.index') }}" class="small text-success text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('Invoices List') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-success" style="height: 3px;"></div>
        </div>
    </div>
    @endif

    {{-- KPI 3: Card Sales --}}
    @if(\App\Traits\AppHelper::perUser('reports.index') || auth()->user()->hasRole('cashier'))
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Card / POS Sales') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(13, 202, 240, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-credit-card-2-front text-info fs-5"></i>
                    </div>
                </div>

                <div x-show="loading.summary" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 70%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 40%;"></div>
                </div>

                <div x-show="!loading.summary">
                    <h4 class="fw-bold mb-1 text-dark" x-text="formatCurrency(data.summary?.revenue?.card)">0.00 EGP</h4>
                    <p class="text-muted small mb-2">
                        {{ __('Electronic Payments') }}
                    </p>
                    <a href="{{ route('sales_invoices.index') }}" class="small text-info text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('View Payments') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-info" style="height: 3px;"></div>
        </div>
    </div>
    @endif

    {{-- KPI 4: Net Profit --}}
    @if(\App\Traits\AppHelper::perUser('reports.index'))
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Net Profit') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(255, 119, 29, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-graph-up-arrow text-warning fs-5"></i>
                    </div>
                </div>

                <div x-show="loading.summary" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 70%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 40%;"></div>
                </div>

                <div x-show="!loading.summary">
                    <h4 class="fw-bold mb-1" :class="data.summary?.net_profit >= 0 ? 'text-success' : 'text-danger'" x-text="formatCurrency(data.summary?.net_profit)">0.00 EGP</h4>
                    <p class="text-muted small mb-2">
                        {{ __('Revenue minus Expenses') }}
                    </p>
                    <a href="{{ route('report.dailySummaryPage') }}" class="small text-warning text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('Daily Summary') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-warning" style="height: 3px;"></div>
        </div>
    </div>
    @endif

    {{-- KPI 5: Appointments Today (All roles) --}}
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Appointments') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(13, 110, 253, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-calendar-check text-primary fs-5"></i>
                    </div>
                </div>

                <div x-show="loading.summary" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 50%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 60%;"></div>
                </div>

                <div x-show="!loading.summary">
                    <h4 class="fw-bold mb-1 text-dark" x-text="formatNumber(data.summary?.appointments?.total)">0</h4>
                    <p class="text-muted small mb-2">
                        <span class="text-success fw-semibold" x-text="data.summary?.appointments?.completed || 0">0</span> {{ __('done') }} •
                        <span class="text-warning fw-semibold" x-text="data.summary?.appointments?.requested || 0">0</span> {{ __('pending') }}
                    </p>
                    <a href="{{ route('home.calender') }}" class="small text-primary text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('Calendar View') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-primary" style="height: 3px;"></div>
        </div>
    </div>

    {{-- KPI 6: Active Customers --}}
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Customers') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(108, 117, 125, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-people text-secondary fs-5"></i>
                    </div>
                </div>

                <div x-show="loading.summary" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 50%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 50%;"></div>
                </div>

                <div x-show="!loading.summary">
                    <h4 class="fw-bold mb-1 text-dark" x-text="formatNumber(data.summary?.customers?.total_active)">0</h4>
                    <p class="text-muted small mb-2">
                        +<span class="text-primary fw-semibold" x-text="data.summary?.customers?.new_today || 0">0</span> {{ __('registered in period') }}
                    </p>
                    <a href="{{ route('customers.index') }}" class="small text-secondary text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('Customer Directory') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-secondary" style="height: 3px;"></div>
        </div>
    </div>

    {{-- KPI 7: Total Expenses --}}
    @if(\App\Traits\AppHelper::perUser('expenses.index') || \App\Traits\AppHelper::perUser('reports.index'))
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Expenses') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(220, 53, 69, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-receipt-cutoff text-danger fs-5"></i>
                    </div>
                </div>

                <div x-show="loading.summary" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 70%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 40%;"></div>
                </div>

                <div x-show="!loading.summary">
                    <h4 class="fw-bold mb-1 text-danger" x-text="formatCurrency(data.summary?.expenses?.total)">0.00 EGP</h4>
                    <p class="text-muted small mb-2">
                        {{ __('Active Period Expenses') }}
                    </p>
                    <a href="{{ route('expenses.index') }}" class="small text-danger text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('Expense Records') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-danger" style="height: 3px;"></div>
        </div>
    </div>
    @endif

    {{-- KPI 8: Inventory Stock Alerts --}}
    @if(\App\Traits\AppHelper::perUser('inventories.index'))
    <div class="col">
        <div class="card h-100 border-0 shadow-sm kpi-card position-relative overflow-hidden">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold text-uppercase tracking-wider">{{ __('Stock Alerts') }}</span>
                    <div class="kpi-icon-wrapper rounded-3 d-flex align-items-center justify-content-center" style="background: rgba(253, 126, 20, 0.1); width: 40px; height: 40px;">
                        <i class="bi bi-box-seam text-warning fs-5"></i>
                    </div>
                </div>

                <div x-show="loading.inventory" class="kpi-skeleton py-2">
                    <div class="skeleton-line mb-2" style="height: 28px; width: 50%;"></div>
                    <div class="skeleton-line" style="height: 14px; width: 50%;"></div>
                </div>

                <div x-show="!loading.inventory">
                    <h4 class="fw-bold mb-1" :class="(data.inventory?.out_of_stock_count > 0) ? 'text-danger' : 'text-dark'" x-text="(data.inventory?.out_of_stock_count || 0) + (data.inventory?.low_stock_count || 0)">0</h4>
                    <p class="text-muted small mb-2">
                        <span class="text-danger fw-semibold" x-text="data.inventory?.out_of_stock_count || 0">0</span> {{ __('out of stock') }} •
                        <span class="text-warning fw-semibold" x-text="data.inventory?.low_stock_count || 0">0</span> {{ __('low') }}
                    </p>
                    <a href="{{ route('inventories.index') }}" class="small text-warning text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                        <span>{{ __('Manage Stock') }}</span>
                        <i class="bi bi-arrow-right rtl-flip"></i>
                    </a>
                </div>
            </div>
            <div class="position-absolute bottom-0 start-0 w-100 bg-warning" style="height: 3px;"></div>
        </div>
    </div>
    @endif

</div>
