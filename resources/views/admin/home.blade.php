@extends('admin.layouts.app')

@section('title', __('Business Command Center'))

@section('css')
<style>
    /* Modern Dashboard Styling & Design Tokens */
    .dashboard-container {
        font-family: inherit;
    }
    .kpi-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        border-radius: 12px;
        background: #ffffff;
    }
    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
    }
    .tracking-wider {
        letter-spacing: 0.05em;
    }
    .very-small {
        font-size: 0.75rem;
    }
    .btn-xs {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
    }
    .shadow-xs {
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    .shadow-2xs {
        box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.03);
    }
    .hover-primary:hover {
        color: #4154f1 !important;
    }

    /* Skeleton Loading Animation */
    .skeleton-line {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 4px;
    }
    @keyframes shimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* Spinning Utility */
    .spin-infinite {
        display: inline-block;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        100% { transform: rotate(360deg); }
    }

    /* Timeline styling */
    .timeline-list {
        border-left: 2px solid #e9ecef;
    }
    [dir="rtl"] .timeline-list {
        border-left: none;
        border-right: 2px solid #e9ecef;
        padding-left: 0 !important;
        padding-right: 1.5rem !important;
    }
    [dir="rtl"] .rtl-flip {
        transform: scaleX(-1);
    }
</style>
@endsection

@section('content')
<div class="dashboard-container"
     x-data="dashboardData({
         userRole: '{{ auth()->user()->roles->first()?->name ?? '' }}',
         canSelectAllBranches: {{ $canSelectAllBranches ? 'true' : 'false' }},
         effectiveBranchId: {{ $effectiveBranchId ? $effectiveBranchId : 'null' }}
     })">

    {{-- Breadcrumbs --}}
    <x-breadcrumb pageName="{{ __('Command Center') }}">
        <x-breadcrumb-item active>{{ __('Dashboard') }}</x-breadcrumb-item>
    </x-breadcrumb>

    {{-- Zone 1: Header --}}
    @include('admin.components.dashboard.header')

    {{-- Zone 2: Filters Bar --}}
    @include('admin.components.dashboard.filters')

    {{-- Zone 3: Quick Actions & Mobile FAB --}}
    @include('admin.components.dashboard.quick-actions')

    {{-- Zone 4: Key Performance Indicators (KPIs) --}}
    <section aria-label="{{ __('Key Performance Indicators') }}" aria-live="polite">
        @include('admin.components.dashboard.kpi-grid')
    </section>

    {{-- Zone 5: Operational Status & Today's Appointments --}}
    <section aria-label="{{ __('Operational Status') }}">
        @include('admin.components.dashboard.operational-status')
    </section>

    {{-- Zone 6: Revenue Trend Line Chart --}}
    @include('admin.components.dashboard.revenue-trend')

    {{-- Zone 7: Branch & Expense Profitability Analysis (Role-gated) --}}
    @include('admin.components.dashboard.branch-expense')

    {{-- Zone 8: Staff Performance Table --}}
    @include('admin.components.dashboard.staff-performance')

    {{-- Zone 9: Critical Stock Alerts & Action Required --}}
    @include('admin.components.dashboard.alerts-inventory')

    {{-- Zone 10: Recent Activity Feed --}}
    @include('admin.components.dashboard.recent-activity')

</div>
@endsection

@section('js')
<script src="{{ asset('admin-assets/assets/vendor/alpinejs/alpine.min.js') }}" defer></script>
<script src="{{ asset('admin-assets/assets/js/dashboard.js') }}"></script>
<script>
    // Live Clock Updater
    function updateDashboardClock() {
        const el = document.getElementById('dashboard-live-clock');
        if (!el) return;
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        el.textContent = now.toLocaleDateString('{{ app()->getLocale() === 'ar' ? 'ar-EG' : 'en-US' }}', options);
    }
    setInterval(updateDashboardClock, 60000);
</script>
@endsection
