<div class="card border-0 shadow-sm mb-4 recent-activity-card" data-lazy-section="activity">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h5 class="card-title m-0 fs-6 fw-bold text-dark">
                    <i class="bi bi-activity text-primary me-2"></i>{{ __('Recent Business Activity') }}
                </h5>
                <span class="text-muted very-small">{{ __('Unified live audit log of operational and financial transactions') }}</span>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-1" @click="fetchActivity()">
                <i class="bi bi-arrow-repeat me-1" :class="{'spin-infinite': loading.activity}"></i>
                <span class="very-small">{{ __('Sync') }}</span>
            </button>
        </div>

        {{-- Loading Spinner --}}
        <div x-show="loading.activity" class="text-center py-4">
            <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
            <p class="text-muted small mt-2 mb-0">{{ __('Aggregating activity events...') }}</p>
        </div>

        {{-- Activity Timeline --}}
        <div x-show="!loading.activity" class="activity-timeline" style="max-height: 420px; overflow-y: auto;">
            <div class="timeline-list position-relative ps-3 ps-md-4 py-2">
                <template x-for="(event, idx) in data.activity" :key="event.type + '-' + idx">
                    <div class="timeline-item d-flex align-items-start gap-3 mb-3 position-relative">
                        {{-- Icon Indicator --}}
                        <div class="timeline-badge rounded-circle d-flex align-items-center justify-content-center shadow-xs flex-shrink-0"
                             :class="{
                                 'bg-success bg-opacity-10 text-success': event.type === 'invoice_created',
                                 'bg-danger bg-opacity-10 text-danger': event.type === 'invoice_voided',
                                 'bg-primary bg-opacity-10 text-primary': event.type === 'appointment_completed',
                                 'bg-info bg-opacity-10 text-info': event.type === 'customer_registered',
                                 'bg-warning bg-opacity-10 text-warning': event.type === 'stock_adjusted',
                                 'bg-danger bg-opacity-10 text-danger': event.type === 'refund_issued'
                             }"
                             style="width: 34px; height: 34px;">
                            <i class="bi"
                               :class="{
                                   'bi-receipt': event.type === 'invoice_created',
                                   'bi-x-circle': event.type === 'invoice_voided',
                                   'bi-check2-circle': event.type === 'appointment_completed',
                                   'bi-person-plus': event.type === 'customer_registered',
                                   'bi-sliders': event.type === 'stock_adjusted',
                                   'bi-arrow-counterclockwise': event.type === 'refund_issued'
                               }"></i>
                        </div>

                        {{-- Event Body --}}
                        <div class="timeline-content flex-grow-1 border-bottom pb-2">
                            <div class="d-flex flex-column flex-sm-row justify-content-sm-between align-items-sm-center gap-1">
                                <a :href="event.url || '#'" class="fw-semibold text-dark text-decoration-none small hover-primary" x-text="event.title"></a>
                                <small class="text-muted very-small text-nowrap" x-text="event.time_ago"></small>
                            </div>
                            <p class="text-muted small mb-0 mt-1" x-text="event.description"></p>
                        </div>
                    </div>
                </template>

                <div x-show="(!data.activity || data.activity.length === 0) && !loading.activity" class="text-center py-4 text-muted small">
                    {{ __('No recent activities recorded today') }}
                </div>
            </div>
        </div>
    </div>
</div>
