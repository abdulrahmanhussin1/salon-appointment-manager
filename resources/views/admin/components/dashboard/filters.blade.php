<div class="card border-0 shadow-sm mb-4 dashboard-filters-bar">
    <div class="card-body p-3">
        <div class="row g-3 align-items-center justify-content-between">
            {{-- Period Filter Pills --}}
            <div class="col-12 col-lg-auto">
                <div class="d-flex flex-wrap align-items-center gap-1">
                    <span class="text-muted small fw-semibold me-2 d-none d-sm-inline">
                        <i class="bi bi-calendar3 me-1"></i>{{ __('Period:') }}
                    </span>
                    <button type="button"
                            class="btn btn-sm rounded-pill px-3 transition-all"
                            :class="filters.period === 'today' ? 'btn-primary shadow-sm fw-bold' : 'btn-light text-muted'"
                            @click="setPeriod('today')">
                        {{ __('Today') }}
                    </button>
                    <button type="button"
                            class="btn btn-sm rounded-pill px-3 transition-all"
                            :class="filters.period === 'yesterday' ? 'btn-primary shadow-sm fw-bold' : 'btn-light text-muted'"
                            @click="setPeriod('yesterday')">
                        {{ __('Yesterday') }}
                    </button>
                    <button type="button"
                            class="btn btn-sm rounded-pill px-3 transition-all"
                            :class="filters.period === 'this_week' ? 'btn-primary shadow-sm fw-bold' : 'btn-light text-muted'"
                            @click="setPeriod('this_week')">
                        {{ __('This Week') }}
                    </button>
                    <button type="button"
                            class="btn btn-sm rounded-pill px-3 transition-all"
                            :class="filters.period === 'this_month' ? 'btn-primary shadow-sm fw-bold' : 'btn-light text-muted'"
                            @click="setPeriod('this_month')">
                        {{ __('This Month') }}
                    </button>
                    <button type="button"
                            class="btn btn-sm rounded-pill px-3 transition-all"
                            :class="filters.period === 'custom' ? 'btn-primary shadow-sm fw-bold' : 'btn-light text-muted'"
                            @click="filters.period = 'custom'">
                        {{ __('Custom') }}
                    </button>
                </div>
            </div>

            {{-- Branch Selector --}}
            <div class="col-12 col-lg-auto">
                <div class="d-flex align-items-center gap-2">
                    @if($canSelectAllBranches)
                        <label for="dashboard-branch-select" class="text-muted small fw-semibold text-nowrap mb-0">
                            <i class="bi bi-building me-1"></i>{{ __('Branch:') }}
                        </label>
                        <select id="dashboard-branch-select"
                                class="form-select form-select-sm rounded-pill border-light-subtle shadow-xs"
                                style="min-width: 180px;"
                                x-model="filters.branchId"
                                @change="onBranchChange($event.target.value)">
                            <option value="">{{ __('All Branches') }}</option>
                            @foreach($availableBranches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    @else
                        <div class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
                            {{ $availableBranches->first()?->name ?? __('My Branch') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Custom Date Range Expansion --}}
        <div x-show="filters.period === 'custom'" x-transition class="mt-3 pt-3 border-top">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-auto">
                    <label class="form-label small text-muted mb-1">{{ __('From Date') }}</label>
                    <input type="date" class="form-control form-control-sm rounded-3" x-model="filters.from">
                </div>
                <div class="col-12 col-sm-auto">
                    <label class="form-label small text-muted mb-1">{{ __('To Date') }}</label>
                    <input type="date" class="form-control form-control-sm rounded-3" x-model="filters.to">
                </div>
                <div class="col-12 col-sm-auto">
                    <button type="button" class="btn btn-sm btn-primary rounded-3 px-3" @click="applyCustomDate()">
                        <i class="bi bi-check2 me-1"></i>{{ __('Apply Range') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
