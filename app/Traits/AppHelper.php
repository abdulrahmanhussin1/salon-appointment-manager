<?php

namespace App\Traits;


use App\Models\CoreGeoCity;
use App\Models\CoreGeoState;
use App\Models\CoreGeoCountry;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;




trait AppHelper
{
    public static function markOpened($model)
    {
        $model->update([
            'opened_by' => auth()->id() ?? 1,
            'opened_at' => now(),
        ]);
    }

    public static function perUser($permission)
    {
        return (auth()->check()) ? auth()->user()->can($permission) : false;
    }

    // public static function exportFileSettings($fileType,$modelName,$data,$columns)
    // {
    //     if ($fileType == 'excel') {
    //         $fileName = $modelName . '_' . date('Y-m-d') . '.xlsx';
    //         return Excel::download(new DataTableExport($data, $columns), $fileName, \Maatwebsite\Excel\Excel::XLSX);
    //     } elseif ($fileType == 'pdf') {
    //         $fileName = $modelName . '_' . date('Y-m-d') . '.pdf';
    //         return Excel::download(new DataTableExport($data, $columns), $fileName, \Maatwebsite\Excel\Excel::DOMPDF);
    //     } elseif ($fileType == 'csv') {
    //         $fileName = $modelName . '_' . date('Y-m-d') . '.csv';
    //         return Excel::download(new DataTableExport($data, $columns), $fileName, \Maatwebsite\Excel\Excel::CSV);
    //     }
    // }

    public static function groupedPermissions()
    {
        $permissions = Permission::select('id', 'name')->distinct();

        // Group permissions by 'group' field
        $groups = $permissions->select('group', \Illuminate\Support\Facades\DB::raw("COUNT('x')"))
            ->groupBy('group')
            ->orderBy(DB::raw("COUNT('x')"), 'DESC')
            ->pluck('group')
            ->toArray();

        $groupedPermissions = [];
        foreach ($groups as $group) {
            $groupedPermissions[$group] = Permission::where('group', $group)
                ->get()
                ->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'func' => ucfirst(substr($permission->name, strpos($permission->name, '.') + 1)),
                        'group' => $permission->group,
                    ];
                })->toArray();
        }

        return $groupedPermissions;
    }
}
