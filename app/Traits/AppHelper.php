<?php

namespace App\Traits;


use App\Models\CoreGeoCity;
use App\Models\CoreGeoState;
use App\Models\CoreGeoCountry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;




trait AppHelper
{


    public static function perUser($permission)
    {
        return (auth()->check()) ? auth()->user()->can($permission) : false;
    }
    public static function handleFileUpload($request, $fileKey, $directory, $existingFile = null)
    {
        if ($request->hasFile($fileKey)) {
            if ($existingFile && Storage::exists($existingFile)) {
                Storage::delete($existingFile);
            }
            return Storage::putFileAs($directory, $request->file($fileKey), now()->format('Y-m-d') . '_' . str_replace(' ', '_', $request->name) . "_{$fileKey}." . $request->file($fileKey)->getClientOriginalExtension());
        }
        return $existingFile;
    }

}


