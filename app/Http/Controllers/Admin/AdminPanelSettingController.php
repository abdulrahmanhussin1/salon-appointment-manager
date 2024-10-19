<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\AdminPanelSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use App\Http\Requests\AdminPanelSettingRequest;

class AdminPanelSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $excludedColumns = ['created_at', 'created_by','opened_at','opened_by','deleted_at','deleted_by'];
        $columns = Schema::getColumnListing('admin_panel_settings');
        $selectedColumns = array_diff($columns, $excludedColumns);


        $setting = AdminPanelSetting::select($selectedColumns)
            //->where('company_id', auth()->user()->company_id)
            ->first();

        return view('admin.pages.settings.admin_panel_settings.index', compact('setting'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AdminPanelSettingRequest $request, $id)
    {
        $setting = AdminPanelSetting::findOrFail($id);
        if ($request->hasFile(key: 'system_logo')) {
            if ($setting->system_logo && Storage::exists($setting->system_logo)) {
                Storage::delete($setting->system_logo);
            }
            $newLogoPath = Storage::putFile('uploads/images/settings', $request->file('system_logo'));
        } else {
            $newLogoPath = $setting->system_logo;
        }
        $setting->update([
            'system_name'    => $request->input('system_name'),
            'system_phone'   => $request->input('system_phone'),
            'system_notes'   => $request->input('system_notes'),
            'system_address' => $request->input('system_address'),
            'system_logo'    => $newLogoPath,
            'updated_by'     => auth()->id(),
        ]);
        Alert::success(__('Settings have been updated successfully.'));
        return redirect()->route('admin_panel_settings.index');
    }

}
