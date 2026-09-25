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
    window.__translations = @json(app()->getLocale() === 'ar' && file_exists(resource_path('lang/ar.json')) ? (json_decode(file_get_contents(resource_path('lang/ar.json')), true) ?? (object)[]) : (object)[]);
    window.__ = function(key) {
        return (window.__translations && window.__translations[key]) ? window.__translations[key] : key;
    };
    $(document).ready(function() {
        @if(app()->getLocale() === 'ar')
        if (typeof $.fn.select2 !== 'undefined') {
            $.fn.select2.defaults.set('dir', 'rtl');
        }
        if (typeof $.fn.dataTable !== 'undefined') {
            $.extend(true, $.fn.dataTable.defaults, {
                language: {
                    sEmptyTable: "ليست هناك بيانات متاحة في الجدول",
                    sLoadingRecords: "جارٍ التحميل...",
                    sProcessing: "جارٍ التحميل...",
                    sLengthMenu: "أظهر _MENU_ مدخلات",
                    sZeroRecords: "لم يعثر على أية سجلات",
                    sInfo: "إظهار _START_ إلى _END_ من أصل _TOTAL_ مدخل",
                    sInfoEmpty: "يعرض 0 إلى 0 من أصل 0 سجل",
                    sInfoFiltered: "(منتقاة من مجموع _MAX_ مُدخل)",
                    sSearch: "ابحث:",
                    oPaginate: {
                        sFirst: "الأول",
                        sPrevious: "السابق",
                        sNext: "التالي",
                        sLast: "الأخير"
                    }
                }
            });
        }
        @endif

        $('.js-example-basic-single').select2();
        $('.js-example-basic-multiple').select2();
    });
</script>
    <script src="{{ asset('admin-assets/assets/vendor/fullcalendar-6.1.15/dist/index.global.min.js') }}"></script>
 <script>

      document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            var calendar = new FullCalendar.Calendar(calendarEl, {
              initialView: 'dayGridWeek',
              locale: '{{ app()->getLocale() }}'
            });
            calendar.render();
        }
      });

    </script>
@include('sweetalert::alert')
@yield('js')
