<?php
namespace App\Http\Controllers;
use App\Models\Asset;
use App\Models\Inspection;
use App\Models\SoftwareRequest;
use App\Models\SystemDowntime;
use App\Models\Ticket;
use Illuminate\View\View;
class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index',['summary'=>[
            'tickets'=>Ticket::where('is_active',true)->count(),'open'=>Ticket::where('is_active',true)->whereNotIn('status',['resolved','verified'])->count(),
            'assets'=>Asset::where('is_active',true)->count(),'inspections_due'=>Inspection::whereDate('due_on','<=',today())->where('status','!=','completed')->count(),
            'downtimes'=>SystemDowntime::where('is_active',true)->count(),'software'=>SoftwareRequest::where('is_active',true)->count(),
        ]]);
    }
}
