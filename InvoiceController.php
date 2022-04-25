<?php

namespace App\Http\Controllers\Backend;

use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use RealRashid\SweetAlert\Facades\Alert;


class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        Gate::authorize('admin.invoices.index');
        $invoices = Invoice::with('doctor','creator','patient','editor','destroyer')->latest()->get();

 
        return view('backend.invoices.index',compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        Gate::authorize('admin.invoices.create');
        $doctors = Doctor::all();

        return view('backend.invoices.form',compact('doctors'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Gate::authorize('admin.invoices.create');

        $this->validate($request,[
            'doctor_id' => 'required',
            'fee'=>'required',
            'patname'=>'required',
            'pat_gen'=>'required',
            // 'pat_contact'=>'required|unique:patients',

        ]);
        \DB::transaction(function() use ($request) {

         Patient::create([

             'patname' => $request->patname,
             'pat_age' => $request->pat_age,
            'pat_contact' => $request->pat_contact,
             'pat_add' => $request->pat_add,
             'pat_gen' => $request->pat_gen,
         ]);
        $lastpatid = DB::getPdo()->lastInsertId();

         $date = Carbon::now('Asia/Dhaka');

         $dat = $date->format('Ymd');
         $today = date('d-m-Y');


            $today = date('d-m-Y');


            $lastsl = Invoice::query()->whereDate('created_at', Carbon::today())->whereDoctor_id($request->doctor_id)->latest()->first('doc_sl');
            $docsl = $lastsl ? $lastsl->doc_sl + 1 : 1;





         $userid = Auth::user()->id;
         $user = Invoice::create([
            //  'invprefix' => $dat,
             'doctor_id' => $request->doctor_id,
             'doc_sl' => $docsl,
             'fee' => $request->fee,
             'fdhcharge' => $request->fee *15 / 100,
             'date' => $date,
             'patient_id' => $lastpatid,
            //  'user_id' => $userid,
         ]);
         $lastinvid = DB::getPdo()->lastInsertId();
            $inv = $dat.''.$lastinvid;
            Invoice::find($lastinvid)->update([
                'inv_no' => $inv,
            ]);
            
         notify()->success('Doctor Sl Successfully Added.', 'Added');

    });
         return redirect()->route('admin.reports.userdashboard');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        Gate::authorize('admin.invoices.edit');
        $doctors = Doctor::all();
        $invoice = Invoice::find($id);

        

        return view('backend.invoices.editform', compact('invoice','doctors'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    

        $invoice = Invoice::findorfail($id);

        DB::table('patients')
        ->where('patid', $request->patid)
        ->update([
            'patname' => $request->patname,
            'pat_age' => $request->pat_age,
           'pat_contact' => $request->pat_contact,
            'pat_add' => $request->pat_add,
            'pat_gen' => $request->pat_gen,
        ]);

        $lastsl = Invoice::query()->whereDate('created_at', Carbon::today())->whereDoctor_id($request->doctor_id)->latest()->first('doc_sl');
        $docsl = $lastsl ? $lastsl ->doc_sl + 1 : 1;
        $invoice->update([
            'doctor_id' => $request->doctor_id,
            'fee' => $request->fee,
            'fdhcharge' => $request->fee * 15 / 100,
        ]);
        return redirect()->route('admin.invoices.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Gate::authorize('admin.invoices.destroy');
        $invoice = Invoice::find($id);
        $invoice->delete();
        notify()->success("Invoice Successfully Deleted", "Deleted");
        return back();
    }

    public function deletedinv()
    {
//        $invoices = DB::table('invoices')
//            ->join('doctors', 'invoices.doctors_id', '=', 'doctors.id')
//            ->select('invoices.*', 'doctors.*')
////            ->where('invoices.')
//            ->get();

        $invoices = Invoice::onlyTrashed()->get();
        return view('backend.invoices.deleteinv',compact('invoices'));
    }

    public function restore ($id)
    {
        Gate::authorize('admin.invoices.destroyRestore');
        
    //  dd($id);

        $blog = Invoice::onlyTrashed()->findOrFail($id);

        $blog->restore();

notify()->success('Invoice restore  Successfully restore.', 'Restore');
        return redirect()->route('admin.invoices.index');

    }

    public function print($id)
    {


        $print = Invoice::with('doctor','user','patient')->find($id);
//        return $print;

        return view('backend.invoices.print',compact('print'));


    }

    public function pdelete($id)
    {
        Gate::authorize('admin.invoices.destroyPermanently');
        
        $blog = Invoice::onlyTrashed()->findOrFail($id);

        $blog->forceDelete();

        notify()->success('Invoice Permanent Delete  Successfully .', 'Delete');
        return redirect()->route('admin.invoices.index');


    }
public function frompatientstore(Request $request)
{

    // return $request->all();
    $this->validate($request,[
        'doctor_id' => 'required',
        'patname'=>'required',
        'pat_age'=>'required',
        'pat_gen'=>'required',
        // 'pat_contact'=>'required|unique:patients',

    ]);
    \DB::transaction(function() use ($request) {


    // $date = Carbon::now('Asia/Dhaka');

    // $dat = $date->format('Ymd');


    // $lastsl = Invoice::query()->whereDate('created_at', Carbon::today())->whereDoctor_id($request->doctor_id)->latest()->first('doc_sl');
    // $docsl = $lastsl ? $lastsl ->doc_sl + 1 : 1;

    // $userid = Auth::user()->id;
    // $user = Invoice::create([
    //     'invprefix' => $dat,
    //     'doctor_id' => $request->doctor_id,
    //     'doc_sl' => $docsl,
    //     'fee' => $request->fee,
    //     'date' => $date,
    //     'patient_id' => $request->patid,
    //     'user_id' => $userid,
    // ]);
// just edit

$date = Carbon::now('Asia/Dhaka');

         $dat = $date->format('Ymd');
         $today = date('d-m-Y');


            $today = date('d-m-Y');


            $lastsl = Invoice::query()->whereDate('created_at', Carbon::today())->whereDoctor_id($request->doctor_id)->latest()->first('doc_sl');
            $docsl = $lastsl ? $lastsl ->doc_sl + 1 : 1;





         $userid = Auth::user()->id;
         $user = Invoice::create([
            //  'invprefix' => $dat,
             'doctor_id' => $request->doctor_id,
             'doc_sl' => $docsl,
             'fee' => $request->fee,
             'fdhcharge' => $request->fee * 15 / 100,
             'date' => $date,
             'patient_id' => $request->patid,
            //  'user_id' => $userid,
         ]);
         $lastinvid = DB::getPdo()->lastInsertId();
            $inv = $dat.''.$lastinvid;
            Invoice::find($lastinvid)->update([
                'inv_no' => $inv,
            ]);
            Alert::success('Success Title', 'Success Message');
        //  notify()->success('Doctor Sl Successfully Added.', 'Added');



    notify()->success('Doctor Sl Successfully Added.', 'Added');
});
         return redirect()->route('admin.reports.userdashboard');

}

}
