<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureOption;
use App\Services\AuditService;
use App\Services\FeatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeatureController extends Controller
{
    public function index(): View
    {
        return view('admin.features.index', ['features'=>FeatureOption::orderBy('label')->get()]);
    }

    public function update(Request $request, FeatureOption $feature, FeatureService $features, AuditService $audit): RedirectResponse
    {
        $old=$feature->enabled;
        $feature->update(['enabled'=>$request->boolean('enabled'),'updated_by'=>$request->user()->id]);
        $features->flush($feature->key);
        $audit->record('FEATURE_SETTING_CHANGED',$feature,['enabled'=>$old],['enabled'=>$feature->enabled]);
        return back()->with('success','Feature option updated.');
    }
}
