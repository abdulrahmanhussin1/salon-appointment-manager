<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

        <li class="nav-item">
            <a class="nav-link collapsed" href="{{ route('home.index') }}">
                <i class="bi bi-grid-fill"></i>
                <span>{{ __('Dashboard') }}</span>
            </a>
        </li>

        <!-- End Dashboard Nav -->
        <hr>

        @if (App\Traits\AppHelper::perUser('sales_invoices.create'))
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('sales_invoices.create') }}">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>{{ __('New Sales Invoices') }}</span>
                </a>
            </li>
        @endif

        @if (App\Traits\AppHelper::perUser('customers.index'))
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('customers.index') }}">
                    <i class="bi bi-person-fill-up"></i>
                    <span>{{ __('Customers') }}</span>
                </a>
            </li>
        @endif


        @if (App\Traits\AppHelper::perUser('inventories.index'))
            <li class="nav-item">
                <a class="nav-link collapsed" href="{{ route('inventories.index') }}">
                    <i class="bi bi-houses-fill"></i>
                    <span>{{ __('Inventories') }}</span>
                </a>
            </li>
        @endif




@if (App\Traits\AppHelper::perUser('sales_invoices.index') || App\Traits\AppHelper::perUser('sales_invoices.create'))

<li class="nav-item">
    <a class="nav-link collapsed" data-bs-target="#sales-nav" data-bs-toggle="collapse" href="#">
       <i class="bi bi-person-fill-down"></i><span>{{ __('Sales Invoices') }}</span><i
            class="bi bi-chevron-down ms-auto"></i>
    </a>
    @if (App\Traits\AppHelper::perUser('sales_invoices.index'))
        <ul id="sales-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
            <li>
                <a href="{{ route('sales_invoices.index') }}">
                    <i class="bi bi-circle"></i><span>{{ __('Invoices') }}</span>
                </a>
            </li>
        </ul>
    @endif

     @if (App\Traits\AppHelper::perUser('sales_invoices.create'))
        <ul id="sales-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
            <li>
                <a href="{{ route('sales_invoices.create') }}">
                    <i class="bi bi-circle"></i><span>{{ __('New Invoices') }}</span>
                </a>
            </li>
        </ul>
    @endif
</li>

@endif


@if (App\Traits\AppHelper::perUser('suppliers.index') || App\Traits\AppHelper::perUser('purchase_invoices.index'))

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#suppier-nav" data-bs-toggle="collapse" href="#">
                   <i class="bi bi-person-fill-down"></i><span>{{ __('Suppliers') }}</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                @if (App\Traits\AppHelper::perUser('suppliers.index'))
                    <ul id="suppier-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('suppliers.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Suppliers') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif

                 @if (App\Traits\AppHelper::perUser('purchase_invoices.index'))
                    <ul id="suppier-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('purchase_invoices.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Purchase Invoices') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif
            </li>

        @endif


 @if (App\Traits\AppHelper::perUser('service_categories.index') || App\Traits\AppHelper::perUser('services.index'))

            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#services-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-box-seam-fill"></i><span>{{ __('Services') }}</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                @if (App\Traits\AppHelper::perUser('service_categories.index'))
                    <ul id="services-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('service_categories.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Service Categories') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif

                @if (App\Traits\AppHelper::perUser('services.index'))
                    <ul id="services-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('services.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Services') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif
            </li>
        @endif



         @if (App\Traits\AppHelper::perUser('expense_types.index') || App\Traits\AppHelper::perUser('expenses.index'))
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#expense-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-cash-stack"></i><span>{{ __('Expenses') }}</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                @if (App\Traits\AppHelper::perUser('expense_types.index'))
                    <ul id="expense-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('expense_types.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Expense Types') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif
                @if (App\Traits\AppHelper::perUser('expenses.index'))
                    <ul id="expense-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('expenses.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Expenses') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif
            </li>

        @endif



        @if (App\Traits\AppHelper::perUser('product_categories.index') || App\Traits\AppHelper::perUser('products.index'))


            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#products-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-tags-fill"></i><span>{{ __('Products') }}</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                @if (App\Traits\AppHelper::perUser('product_categories.index'))
                    <ul id="products-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('product_categories.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Product Categories') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif
                @if (App\Traits\AppHelper::perUser('products.index'))
                    <ul id="products-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('products.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Products') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif

            </li>
        @endif

        @if (App\Traits\AppHelper::perUser('employees.index') || App\Traits\AppHelper::perUser('employee_levels.index'))
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#employees-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-people-fill"></i><span>{{ __('Employees') }}</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>

                @if (App\Traits\AppHelper::perUser('employee_levels.index'))
                    <ul id="employees-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('employee_levels.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Employee Levels') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif

                @if (App\Traits\AppHelper::perUser('employees.index'))
                    <ul id="employees-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('employees.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Employees') }}</span>
                            </a>
                        </li>

                    </ul>
                @endif
            </li>
        @endif



        <hr>

        @if (App\Traits\AppHelper::perUser('units.index') ||
                App\Traits\AppHelper::perUser('tools.index') ||
                App\Traits\AppHelper::perUser('admin_panel_settings.index') ||
                App\Traits\AppHelper::perUser('branches.index') ||
                App\Traits\AppHelper::perUser('payment_methods.index')
                )


            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-gear-wide-connected"></i><span>{{ __('Settings') }}</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                @if (App\Traits\AppHelper::perUser('branches.index'))
                    {{-- branches --}}
                    <ul id="settings-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('branches.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Branches') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif

                @if (App\Traits\AppHelper::perUser('payment_methods.index'))
                    {{-- payment_methods --}}
                    <ul id="settings-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('payment_methods.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Payment Methods') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif


                @if (App\Traits\AppHelper::perUser('units.index'))
                    {{-- units --}}
                    <ul id="settings-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('units.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Units') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif

                @if (App\Traits\AppHelper::perUser('tools.index'))
                    {{-- tools --}}

                    <ul id="settings-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('tools.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Tools') }}</span>
                            </a>
                        </li>

                    </ul>
                @endif
                @if (App\Traits\AppHelper::perUser('admin_panel_settings.index'))
                    {{-- admin_panel_settings --}}


                    <ul id="settings-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                        <li>
                            <a href="{{ route('admin_panel_settings.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Admin Panel Settings') }}</span>
                            </a>
                        </li>
                    </ul>
                @endif


            </li>
        @endif

        <!-- End settings Nav -->
        @if (App\Traits\AppHelper::perUser('roles.index') || App\Traits\AppHelper::perUser('users.index'))
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#users-nav" data-bs-toggle="collapse" href="#">
                    <i class="bi bi-people-fill"></i><span>{{ __('Users & Roles') }}</span><i
                        class="bi bi-chevron-down ms-auto"></i>
                </a>
                <ul id="users-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    @if (App\Traits\AppHelper::perUser('roles.index'))
                        <li>
                            <a href="{{ route('roles.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Roles') }}</span>
                            </a>
                        </li>
                    @endif
                    @if (App\Traits\AppHelper::perUser('users.index'))
                        <li>
                            <a href="{{ route('users.index') }}">
                                <i class="bi bi-circle"></i><span>{{ __('Users') }}</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </li><!-- End Forms Nav -->
        @endif

    </ul>

</aside>
<!-- End Sidebar-->
