<div class="form-group col-lg-12">
    <label class="mt-5 fs-2 bold">{{ __('Permissions') }}</label>
    <div class="table-responsive mt-2">
        <table class="table table-hover">
            <thead>
                <tr>
                    <td class="text-gray-800">{{ __('All Access') }}
                        

                            <i class="bi bi-exclamation-circle-fill ms-1" data-bs-toggle="tooltip"
                            title="{{ __('Allows a full access to the system') }}"></i>
                    </td>
                    <td>
                        <!--begin::Checkbox-->
                        <label class="form-check form-check-custom form-check-solid me-9">
                            <input class="form-check-input" type="checkbox" value="" id="roles_select_all" />
                            <span class="form-check-label" for="roles_select_all">{{ __('Select all') }}</span>
                        </label>
                        <!--end::Checkbox-->
                    </td>
                </tr>
            </thead>
            <tbody>
                @php
                    $groups = \Spatie\Permission\Models\Permission::select('group', \Illuminate\Support\Facades\DB::raw("COUNT('x')"))
                        ->groupBy('group')
                        ->orderBy(\Illuminate\Support\Facades\DB::raw("COUNT('x')"), 'DESC')
                        ->pluck('group')
                        ->toArray();

                @endphp
                @foreach ($groups as $group)
                    <!--begin::Table row-->
                    <tr>
                        <!--begin::Label-->
                        <td class="text-gray-800">
                            <label class="form-check form-check-sm form-check-custom form-check-solid me-5 me-lg-20">
                                <input class="form-check-input selectAllPermission" type="checkbox"
                                    value="{{ $group }}" id="group" />
                                <span class="form-check-label">{{ $group }}</span>
                            </label>
                        </td>
                        <!--end::Label-->
                        <!--begin::Options-->
                        @php
                            $array = isset($role)
                                ? $role->permissions->pluck('id')->toArray()
                                : (isset($user)
                                    ? $user
                                        ->permissions()
                                        ->pluck('id')
                                        ->toArray()
                                    : []);

                            $permission = \Spatie\Permission\Models\Permission::where('group', $group)
                                ->orderBY('id', 'ASC')
                                ->pluck('name', 'id')
                                ->toArray();

                        @endphp
                        @foreach ($permission as $id => $name)
                            <td>
                                <!--begin::Checkbox-->
                                <label
                                    class="form-check form-check-sm form-check-custom form-check-solid me-5 me-lg-20">
                                    <input class="form-check-input" type="checkbox" value="{{ $name }}"
                                        @if (in_array($id, $array)) checked="checked" @endif
                                        name="permissions[]" />
                                    <span class="form-check-label">{{ __($name) }}</span>
                                </label>
                                <!--end::Checkbox-->
                            </td>
                        @endforeach
                        <!--end::Options-->
                    </tr>
                    <!--end::Table row-->
                @endforeach
            </tbody>
        </table>
    </div>
</div>
