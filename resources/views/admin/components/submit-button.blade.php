@props(['label','id'=>false,'hidden'=>false,'disabled'=>false])
<button type="submit" class="btn btn-dark  btn-sm" @if($id)id="{{ $id }}"@endif  @if($hidden) hidden @endif @if($disabled) disabled @endif>{{Str::ucfirst( __($label)) }}</button>
