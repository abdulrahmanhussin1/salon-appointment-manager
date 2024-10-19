<?php

namespace App\Http\Controllers\Admin;

use App\Models\Tool;
use Illuminate\Http\Request;
use App\DataTables\ToolDataTable;
use App\Http\Requests\ToolRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use RealRashid\SweetAlert\Facades\Alert;

class ToolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(ToolDataTable $dataTable)
    {
        return $dataTable->render('admin.pages.settings.tools.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort(404);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ToolRequest $request)
    {
        $image = NULL;
        if ($request->hasFile('image')) {
            $image = Storage::putFileAs("uploads/images/tools", $request->image,now()->format('Y-m-d').'_'.str_replace(' ','_',$request->name).'_image.'. $request->image->getClientOriginalExtension());
        }

        Tool::create([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'image'=> $image,
            'created_by'=> auth()->id(),
        ]);

        Alert::success(__('Success'), __('Created Successfully'));
        return redirect()->back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Tool $tool)
    {
        abort(404);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tool $tool)
    {
        return view('admin.pages.settings.tools.edit', compact('tool'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ToolRequest $request, Tool $tool)
    {
        $image = $tool->image;
        if ($request->hasFile('image')) {
            if ($tool->image && Storage::exists($tool->image))
            {
                Storage::delete($tool->image);
            }
            $image = Storage::putFileAs("uploads/images/tools", $request->image,now()->format('Y-m-d').'_'.str_replace(' ','_',$request->name).'_image.'. $request->image->getClientOriginalExtension());
        }

        $tool->update([
            'name'=> $request->name,
            'description'=> $request->description,
            'status'=> $request->status,
            'image'=> $image,
            'updated_by'=> auth()->id(),
            ]);

            Alert::success(__('Success'), __('Updated Successfully'));
            return redirect()->route('tools.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tool $tool)
    {
        // Delete tool Image if exists
        if ($tool->image && Storage::exists($tool->image)) {
            Storage::delete($tool->image);
        }
        $tool->delete();
        Alert::success(__('Success'), __('Deleted Successfully'));
    }
}
