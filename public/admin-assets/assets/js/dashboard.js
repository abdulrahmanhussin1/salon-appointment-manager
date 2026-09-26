/**
 * Dashboard Command Center State Manager
 * Uses Alpine.js and ApexCharts
 */

function dashboardData(config = {}) {
    return {
        // Configuration
        userRole: config.userRole || '',
        canSelectAllBranches: Boolean(config.canSelectAllBranches),
        effectiveBranchId: config.effectiveBranchId || null,

        // Filters
        filters: {
            branchId: config.effectiveBranchId || null,
            period: 'today',
            from: '',
            to: '',
            date: new Date().toISOString().slice(0, 10),
            viewBy: 'day'
        },

        // Loading states
        loading: {
            summary: true,
            revenue: true,
            appointments: true,
            staff: true,
            inventory: true,
            expenses: true,
            activity: true,
            alerts: true,
            action: false
        },

        // Error states
        errors: {
            summary: false,
            revenue: false,
            appointments: false,
            staff: false,
            inventory: false,
            expenses: false,
            activity: false,
            alerts: false
        },

        // Data containers
        data: {
            summary: {
                revenue: { total: 0, cash: 0, card: 0, services: 0, products: 0, invoice_count: 0, customer_count: 0, avg_ticket: 0 },
                expenses: { total: 0 },
                net_profit: 0,
                refunds: { total: 0, count: 0 },
                commissions: { total: 0 },
                customers: { new_today: 0, total_active: 0 },
                appointments: { total: 0, requested: 0, confirmed: 0, checked_in: 0, in_service: 0, completed: 0, cancelled: 0, no_show: 0 },
                top_employee: null,
                top_service: null
            },
            revenue: { labels: [], services: [], products: [], total: [] },
            appointments: [],
            statusBreakdown: { requested: 0, confirmed: 0, checked_in: 0, in_service: 0, completed: 0, cancelled: 0, no_show: 0 },
            staff: [],
            inventory: { out_of_stock: [], low_stock: [], out_of_stock_count: 0, low_stock_count: 0 },
            expenses: { total: 0, by_category: [] },
            activity: [],
            alerts: []
        },

        // Charts instances
        charts: {
            revenue: null,
            appointmentDonut: null,
            expenseDonut: null
        },

        // Mobile FAB State
        fabOpen: false,

        // Status filter for appointments table
        appointmentTableFilter: 'all',

        get filteredAppointments() {
            if (this.appointmentTableFilter === 'all') {
                return this.data.appointments;
            }
            return this.data.appointments.filter(a => a.status === this.appointmentTableFilter);
        },

        // Init
        init() {
            this.loadFromUrl();
            this.fetchAll();
            this.setupLazyObservers();
        },

        // Load params from URL
        loadFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const branchParam = params.get('branch_id');
            if (branchParam !== null && branchParam !== '') {
                this.filters.branchId = branchParam;
            }
            if (params.get('period')) {
                this.filters.period = params.get('period');
            }
            if (params.get('from')) {
                this.filters.from = params.get('from');
            }
            if (params.get('to')) {
                this.filters.to = params.get('to');
            }
        },

        // Update URL state
        updateUrl() {
            const params = new URLSearchParams();
            if (this.filters.branchId) {
                params.set('branch_id', this.filters.branchId);
            }
            if (this.filters.period) {
                params.set('period', this.filters.period);
            }
            if (this.filters.period === 'custom') {
                if (this.filters.from) params.set('from', this.filters.from);
                if (this.filters.to) params.set('to', this.filters.to);
            }
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, '', newUrl);
        },

        // Change branch
        onBranchChange(newBranchId) {
            this.filters.branchId = newBranchId;
            this.updateUrl();
            this.fetchAll();
        },

        // Change period
        setPeriod(newPeriod) {
            this.filters.period = newPeriod;
            this.updateUrl();
            this.fetchAll();
        },

        applyCustomDate() {
            if (this.filters.from && this.filters.to) {
                this.filters.period = 'custom';
                this.updateUrl();
                this.fetchAll();
            }
        },

        // Fetch All Widgets
        fetchAll() {
            // Priority 1: Core operational and KPI summary
            Promise.all([
                this.fetchSummary(),
                this.fetchAppointments(),
                this.fetchAlerts()
            ]).then(() => {
                // Priority 2: Revenue and Inventory
                return Promise.all([
                    this.fetchRevenue(),
                    this.fetchInventory()
                ]);
            }).then(() => {
                // Priority 3: Staff, Expenses, Activity
                this.fetchStaff();
                this.fetchExpenses();
                this.fetchActivity();
            });
        },

        getQueryParams() {
            return {
                branch_id: this.filters.branchId,
                period: this.filters.period,
                from: this.filters.from,
                to: this.filters.to,
                date: this.filters.date
            };
        },

        async request(url, params = {}) {
            const query = new URLSearchParams();
            for (const key in params) {
                if (params[key] !== null && params[key] !== undefined && params[key] !== '') {
                    query.append(key, params[key]);
                }
            }
            const fullUrl = query.toString() ? `${url}?${query.toString()}` : url;
            const res = await fetch(fullUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });
            if (!res.ok) throw new Error(`HTTP error ${res.status}`);
            return await res.json();
        },

        async postRequest(url, data = {}) {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(data)
            });
            const json = await res.json().catch(() => ({}));
            if (!res.ok) {
                const message = json.message || Object.values(json.errors || {})[0]?.[0] || `HTTP error ${res.status}`;
                const err = new Error(message);
                err.response = json;
                throw err;
            }
            return json;
        },

        // Fetch Summary
        async fetchSummary() {
            this.loading.summary = true;
            this.errors.summary = false;
            try {
                const res = await this.request('/api/dashboard/summary', this.getQueryParams());
                if (res.data) {
                    this.data.summary = res.data;
                }
            } catch (err) {
                console.error('Summary error:', err);
                this.errors.summary = true;
            } finally {
                this.loading.summary = false;
            }
        },

        // Fetch Revenue Trend
        async fetchRevenue() {
            this.loading.revenue = true;
            this.errors.revenue = false;
            try {
                const res = await this.request('/api/dashboard/revenue', this.getQueryParams());
                if (res.data) {
                    this.data.revenue = res.data;
                    this.$nextTick(() => {
                        this.renderRevenueChart();
                    });
                }
            } catch (err) {
                console.error('Revenue error:', err);
                this.errors.revenue = true;
            } finally {
                this.loading.revenue = false;
            }
        },

        // Fetch Appointments
        async fetchAppointments() {
            this.loading.appointments = true;
            this.errors.appointments = false;
            try {
                const res = await this.request('/api/dashboard/appointments', this.getQueryParams());
                if (res.data) {
                    this.data.appointments = res.data.appointments || [];
                    this.data.statusBreakdown = res.data.status_breakdown || {};
                    this.$nextTick(() => {
                        this.renderAppointmentChart();
                    });
                }
            } catch (err) {
                console.error('Appointments error:', err);
                this.errors.appointments = true;
            } finally {
                this.loading.appointments = false;
            }
        },

        // Fetch Staff
        async fetchStaff() {
            this.loading.staff = true;
            this.errors.staff = false;
            try {
                const res = await this.request('/api/dashboard/staff', this.getQueryParams());
                if (res.data) {
                    this.data.staff = res.data || [];
                }
            } catch (err) {
                console.error('Staff error:', err);
                this.errors.staff = true;
            } finally {
                this.loading.staff = false;
            }
        },

        // Fetch Inventory Alerts
        async fetchInventory() {
            this.loading.inventory = true;
            this.errors.inventory = false;
            try {
                const res = await this.request('/api/dashboard/inventory-alerts', { branch_id: this.filters.branchId });
                if (res.data) {
                    this.data.inventory = res.data;
                }
            } catch (err) {
                console.error('Inventory error:', err);
                this.errors.inventory = true;
            } finally {
                this.loading.inventory = false;
            }
        },

        // Fetch Expenses
        async fetchExpenses() {
            this.loading.expenses = true;
            this.errors.expenses = false;
            try {
                const res = await this.request('/api/dashboard/expenses', this.getQueryParams());
                if (res.data) {
                    this.data.expenses = res.data;
                    this.$nextTick(() => {
                        this.renderExpenseChart();
                    });
                }
            } catch (err) {
                console.error('Expenses error:', err);
                this.errors.expenses = true;
            } finally {
                this.loading.expenses = false;
            }
        },

        // Fetch Activity
        async fetchActivity() {
            this.loading.activity = true;
            this.errors.activity = false;
            try {
                const res = await this.request('/api/dashboard/activity', { branch_id: this.filters.branchId, limit: 15 });
                if (res.data) {
                    this.data.activity = res.data || [];
                }
            } catch (err) {
                console.error('Activity error:', err);
                this.errors.activity = true;
            } finally {
                this.loading.activity = false;
            }
        },

        // Fetch Alerts
        async fetchAlerts() {
            this.loading.alerts = true;
            this.errors.alerts = false;
            try {
                const res = await this.request('/api/dashboard/alerts', { branch_id: this.filters.branchId });
                if (res.data) {
                    this.data.alerts = res.data || [];
                }
            } catch (err) {
                console.error('Alerts error:', err);
                this.errors.alerts = true;
            } finally {
                this.loading.alerts = false;
            }
        },

        // Appointment Lifecycle Actions
        async confirmAppointment(id) {
            await this.performAppointmentAction(`/api/appointments/${id}/confirm`);
        },

        async checkInAppointment(id) {
            await this.performAppointmentAction(`/api/appointments/${id}/check-in`);
        },

        async startServiceAppointment(id) {
            await this.performAppointmentAction(`/api/appointments/${id}/start-service`);
        },

        async completeAppointment(id) {
            await this.performAppointmentAction(`/api/appointments/${id}/complete`);
        },

        async noShowAppointment(id) {
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: this.t('Mark as No Show?'),
                    text: this.t('Are you sure you want to mark this appointment as No Show?'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#212529',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: this.t('Yes, mark No Show')
                });
                if (!result.isConfirmed) return;
            } else {
                if (!confirm(this.t('Are you sure you want to mark this appointment as No Show?'))) return;
            }
            await this.performAppointmentAction(`/api/appointments/${id}/no-show`);
        },

        async cancelAppointment(id) {
            let reason = '';
            if (typeof Swal !== 'undefined') {
                const result = await Swal.fire({
                    title: this.t('Cancel Appointment'),
                    input: 'textarea',
                    inputLabel: this.t('Cancellation Reason'),
                    inputPlaceholder: this.t('Please enter cancellation reason...'),
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: this.t('Confirm Cancellation'),
                    inputValidator: (value) => {
                        if (!value || !value.trim()) {
                            return this.t('A cancellation reason is required!');
                        }
                    }
                });
                if (!result.isConfirmed || !result.value) return;
                reason = result.value;
            } else {
                reason = prompt(this.t('Please enter cancellation reason:'));
                if (!reason) return;
            }
            await this.performAppointmentAction(`/api/appointments/${id}/cancel`, { cancellation_reason: reason });
        },

        async performAppointmentAction(url, payload = {}) {
            this.loading.action = true;
            try {
                const response = await this.postRequest(url, payload);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: this.t('Success'),
                        text: response.message || this.t('Status updated successfully'),
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
                await this.fetchAppointments();
                await this.fetchSummary();
                await this.fetchAlerts();
            } catch (err) {
                const msg = err.message || this.t('Action could not be completed. Please try again.');
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: this.t('Error'),
                        text: msg
                    });
                } else {
                    alert(msg);
                }
                console.error('Action error:', err);
            } finally {
                this.loading.action = false;
            }
        },

        // Charts Rendering
        isRtl() {
            return document.documentElement.dir === 'rtl' || document.documentElement.getAttribute('lang') === 'ar';
        },

        t(key) {
            return typeof window.__ === 'function' ? window.__(key) : key;
        },

        renderRevenueChart() {
            const el = document.querySelector('#revenue-trend-chart');
            if (!el || typeof ApexCharts === 'undefined') return;

            const isRtl = this.isRtl();
            const rev = this.data.revenue;

            const options = {
                chart: {
                    type: 'area',
                    height: 320,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    zoom: { enabled: false },
                    animations: { enabled: true, easing: 'easeinout', speed: 600 }
                },
                series: [
                    { name: this.t('Total Revenue'), data: rev.total || [] },
                    { name: this.t('Services'), data: rev.services || [] },
                    { name: this.t('Products'), data: rev.products || [] }
                ],
                xaxis: {
                    categories: rev.labels || [],
                    labels: { style: { colors: '#6c757d', fontSize: '12px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false }
                },
                yaxis: {
                    labels: {
                        formatter: (val) => Number(val).toLocaleString() + ' ' + this.t('EGP'),
                        style: { colors: '#6c757d', fontSize: '12px' }
                    }
                },
                colors: ['#4154f1', '#2eca6a', '#ff771d'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.45,
                        opacityTo: 0.05,
                        stops: [20, 100]
                    }
                },
                stroke: { curve: 'smooth', width: [3, 2, 2] },
                dataLabels: { enabled: false },
                grid: { borderColor: '#f1f1f1', strokeDashArray: 4 },
                tooltip: {
                    y: {
                        formatter: (val) => Number(val).toLocaleString(undefined, { minimumFractionDigits: 2 }) + ' ' + this.t('EGP')
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: isRtl ? 'right' : 'left',
                    fontFamily: 'inherit',
                    markers: { radius: 12 }
                }
            };

            if (this.charts.revenue) {
                this.charts.revenue.updateOptions(options);
            } else {
                this.charts.revenue = new ApexCharts(el, options);
                this.charts.revenue.render();
            }
        },

        renderAppointmentChart() {
            const el = document.querySelector('#appointment-status-chart');
            if (!el || typeof ApexCharts === 'undefined') return;

            const sb = this.data.statusBreakdown;
            const series = [
                sb.requested || 0,
                sb.confirmed || 0,
                sb.checked_in || 0,
                sb.in_service || 0,
                sb.completed || 0,
                sb.cancelled || 0,
                sb.no_show || 0
            ];

            const total = series.reduce((a, b) => a + b, 0);

            const options = {
                chart: {
                    type: 'donut',
                    height: 280,
                    fontFamily: 'inherit'
                },
                series: series,
                labels: [
                    this.t('Requested'),
                    this.t('Confirmed'),
                    this.t('Checked In'),
                    this.t('In Service'),
                    this.t('Completed'),
                    this.t('Cancelled'),
                    this.t('No Show')
                ],
                colors: ['#0d6efd', '#0dcaf0', '#ffc107', '#fd7e14', '#198754', '#dc3545', '#6c757d'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '72%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: this.t('Total'),
                                    formatter: () => total
                                }
                            }
                        }
                    }
                },
                legend: {
                    position: 'bottom',
                    fontFamily: 'inherit',
                    markers: { radius: 6 }
                },
                dataLabels: { enabled: false },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: { height: 240 },
                        legend: { position: 'bottom' }
                    }
                }]
            };

            if (this.charts.appointmentDonut) {
                this.charts.appointmentDonut.updateOptions(options);
            } else {
                this.charts.appointmentDonut = new ApexCharts(el, options);
                this.charts.appointmentDonut.render();
            }
        },

        renderExpenseChart() {
            const el = document.querySelector('#expense-category-chart');
            if (!el || typeof ApexCharts === 'undefined') return;

            const cats = this.data.expenses.by_category || [];
            const series = cats.map(c => Number(c.total));
            const labels = cats.map(c => c.category);

            if (series.length === 0) {
                series.push(1);
                labels.push(this.t('No Expenses'));
            }

            const options = {
                chart: {
                    type: 'donut',
                    height: 260,
                    fontFamily: 'inherit'
                },
                series: series,
                labels: labels,
                colors: ['#4154f1', '#2eca6a', '#ff771d', '#e03e2d', '#7030a0', '#17a2b8', '#6c757d'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: this.t('Total Expenses'),
                                    formatter: () => this.formatCurrency(this.data.expenses.total)
                                }
                            }
                        }
                    }
                },
                legend: { position: 'bottom', fontFamily: 'inherit' },
                dataLabels: { enabled: false }
            };

            if (this.charts.expenseDonut) {
                this.charts.expenseDonut.updateOptions(options);
            } else {
                this.charts.expenseDonut = new ApexCharts(el, options);
                this.charts.expenseDonut.render();
            }
        },

        setupLazyObservers() {
            if (!('IntersectionObserver' in window)) return;
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const section = entry.target.dataset.lazySection;
                        if (section === 'staff' && !this.data.staff.length) this.fetchStaff();
                        if (section === 'activity' && !this.data.activity.length) this.fetchActivity();
                        observer.unobserve(entry.target);
                    }
                });
            }, { rootMargin: '150px' });

            document.querySelectorAll('[data-lazy-section]').forEach(el => observer.observe(el));
        },

        // Formatters
        formatCurrency(amount) {
            const num = Number(amount || 0);
            return num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + this.t('EGP');
        },

        formatNumber(num) {
            return Number(num || 0).toLocaleString();
        }
    };
}
