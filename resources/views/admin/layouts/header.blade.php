<!-- ======= Header ======= -->
<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('home.index') }}" class="logo d-flex align-items-center">
            <img style="max-height:50px"
                src="{{ !empty($adminPanelSetting->system_logo) ? (Storage::exists($adminPanelSetting->system_logo) ? Storage::url($adminPanelSetting->system_logo) : asset('admin-assets/assets/img/avatar.jpg')) : '' }}"
                alt="">
            <span class="d-none d-lg-block">{{ $adminPanelSetting->system_name }}</span>
        </a>
        {{-- <i class="bi bi-list toggle-sidebar-btn"></i> --}}
    </div><!-- End Logo -->


    {{-- <div class="search-bar">
        <form class="search-form d-flex align-items-center" method="POST" action="#">
            <input type="text" name="query" placeholder="Search" title="Enter search keyword">
            <button type="submit" title="Search"><i class="bi bi-search"></i></button>
        </form>
    </div><!-- End Search Bar --> --}}

    @include('admin.layouts.navbar')

    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">

            <!-- Language Switcher in Header/Navbar -->
            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center px-2 py-1 rounded border bg-light text-dark text-decoration-none shadow-sm" href="#"
                    data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('Switch Language') }}" style="cursor: pointer; font-size: 0.875rem;">
                    @if(app()->getLocale() === 'ar')
                        <span class="fs-6 me-1">🇸🇦</span>
                        <span class="d-none d-sm-inline fw-bold text-secondary">العربية</span>
                    @else
                        <span class="fs-6 me-1">🇬🇧</span>
                        <span class="d-none d-sm-inline fw-bold text-secondary">English</span>
                    @endif
                    <i class="bi bi-chevron-down ms-1 text-muted" style="font-size: 11px;"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow py-1 shadow" style="min-width: 150px;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center py-2 px-3 {{ app()->getLocale() === 'en' ? 'active fw-bold' : '' }}" href="{{ route('lang.switch', 'en') }}">
                            <span class="me-2 fs-6">🇬🇧</span>
                            <span>English</span>
                            @if(app()->getLocale() === 'en')
                                <i class="bi bi-check2 ms-auto text-success fw-bold"></i>
                            @endif
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center py-2 px-3 {{ app()->getLocale() === 'ar' ? 'active fw-bold' : '' }}" href="{{ route('lang.switch', 'ar') }}">
                            <span class="me-2 fs-6">🇸🇦</span>
                            <span>العربية</span>
                            @if(app()->getLocale() === 'ar')
                                <i class="bi bi-check2 ms-auto text-success fw-bold"></i>
                            @endif
                        </a>
                    </li>
                </ul>
            </li><!-- End Language Switcher -->

            <li class="nav-item dropdown pe-3">

                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#"
                    data-bs-toggle="dropdown">
                    <img src="@if (Auth()->user()->photo && (Storage::disk('public')->exists(Auth()->user()->photo) || Storage::exists(Auth()->user()->photo))) {{ asset('storage') . '/' . Auth::user()->photo }} @else {{ asset('admin-assets/assets/img/avatar.jpg') }} @endif"
                        alt="Profile" class="rounded-circle">
                    <span class="d-none d-md-block dropdown-toggle ps-2">{{ Str::ucfirst(Auth::user()->name) }}</span>
                </a><!-- End Profile Iamge Icon -->

                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6>{{ Str::ucfirst(auth()->user()->name) }}</h6>
                        <span>{{ auth()->user()->email }}</span>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="{{ route('profile.index') }}">
                            <i class="bi bi-person"></i>
                            <span>{{ __('My Profile') }}</span>
                        </a>
                    </li>
              
                    <li>
                        <hr class="dropdown-divider">
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'en' ? 'fw-bold text-primary' : '' }}" href="{{ route('lang.switch', 'en') }}">
                            <i class="bi bi-translate"></i>
                            <span>English</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center {{ app()->getLocale() === 'ar' ? 'fw-bold text-primary' : '' }}" href="{{ route('lang.switch', 'ar') }}">
                            <i class="bi bi-translate"></i>
                            <span>العربية</span>
                        </a>
                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    <li>
                        <a class="dropdown-item d-flex align-items-center"
                            onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                style="display: none;">
                                @csrf
                            </form>
                            <i class="bi bi-box-arrow-right"></i>
                            <span>{{ __('Sign Out') }}</span>
                        </a>
                    </li>

                </ul><!-- End Profile Dropdown Items -->
            </li><!-- End Profile Nav -->

        </ul>
    </nav><!-- End Icons Navigation -->

</header>
<!-- End Header -->
