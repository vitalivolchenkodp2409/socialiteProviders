<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Session;
use Auth;
use App\User;
use App\Zero;
use App\One;
use App\Two;
use App\Three;
use App\Four;
use App\Five;
use Illuminate\Http\Request;
use App\Mail\AfterDubFromSubmitByAdvance;
use Illuminate\Support\Facades\Mail;


class TwosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $keyword = $request->get('search');
        $perPage = 25;

        if (!empty($keyword)) {
            $twos = Two::where('privacy', 'LIKE', "%$keyword%")
                ->orWhere('type', 'LIKE', "%$keyword%")
                ->orWhere('number', 'LIKE', "%$keyword%")
                ->latest()->paginate($perPage);
        } else {
            $twos = Two::latest()->paginate($perPage);
        }

        return view('twos.index', compact('twos'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $user_id = Auth::id();
        $count = Two::where('user_id', $user_id)->count();
        if ($count == 0) {
            return view('twos.create');
        }else{
            $two = Two::where('user_id', $user_id)->first();
            return redirect(route('twos.show', array('id' => $two->id)));
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'g-recaptcha-response' => 'required|captcha'
        ]);
            
        $requestData = $request->all();
        $user = Auth::user();
        $user_id = $user->id;

        $number = $request->number;
        if ($request->privacy == "Anonymous") {

            if ($request->type == "Audio") {
                $point = $number*0.1;
            }elseif ($request->type == "Video") {
                $point = $number*0.3;
            }

        }elseif ($request->privacy == "Raw") {

            if ($request->type == "Audio") {
                $point = $number*0.3;
            }elseif ($request->type == "Video") {
                $point = $number*0.6;
            }
            
        }
        $point = round($point, 2);
        $two = Two::create($requestData + ['user_id' => $user_id, 'point' => $point, 'ip' => $request->ip()]);

        $this->recount();

        Session::flash('flash_message','Date successfully added.');

        Mail::to($user->email)->send(new AfterDubFromSubmitByAdvance($two));

        return redirect(route('twos.show', array('id' => $two->id)));
    }

    public function recount()
    {
        $user = Auth::user();
        if($user->type == 'advance'){
            if((Zero::where('user_id', $user->id)->count() == 1) && (One::where('user_id', $user->id)->count() == 1) && (Two::where('user_id', $user->id)->count() == 1) && (Three::where('user_id', $user->id)->count() == 1) && (Four::where('user_id', $user->id)->count() == 1) && (Five::where('user_id', $user->id)->count() == 1) ){


                $point = One::where('user_id', $user->id)->first()->point + Two::where('user_id', $user->id)->first()->point + Three::where('user_id', $user->id)->first()->point + Four::where('user_id', $user->id)->first()->point + Five::where('user_id', $user->id)->first()->point;

                $update_user = User::find($user->id);
                $update_user->point = $point;
                $update_user->is_locked = 1;
                $update_user->save();

            }else{
                $update_user = User::find($user->id);
                $update_user->point = 0;
                $update_user->is_locked = 0;
                $update_user->save();
            }
        }else{
            if((Zero::where('user_id', $user->id)->count() == 1) && (One::where('user_id', $user->id)->count() == 1) && (Two::where('user_id', $user->id)->count() == 1) && (Three::where('user_id', $user->id)->count() == 1) ){


                $point = One::where('user_id', $user->id)->first()->point + Two::where('user_id', $user->id)->first()->point + Three::where('user_id', $user->id)->first()->point;

                $update_user = User::find($user->id);
                $update_user->point = $point;
                $update_user->is_locked = 1;
                $update_user->save();

            }else{
                $update_user = User::find($user->id);
                $update_user->point = 0;
                $update_user->is_locked = 0;
                $update_user->save();
            }
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        $two = Two::findOrFail($id);

        return view('twos.show', compact('two'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $two = Two::findOrFail($id);

        return view('twos.edit', compact('two'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int  $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'g-recaptcha-response' => 'required|captcha'
        ]);
            
        $requestData = $request->all();
        $two = Two::findOrFail($id);
        $last_point = $two->point;
        $user_id = Auth::user()->id;
        $user = Auth::user();

        $number = $request->number;
        if ($request->privacy == "Anonymous") {

            if ($request->type == "Audio") {
                $point = $number*0.1;
            }elseif ($request->type == "Video") {
                $point = $number*0.3;
            }

        }elseif ($request->privacy == "Raw") {

            if ($request->type == "Audio") {
                $point = $number*0.3;
            }elseif ($request->type == "Video") {
                $point = $number*0.6;
            }
            
        }

        $point = round($point, 2);

        $two->update($requestData + ['user_id' => $user_id, 'point' => $point, 'ip' => $request->ip()]);

        $this->recount();

        Session::flash('flash_message','Date successfully updated.');

        $two = Two::findOrFail($id);
        Mail::to($user->email)->send(new AfterDubFromSubmitByAdvance($two));

        return redirect(route('twos.show', array('id' => $two->id)));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        Two::destroy($id);

        return redirect('twos')->with('flash_message', 'Two deleted!');
    }
}
