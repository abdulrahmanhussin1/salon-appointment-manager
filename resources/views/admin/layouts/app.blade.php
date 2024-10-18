<!DOCTYPE html>
<html lang="en">
@include('admin.layouts.head')

<body>
    @include('admin.layouts.header')
    @include('admin.layouts.sidebar')
    <main id="main" class="main">

        @yield('content')

    </main>
    <!-- End #main -->

    @include('admin.layouts.footer')
    <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i
            class="bi bi-arrow-up-short"></i></a>

    @include('admin.layouts.scripts')

</body>

</html>
