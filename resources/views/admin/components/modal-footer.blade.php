@props(['class' => 'btn-success','id'=>false])

<div class="modal-footer">
    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">{{__('Close')}}</button>
    <button type="submit" class="btn {{ $class }} btn-sm" @if ($id) id="{{ $id }}" @endif >{{__('Confirm')}}</button>
</div>
