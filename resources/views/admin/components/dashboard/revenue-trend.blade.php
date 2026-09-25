@if(\App\Traits\AppHelper::perUser('reports.index'))
<div class="card border-0 shadow-sm mb-4 revenue-trend-card">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3">
            <div>
                <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                    <i class="bi bi-graph-up text-primary me-2"></i>{{ __('Revenue Trend') }}
                </h5>
                <span class="text-muted very-small">{{ __('Daily sales volume across services and retail products') }}</span>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-primary border px-2 py-1 small">
                    <i class="bi bi-circle-fill me-1" style="font-size: 8px;"></i>{{ __('Live Trend') }}
                </span>
            </div>
        </div>

        {{-- Loading Spinner --}}
        <div x-show="loading.revenue" class="text-center py-5">
            <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
            <p class="text-muted small mt-2 mb-0">{{ __('Calculating revenue time-series...') }}</p>
        </div>

        {{-- Chart Canvas --}}
        <div x-show="!loading.revenue">
            <div id="revenue-trend-chart" style="min-height: 320px;"></div>
        </div>

        {{-- Accessible Fallback Table --}}
        <div class="visually-hidden" aria-live="polite">
            <table aria-label="{{ __('Revenue Trend Accessible Data') }}">
                <thead>
                    <tr><th>{{ __('Date') }}</th><th>{{ __('Total') }}</th><th>{{ __('Services') }}</th><th>{{ __('Products') }}</th></tr>
                </thead>
                <tbody>
                    <template x-for="(label, i) in data.revenue?.labels || []" :key="i">
                        <tr>
                            <td x-text="label"></td>
                            <td x-text="data.revenue?.total[i]"></td>
                            <td x-text="data.revenue?.services[i]"></td>
                            <td x-text="data.revenue?.products[i]"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
