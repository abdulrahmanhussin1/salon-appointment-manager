<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\HasBranchFilter;
use Illuminate\Http\Request;

class HomePageController extends Controller
{
    use HasBranchFilter;

    /**
     * Display the main dashboard command center.
     */
    public function index(Request $request)
    {
        $canSelectAllBranches = $this->canAccessAllBranches();
        $availableBranches = $this->getAvailableBranches();
        $effectiveBranchId = $this->getEffectiveBranchId($request->input('branch_id'));

        return view('admin.home', compact(
            'canSelectAllBranches',
            'availableBranches',
            'effectiveBranchId'
        ));
    }
}
