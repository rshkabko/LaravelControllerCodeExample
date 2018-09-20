<?php
namespace App\Http\Controllers\Admin\GasRequest;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\GasRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Mail\GasRequest as GasRequestMail;
use Illuminate\Support\Facades\Mail;

class GasRequestController extends Controller
{
    /**
     * Display a listing of the gas requests.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Sorting requests by date of creation
        $gasRequests = GasRequest::orderBy('created_at', 'desc')->get();

        return view('admin.gas_requests.index', [
            'gasRequests' => $gasRequests,
            "user" => Auth::user()
        ]);
    }

    /**
     * Show the form for creating a gas request.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.gas_requests.create', [
            "users" => User::all(),
            "months" => GasRequest::getAllMonths()
        ]);
    }

    /**
     * Store a newly created gas request in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Check out the sent form
        $validator = Validator::make($request->all(), [
            'request_date' => 'required|date',
            'month' => 'integer|required|max:12',
            'planned_volume' => 'numeric|required|min:1',
            'price' => 'numeric|required|min:1',
        ]);

        // If the validation is not passed, then we make a redirect to the page of the request update and send there a list of errors.
        if ($validator->fails()) {
            return redirect('/admin/gas_requests/create/')
                ->withInput()
                ->withErrors($validator);
        }
        // Creating a date object
        $carbonRequestDate = new Carbon($request->request_date);

        // Creating a new request
        $newRequest = new GasRequest();
        $newRequest->request_date = $carbonRequestDate;
        $newRequest->month = $request->month;
        $newRequest->planned_volume = $request->planned_volume;
        $newRequest->price  = $request->price;
        $newRequest->user_id = $request->user_id;
        $newRequest->comment = $request->comment;
        $newRequest->fio = $request->fio;
        $newRequest->email = $request->email;
        $newRequest->phone = $request->phone;
        $newRequest->payment_schedule = $request->payment_schedule;
        $newRequest->save();

        return redirect('/admin/gas_requests/');
    }

    /**
     * Show the form for editing the specified gas request.
     *
     * @param  \App\Models\GasRequest  $gasRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(GasRequest $gasRequest)
    {
        return view('admin.gas_requests.edit', [
            "request" => $gasRequest,
            "users" => User::all(),
            "month" => GasRequest::getAllMonths()
        ]);
    }

    /**
     * Update the specified gas request in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        // Check out the sent form
        $validator = Validator::make($request->all(), [
            'month' => 'integer|required|max:12',
            'planned_volume' => 'numeric|required|min:1',
            'price' => 'numeric|required|min:1',
        ]);

        // If the validation is not passed, then we make a redirect to the page of the request update and send there a list of errors.
        if ($validator->fails()) {
            return redirect('/admin/gas_requests/'.$id.'/edit/')
                ->withInput()
                ->withErrors($validator);
        }

        // Find an request for id and update it
        $gasRequest = GasRequest::find($id);
        $gasRequest->month = $request->month;
        $gasRequest->planned_volume = $request->planned_volume;
        $gasRequest->price  = $request->price;
        $gasRequest->comment = $request->comment;
        $gasRequest->fio = $request->fio;
        $gasRequest->email = $request->email;
        $gasRequest->phone = $request->phone;
        $gasRequest->payment_schedule = $request->payment_schedule;
        $gasRequest->save();

        // Retrieve the client who created the request
        $gasRequestUser = User::find($gasRequest->user_id);
        
        // Sending a message about updating a client's request
        Mail::to($gasRequestUser->email)
            ->send(new GasRequestMail(
                $gasRequest,
                'edit_admin_for_user',
                'Вашу заявку на газ обработали на сайте '.env("APP_NAME"),
                ''));

        return redirect('/admin/');

    }

    /**
     * Remove the specified gas request from storage.
     *
     * @param  \App\Models\GasRequest  $gasRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(GasRequest $gasRequest)
    {
        // Delete the selected request
        $gasRequest->forceDelete();

        // Send a message to the user about the removal of his request
        Mail::to($gasRequest->user->email)
            ->send(new GasRequestMail(
                $gasRequest,
                'delete',
                'Удалили заявку на газ на сайте '.env("APP_NAME"),
                ''));

        return redirect('/admin/gas_requests/');
    }
}
