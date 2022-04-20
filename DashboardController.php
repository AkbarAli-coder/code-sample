<?php

namespace App\Http\Controllers\Backend;

use App\Models\Invoice;

use Illuminate\Http\Request;
use Illuminate\Auth\Access\Gate;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
//     public function __invoke(Request $request)
//     {
// //        Gate::authorize('admin-dashboard');
// //        return view('backend.dashboard');
//     }

    public function index (Request $request)
    {
        // Gate::authorize('admin.dashboard');
        // $today = now();
        // $date = Invoice::with('doctor','user','patient')
        // ->Where('date', $today)
        // ->selectRaw("SUM(fee) as total_fee")
        // // ->selectRaw("SUM(credit) as total_credit")
        // ->groupBy('doctor_id')
        // ->get();

        $date = Invoice::groupBy('doctor_id')
        ->selectRaw('sum(fee) as sum, doctor_id')
        ->get();
        $today = Invoice::groupBy('doctor_id')
        ->whereDate('Created_at',today())
        ->selectRaw('sum(fee) as sum, doctor_id')
        ->get();
// return $date;
        $invoices = Invoice::with('doctor','user','patient')->latest()->get();
        return view('backend.dashboard',compact('invoices','date','today'));
    }


    public function docprint (Request $request, $id)
    {

        $today = Invoice::groupBy('doctor_id')
        ->where('doctor_id',$id)
        ->whereDate('Created_at',today())
        ->selectRaw('sum(fee) as sum, doctor_id')
        ->get();
        // return $today;

        return view('backend.invoices.docprint',compact('today'));
        // return view('backend.invoices.test');
    }


}
