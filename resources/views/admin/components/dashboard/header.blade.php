<div class="card border-0 shadow-sm mb-4 dashboard-header-card">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h3 class="h4 fw-bold mb-0 text-dark">
                        {{ __('Welcome back') }}, {{ auth()->user()->name }} 👋
                    </h3>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill small">
                        <i class="bi bi-shield-check me-1"></i>{{ ucfirst(auth()->user()->roles->first()?->name ?? __('Staff')) }}
                    </span>
                </div>
                <p class="text-muted mb-0 small">
                    <i class="bi bi-clock me-1"></i><span id="dashboard-live-clock">{{ now()->format('l, d F Y') }}</span>
                    @if($effectiveBranchId && ($currentBranch = $availableBranches->firstWhere('id', $effectiveBranchId)))
                        <span class="mx-2">•</span>
                        <i class="bi bi-geo-alt-fill text-danger me-1"></i><strong class="text-dark">{{ $currentBranch->name }}</strong>
                    @elseif($canSelectAllBranches)
                        <span class="mx-2">•</span>
                        <i class="bi bi-building text-primary me-1"></i><strong class="text-dark">{{ __('All Branches') }}</strong>
                    @endif
                </p>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="button"
                        class="btn btn-outline-secondary btn-sm px-3 d-flex align-items-center gap-2 rounded-pill shadow-xs"
                        @click="fetchAll()"
                        :disabled="loading.summary || loading.revenue"
                        aria-label="{{ __('Refresh Dashboard Data') }}">
                    <i class="bi bi-arrow-repeat" :class="{'spin-infinite': loading.summary || loading.revenue}"></i>
                    <span>{{ __('Refresh') }}</span>
                </button>

                @if(\App\Traits\AppHelper::perUser('reports.index'))
                <a href="{{ route('report.daily_revenues') }}" class="btn btn-primary btn-sm px-3 rounded-pill d-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    <span>{{ __('Financial Reports') }}</span>
                </a>
                @endif
            </div>
        </div>
    </div>
</div>
