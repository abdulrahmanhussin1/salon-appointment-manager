{{-- Desktop Quick Actions Bar --}}
<div class="d-none d-md-flex flex-wrap align-items-center gap-2 mb-4 quick-actions-container">
    <span class="text-muted small fw-semibold me-1">
        <i class="bi bi-lightning-charge-fill text-warning me-1"></i>{{ __('Quick Actions:') }}
    </span>

    @if(\App\Traits\AppHelper::perUser('appointments.create'))
        <a href="{{ route('home.calender') }}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-xs d-flex align-items-center gap-1">
            <i class="bi bi-calendar-plus"></i>
            <span>{{ __('New Appointment') }}</span>
        </a>
    @endif

    @if(\App\Traits\AppHelper::perUser('sales_invoices.create'))
        <a href="{{ route('sales_invoices.create') }}" class="btn btn-sm btn-success rounded-pill px-3 shadow-xs d-flex align-items-center gap-1">
            <i class="bi bi-receipt"></i>
            <span>{{ __('New Sales Invoice') }}</span>
        </a>
    @endif

    @if(\App\Traits\AppHelper::perUser('customers.create'))
        <a href="{{ route('customers.create') }}" class="btn btn-sm btn-info text-white rounded-pill px-3 shadow-xs d-flex align-items-center gap-1">
            <i class="bi bi-person-plus"></i>
            <span>{{ __('Add Customer') }}</span>
        </a>
    @endif

    @if(\App\Traits\AppHelper::perUser('expenses.create'))
        <a href="{{ route('expenses.create') }}" class="btn btn-sm btn-outline-danger rounded-pill px-3 shadow-xs d-flex align-items-center gap-1">
            <i class="bi bi-cash-coin"></i>
            <span>{{ __('Record Expense') }}</span>
        </a>
    @endif

    @if(\App\Traits\AppHelper::perUser('inventories.create') || \App\Traits\AppHelper::perUser('inventory_transactions.adjustView'))
        <a href="{{ route('inventory_transactions.adjustView') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 shadow-xs d-flex align-items-center gap-1">
            <i class="bi bi-sliders"></i>
            <span>{{ __('Stock Adjustment') }}</span>
        </a>
    @endif
</div>

{{-- Mobile Floating Action Button (FAB) --}}
<div class="d-md-none position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div class="d-flex flex-column align-items-end gap-2 mb-2" x-show="fabOpen" x-transition @click.away="fabOpen = false">
        @if(\App\Traits\AppHelper::perUser('appointments.create'))
            <a href="{{ route('home.calender') }}" class="btn btn-primary rounded-pill shadow-lg d-flex align-items-center gap-2 py-2 px-3">
                <span>{{ __('New Appointment') }}</span>
                <i class="bi bi-calendar-plus fs-6"></i>
            </a>
        @endif

        @if(\App\Traits\AppHelper::perUser('sales_invoices.create'))
            <a href="{{ route('sales_invoices.create') }}" class="btn btn-success rounded-pill shadow-lg d-flex align-items-center gap-2 py-2 px-3">
                <span>{{ __('New Invoice') }}</span>
                <i class="bi bi-receipt fs-6"></i>
            </a>
        @endif

        @if(\App\Traits\AppHelper::perUser('customers.create'))
            <a href="{{ route('customers.create') }}" class="btn btn-info text-white rounded-pill shadow-lg d-flex align-items-center gap-2 py-2 px-3">
                <span>{{ __('Add Customer') }}</span>
                <i class="bi bi-person-plus fs-6"></i>
            </a>
        @endif
    </div>

    <button type="button"
            class="btn btn-primary rounded-circle shadow-lg d-flex align-items-center justify-content-center"
            style="width: 56px; height: 56px;"
            @click="fabOpen = !fabOpen"
            aria-label="{{ __('Toggle Quick Actions') }}">
        <i class="bi fs-4" :class="fabOpen ? 'bi-x-lg' : 'bi-plus-lg'"></i>
    </button>
</div>
