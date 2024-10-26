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
                @if (App\Traits\AppHelper::perUser('suppliers.index'))
                <ul id="products-nav" class="nav-content collapse " data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="{{ route('suppliers.index') }}">
                            <i class="bi bi-circle"></i><span>{{ __('Suppliers') }}</span>
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

        <hr>

        @if (App\Traits\AppHelper::perUser('units.index')
        || App\Traits\AppHelper::perUser('tools.index')
        || App\Traits\AppHelper::perUser('admin_panel_settings.index'))

        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#settings-nav" data-bs-toggle="collapse" href="#">
                <i class="bi bi-gear-wide-connected"></i><span>{{ __('Settings') }}</span><i
                    class="bi bi-chevron-down ms-auto"></i>
            </a>
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


 @if (App\Traits\AppHelper::perUser('roles.index')
        || App\Traits\AppHelper::perUser('users.index'))


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
