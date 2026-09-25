@if(\App\Traits\AppHelper::perUser('reports.index'))
<div class="row g-3 mb-4 branch-expense-section">

    {{-- Expense Category Breakdown --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                        <i class="bi bi-pie-chart text-danger me-2"></i>{{ __('Expense Categories') }}
                    </h5>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25" x-text="formatCurrency(data.expenses?.total)">0.00 EGP</span>
                </div>

                {{-- Chart Loading --}}
                <div x-show="loading.expenses" class="text-center py-5">
                    <div class="spinner-border text-danger spinner-border-sm" role="status"></div>
                    <p class="text-muted small mt-2 mb-0">{{ __('Aggregating expenses by category...') }}</p>
                </div>

                <div x-show="!loading.expenses">
                    <div id="expense-category-chart" style="min-height: 240px;"></div>

                    {{-- Category List --}}
                    <div class="mt-3 pt-3 border-top">
                        <template x-for="cat in data.expenses?.by_category || []" :key="cat.category">
                            <div class="d-flex align-items-center justify-content-between py-1 small">
                                <span class="text-muted d-flex align-items-center gap-2">
                                    <i class="bi bi-circle-fill" style="font-size: 8px; color: #dc3545;"></i>
                                    <span x-text="cat.category"></span>
                                </span>
                                <div>
                                    <strong class="text-dark me-2" x-text="formatCurrency(cat.total)"></strong>
                                    <span class="badge bg-light text-muted border" x-text="cat.percentage + '%'"></span>
                                </div>
                            </div>
                        </template>
                        <div x-show="(!data.expenses?.by_category || data.expenses?.by_category?.length === 0) && !loading.expenses" class="text-center py-3 text-muted small">
                            {{ __('No expenses recorded for this period') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Financial Profitability Command Matrix --}}
    <div class="col-12 col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body p-3 p-md-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                        <i class="bi bi-cash-coin text-success me-2"></i>{{ __('Financial Overview') }}
                    </h5>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">{{ __('Real-time P&L') }}</span>
                </div>

                <div x-show="loading.summary" class="text-center py-5">
                    <div class="spinner-border text-success spinner-border-sm" role="status"></div>
                </div>

                <div x-show="!loading.summary" class="d-flex flex-column gap-3">
                    {{-- Row 1: Gross Sales vs Expenses --}}
                    <div class="p-3 rounded-3 bg-light border">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">{{ __('Total Period Revenue') }}</span>
                            <strong class="text-dark fs-6" x-text="formatCurrency(data.summary?.revenue?.total)">0.00 EGP</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">{{ __('Operating Expenses') }}</span>
                            <span class="text-danger fw-semibold" x-text="'-' + formatCurrency(data.summary?.expenses?.total)">0.00 EGP</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="fw-bold text-dark small">{{ __('Net Operating Profit') }}</span>
                            <strong :class="data.summary?.net_profit >= 0 ? 'text-success' : 'text-danger'" class="fs-5" x-text="formatCurrency(data.summary?.net_profit)">0.00 EGP</strong>
                        </div>
                    </div>

                    {{-- Row 2: Breakdown Services vs Products --}}
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border h-100">
                                <span class="d-block text-muted very-small text-uppercase fw-semibold mb-1">
                                    <i class="bi bi-scissors text-primary me-1"></i>{{ __('Services Revenue') }}
                                </span>
                                <h6 class="fw-bold mb-0 text-primary" x-text="formatCurrency(data.summary?.revenue?.services)">0 EGP</h6>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-light border h-100">
                                <span class="d-block text-muted very-small text-uppercase fw-semibold mb-1">
                                    <i class="bi bi-bag-check text-warning me-1"></i>{{ __('Products Revenue') }}
                                </span>
                                <h6 class="fw-bold mb-0 text-warning" x-text="formatCurrency(data.summary?.revenue?.products)">0 EGP</h6>
                            </div>
                        </div>
                    </div>

                    {{-- Row 3: Staff Commission Liability & Refunds --}}
                    <div class="d-flex justify-content-between align-items-center px-3 py-2 rounded-3 bg-light border small">
                        <div>
                            <i class="bi bi-person-badge text-secondary me-1"></i>
                            <span class="text-muted">{{ __('Commissions Accrued:') }}</span>
                            <strong class="text-dark ms-1" x-text="formatCurrency(data.summary?.commissions?.total)">0 EGP</strong>
                        </div>
                        <div>
                            <i class="bi bi-arrow-counterclockwise text-danger me-1"></i>
                            <span class="text-muted">{{ __('Refunds:') }}</span>
                            <strong class="text-danger ms-1" x-text="formatCurrency(data.summary?.refunds?.total)">0 EGP</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@endif
