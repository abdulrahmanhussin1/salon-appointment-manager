<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait AppHelper
{
    public static function perUser($permission)
    {
        return (auth()->check()) ? auth()->user()->can($permission) : false;
    }

    public static function handleFileUpload($request, $fileKey, $directory, $existingFile = null)
    {
        if ($request->hasFile($fileKey)) {
            $disk = Storage::disk('public');
            if ($existingFile && $disk->exists($existingFile)) {
                $disk->delete($existingFile);
            }

            return $disk->putFileAs($directory, $request->file($fileKey), now()->format('Y-m-d').'_'.str_replace(' ', '_', $request->name)."_{$fileKey}.".$request->file($fileKey)->getClientOriginalExtension());
        }

        return $existingFile;
    }
}
