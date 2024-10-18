@extends('admin.layouts.app')
@section('title')
    {{ __('Admin Panel Settings Page') }}
@endsection
@section('content')
    {{-- Start breadcrumbs --}}
    <x-breadcrumb pageName="Users">
        <x-breadcrumb-item>
            <a class="active" href="{{ route('home.index') }}">{{ __('Home') }}</a>
        </x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Admin Panel Settings') }}</x-breadcrumb-item>
    </x-breadcrumb>
    {{-- End breadcrumbs --}}

    <h3 class="my-3">{{ __('Admin Panel Setting Details') }}</h3>

    <div class="card my-3">
        <div class="card-title mx-3">
            <h4>{{ __('System Settings') }}</h4>
            <div class="d-flex justify-content-end">
                @if (App\Traits\AppHelper::perUSer('users.create'))
                    <x-modal-button target="editAdminPanalSettingsModal" title="Edit">
                        <i class="fa-solid fa-file-pen me-2"></i>
                    </x-modal-button>
                @endif

                <x-modal id="editAdminPanalSettingsModal" title="Edit Admin Panal Settings">
                    <form action="{{ route('admin_panel_settings.update', ['id' => $setting->id]) }}" method="POST" id="adminPanalSettingsForm"
                        enctype="multipart/form-data">
                        @method('PUT') @csrf

                        <div class="modal-body">

                            <x-input type='text' :value="isset($setting) ? $setting->system_name : old('system_name')" label="Name" name='system_name'
                                placeholder='Name' oninput="" required />

                                <x-input type='text' :value="isset($setting) ? $setting->system_phone : old('system_phone')" label="Phone" name='system_phone'
                                    placeholder='+012345678900' id='systemPhoneInput'   oninput="this.value = this.value.replace(/[^0-9+]/g, '')" required />


                            <x-form-description value="{{ $setting->system_notes  }}" label=" Notes"
                                name='system_notes' placeholder=' Notes' />

                            <x-form-description value="{{ $setting->system_address }}"
                                label=" Address" name='system_address' placeholder=' Address' />
                                <div class="mb-3">
                                    <label for="formFile" class="form-label">Logo</label>
                                    <input class="form-control" name="system_logo" type="file" id="systemLogo">
                                  </div>
                        </div>

                        <x-modal-footer class="btn-primary"/>
                    </form>
                </x-modal>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped table-bordered my-4">
                <tr>
                    <th>{{ __('Company Logo') }}</th>
                    <td><img src="{{ !empty($setting->system_logo) ? (Storage::exists($setting->system_logo) ? Storage::url($setting->system_logo) : asset('admin-assets/assets/img/avatar.jpg')) : '' }}"
                            width="
                            100px"></td>
                </tr>
                <tr>
                    <th>{{ __('Company Name') }}</th>
                    <td>{{ $setting->system_name }}</td>
                </tr>

                <tr>
                    <th>{{ __('Company Phone') }}</th>
                    <td>{{ $setting->system_phone }}</td>
                </tr>

                <tr>
                    <th>{{ __('Company Address') }}</th>
                    <td>{{ $setting->system_address }}</td>
                </tr>

                <tr>
                    <th>{{ __('Company Notes') }}</th>
                    <td>{{ $setting->system_notes }}</td>
                </tr>


            </table>
        </div>
    </div>
@endsection
