<?php

namespace App\Http\Controllers\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\DataTables\UsersDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UsersDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.users.index');
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.pages.users.create_edit');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        $photo = NULL;
        if ($request->hasFile('photo')) {
            $photo = Storage::putFileAs("uploads/images/users", $request->photo,now()->format('Y-m-d').'_'.str_replace(' ','_',$request->name).'_photo.'. $request->photo->getClientOriginalExtension());
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'photo' => $photo,
            'status' => $request->status,
            'created_by' => auth()->id(),
        ]);

        $role = Role::find($request->role_id);
        if ($role) {
            $user->assignRole($role);
            $permissions = $role->permissions->pluck('id')->merge($request->permissions);
            $user->syncPermissions($permissions);
        }

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $roles = Role::select('id','name')->get();
        return view('admin.pages.users.create_edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        $photo = $user->photo;
        if ($request->hasFile('photo')) {
            if ($user->photo && Storage::exists($user->photo))
            {
                Storage::delete($user->photo);
            }
            $photo = Storage::putFileAs("uploads/images/users", $request->photo,now()->format('Y-m-d').'_'.str_replace(' ','_',$request->name).'_photo.'. $request->photo->getClientOriginalExtension());
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password ?  Hash::make($request->password) : $user->password,
            'photo' => $photo,
            'status' => $request->status,
            'updated_by' => auth()->id(),
        ]);
        $role = Role::find($request->role_id);
        if ($role) {
            $user->syncRoles([$role]);
            $permissions = $role->permissions->pluck('id')->merge($request->permissions);
            $user->syncPermissions($permissions);
        }
        Alert::success(__('Success'), __('Updated Successfully'));
        return redirect()->route('users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        if ($user->photo && Storage::exists($user->photo)) {
            Storage::delete($user->photo);
        }
        $user->syncRoles([]);
        $user->syncPermissions([]);  
        $user->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
