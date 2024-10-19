  <!-- Vendor JS Files -->
  <script src="{{asset('admin-assets')}}/assets/js/jQuery.min.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/chart.js/chart.umd.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/echarts/echarts.min.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/quill/quill.min.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="{{asset('admin-assets')}}/assets/vendor/php-email-form/validate.js"></script>
  <script src="{{asset('admin-assets')}}/assets/js/jQuery-validation.min.js"></script>
  <!-- Template Main JS File -->
  <script src="{{asset('admin-assets')}}/assets/js/main.js"></script>
  <script src="{{ asset('admin-assets') }}/assets/vendor/datatable/js/jquery.dataTables.min.js"></script>

  <!-- DataTables Buttons -->
  <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.print.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>




  <script src="{{ asset('admin-assets/assets/vendor/select2-4.1.0-rc.0/dist/js/select2.full.min.js') }}"></script>
  <script src="{{ asset('vendor/sweetalert/sweetalert.all.js') }}"></script>
  <script>
    $(document).ready(function() {
        $('.js-example-basic-single').select2();
    });
</script>
<script>
    $(document).ready(function() {
        $('.js-example-basic-multiple').select2();
    });
</script>
@include('sweetalert::alert')
@yield('js')
