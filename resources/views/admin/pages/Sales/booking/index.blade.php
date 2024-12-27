@extends('admin.layouts.app')
@section('title')
    Book Appointment
@endsection
@section('content')
    <div class="card">
        <div class="card-body">
            <div id='calendar' class="m-2">

            </div>
        </div>
    </div>
@endsection
@section('js')
<script>

var calendar = new FullCalendar.Calendar(calendarEl, {
  initialView: 'resourceDayGridDay',
  resources: [
    // your list of resources
  ]
});
</script>
@endsection
