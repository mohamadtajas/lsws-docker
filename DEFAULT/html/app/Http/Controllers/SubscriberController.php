<?php

namespace App\Http\Controllers;

use App\Http\Requests\Subsciber\StoreRequest;
use App\Models\Subscriber;

class SubscriberController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_subscribers'])->only('index');
        $this->middleware(['permission:delete_subscriber'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $subscribers = Subscriber::orderBy('created_at', 'desc')->paginate(15);
        return view('backend.marketing.subscribers.index', compact('subscribers'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $subscriber = Subscriber::where('email', $request->email)->first();
        if ($subscriber == null) {
            $subscriber = new Subscriber;
            $subscriber->email = $request->email;
            $subscriber->save();
            flash(translate('You have subscribed successfully'))->success();
        } else {
            flash(translate('You are  already a subscriber'))->success();
        }
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        Subscriber::destroy($id);
        flash(translate('Subscriber has been deleted successfully'))->success();
        return redirect()->route('subscribers.index');
    }
}
