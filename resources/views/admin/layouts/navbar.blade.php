    <nav class="header-nav m-auto">
        <ul class="d-flex  justify-content-between">

            @if (App\Traits\AppHelper::perUser('sales_invoices.index') ||
                    App\Traits\AppHelper::perUser('sales_invoices.create') ||
                    App\Traits\AppHelper::perUser('customers.index'))

                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-receipt"></i>
                        <span class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Sales')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow sales">
                        @if (App\Traits\AppHelper::perUser('sales_invoices.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('sales_invoices.index') }}">
                                    <i class=" ri-folder-open-fill"></i><span>{{ Str::ucfirst(__('invoices')) }}</span>
                                </a>
                            </li>
                        @endif
                        @if (App\Traits\AppHelper::perUser('sales_invoices.create'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('sales_invoices.create') }}">
                                    <i class="bi bi-receipt"></i><span>{{ Str::ucfirst(__('new invoices')) }}</span>
                                </a>
                            </li>
                        @endif

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        @if (App\Traits\AppHelper::perUser('customers.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('customers.index') }}">
                                    <i
                                        class="bi bi-person-fill-down"></i><span>{{ Str::ucfirst(__('customers')) }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif






            @if (App\Traits\AppHelper::perUser('suppliers.index') || App\Traits\AppHelper::perUser('purchase_invoices.index'))
                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-person-fill-down"></i>
                        <span class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Purchases')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow Purchases">

                        @if (App\Traits\AppHelper::perUser('purchase_invoices.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('purchase_invoices.index') }}">
                                    <i
                                        class="bi bi-receipt"></i><span>{{ Str::ucfirst(__('purchase invoices')) }}</span>
                                </a>
                            </li>
                        @endif
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        @if (App\Traits\AppHelper::perUser('suppliers.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('suppliers.index') }}">
                                    <i
                                        class="bi bi-person-fill-down"></i><span>{{ Str::ucfirst(__('suppliers')) }}</span>
                                </a>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif

            @if (App\Traits\AppHelper::perUser('inventories.index'))
                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-houses-fill"></i>
                        <span
                            class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Inventories')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow inventories">
                        @if (App\Traits\AppHelper::perUser('inventories.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('inventories.index') }}">
                                    <i class="bi bi-houses-fill"></i>
                                    <span>{{ Str::ucfirst(__('inventories')) }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif


            @if (App\Traits\AppHelper::perUser('service_categories.index') ||
                    App\Traits\AppHelper::perUser('services.index') ||
                    App\Traits\AppHelper::perUser('product_categories.index') ||
                    App\Traits\AppHelper::perUser('products.index'))

                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-box-seam-fill"></i>
                        <span
                            class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Services & Products')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow services">
                        @if (App\Traits\AppHelper::perUser('service_categories.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('service_categories.index') }}">
                                    <i
                                        class="ri-folder-open-fill"></i><span>{{ Str::ucfirst(__('service categories')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('services.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('services.index') }}">
                                    <i class="bi bi-box-seam-fill"></i><span>{{ Str::ucfirst(__('services')) }}</span>
                                </a>
                            </li>
                        @endif

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        @if (App\Traits\AppHelper::perUser('product_categories.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('product_categories.index') }}">
                                    <i
                                        class="ri-folder-open-fill"></i><span>{{ Str::ucfirst(__('product categories')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('products.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('products.index') }}">
                                    <i class="bi bi-tags-fill"></i><span>{{ Str::ucfirst(__('products')) }}</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if (App\Traits\AppHelper::perUser('expense_types.index') || App\Traits\AppHelper::perUser('expenses.index'))
                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-cash-stack"></i>
                        <span class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Expenses')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow expenses">

                        @if (App\Traits\AppHelper::perUser('expense_types.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('expense_types.index') }}">
                                    <i
                                        class="ri-folder-open-fill"></i><span>{{ Str::ucfirst(__('expense types')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('expenses.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('expenses.index') }}">
                                    <i class="bi bi-cash-stack"></i><span>{{ Str::ucfirst(__('expenses')) }}</span>
                                </a>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif

            @if (App\Traits\AppHelper::perUser('employees.index') || App\Traits\AppHelper::perUser('employee_levels.index'))
                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-people-fill"></i>
                        <span class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Employees')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow employees">

                        @if (App\Traits\AppHelper::perUser('employee_levels.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('employee_levels.index') }}">
                                    <i
                                        class=" ri-user-star-fill"></i><span>{{ Str::ucfirst(__('employee levels')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('employees.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('employees.index') }}">
                                    <i class="bi bi-people-fill"></i><span>{{ Str::ucfirst(__('employees')) }}</span>
                                </a>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif

            @if (App\Traits\AppHelper::perUser('roles.index') || App\Traits\AppHelper::perUser('users.index'))

                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-people-fill"></i>
                        <span
                            class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Users & Roles')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow users">

                        @if (App\Traits\AppHelper::perUser('roles.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('roles.index') }}">
                                    <i class=" ri-shield-user-fill"></i><span>{{ Str::ucfirst(__('roles')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('users.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('users.index') }}">
                                    <i class="bi bi-people-fill"></i><span>{{ Str::ucfirst(__('users')) }}</span>
                                </a>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif

            @if (App\Traits\AppHelper::perUser('units.index') ||
                    App\Traits\AppHelper::perUser('tools.index') ||
                    App\Traits\AppHelper::perUser('admin_panel_settings.index') ||
                    App\Traits\AppHelper::perUser('branches.index') ||
                    App\Traits\AppHelper::perUser('payment_methods.index'))
                <li class="nav-item dropdown mx-3">
                    <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-gear-wide-connected"></i>
                        <span class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(_('Settings')) }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow settings">

                        @if (App\Traits\AppHelper::perUser('branches.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('branches.index') }}">
                                    <i class="bi bi-house-fill"></i><span>{{ Str::ucfirst(__('Branches')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('units.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('units.index') }}">
                                    <i class=" ri-scales-2-fill"></i><span>{{ Str::ucfirst(__('units')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('tools.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('tools.index') }}">
                                    <i class=" ri-tools-fill"></i><span>{{ Str::ucfirst(__('tools')) }}</span>
                                </a>
                            </li>
                        @endif

                        @if (App\Traits\AppHelper::perUser('admin_panel_settings.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('admin_panel_settings.index') }}">
                                    <i
                                        class="bi bi-gear-wide-connected"></i><span>{{ Str::ucfirst(__('admin panel settings')) }}</span>
                                </a>
                            </li>
                        @endif
                        @if (App\Traits\AppHelper::perUser('payment_methods.index'))
                            <li class="">
                                <a class="dropdown-item d-flex align-items-center"
                                    href="{{ route('payment_methods.index') }}">
                                    <i
                                        class="bi bi-credit-card-2-back-fill"></i><span>{{ Str::ucfirst(__('payment methods')) }}</span>
                                </a>
                            </li>
                        @endif

                    </ul>
                </li>
            @endif


        </ul>
    </nav>
