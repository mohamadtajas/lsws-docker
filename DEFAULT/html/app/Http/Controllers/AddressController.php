<?php

namespace App\Http\Controllers;

use App\Http\Requests\Address\AddressRequest;
use App\Http\Requests\Address\GetCitiesRequest;
use App\Http\Requests\Address\GetStatesRequest;
use App\Models\Address;
use App\Models\City;
use App\Models\State;
use Auth;

class AddressController extends Controller
{

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(AddressRequest $request)
    {
        $address = new Address;
        if ($request->has('customer_id')) {
            $address->user_id   = $request->customer_id;
        } else {
            $address->user_id   = Auth::user()->id;
        }
        $address->first_name    = $request->first_name;
        $address->last_name     = $request->last_name;
        $address->id_number     = $request->id_number;
        $address->address       = $request->address;
        $address->country_id    = $request->country_id;
        $address->state_id      = $request->state_id;
        $address->city_id       = $request->city_id;
        $address->longitude     = $request->longitude;
        $address->latitude      = $request->latitude;
        $address->postal_code   = $request->postal_code;
        $address->phone         = $request->phone;
        $address->save();

        if (Auth::user()->phone == null) {
            $user = Auth::user();
            $user->phone = $request->phone;
            $user->save();
        }

        flash(translate('Address info Stored successfully'))->success();
        return back();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(string $id)
    {
        $data = [];
        $address = Address::where('user_id', Auth::user()->id)->find($id);

        if (!$address) {
            $address = Address::where('user_id', Auth::user()->id)->first();
        }

        $data['address_data'] = $address;
        $data['states'] = State::where('status', 1)->where('country_id', $data['address_data']->country_id)->get();
        $data['cities'] = City::where('status', 1)->where('state_id', $data['address_data']->state_id)->get();

        $returnHTML = view('frontend.' . get_setting('homepage_select') . '.partials.address_edit_modal', $data)->render();
        return response()->json(array('data' => $data, 'html' => $returnHTML));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(AddressRequest $request,string $id)
    {
        $address = Address::where('user_id', Auth::user()->id)->find($id);
        if (!$address) {
            flash(translate('Something went wrong'))->error();
            return back();
        }
        $address->first_name    = $request->first_name;
        $address->last_name     = $request->last_name;
        $address->id_number     = $request->id_number;
        $address->address       = $request->address;
        $address->country_id    = $request->country_id;
        $address->state_id      = $request->state_id;
        $address->city_id       = $request->city_id;
        $address->longitude     = $request->longitude;
        $address->latitude      = $request->latitude;
        $address->postal_code   = $request->postal_code;
        $address->phone         = $request->phone;

        $address->save();

        flash(translate('Address info updated successfully'))->success();
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
        $address = Address::where('user_id', Auth::user()->id)->find($id);
        if (!$address) {
            flash(translate('Something went wrong'))->error();
            return back();
        }
        if (!$address->set_default) {
            $address->delete();
            return back();
        }
        flash(translate('Default address cannot be deleted'))->warning();
        return back();
    }

    public function getStates(GetStatesRequest $request)
    {
        $states = State::where('status', 1)->where('country_id', $request->country_id)->get();
        $html = '<option value="" >' . translate("Select State") . '</option>';

        foreach ($states as $state) {
            $html .= '<option value="' . $state->id . '">' . $state->name . '</option>';
        }

        echo json_encode($html);
    }

    public function getCities(GetCitiesRequest $request)
    {
        $cities = City::where('status', 1)->where('state_id', $request->state_id)->get();
        $html = '<option value="">' . translate("Select City") . '</option>';

        foreach ($cities as $row) {
            $html .= '<option value="' . $row->id . '">' . $row->getTranslation('name') . '</option>';
        }

        echo json_encode($html);
    }

    public function set_default(string $id)
    {
        $address = Address::where('user_id', Auth::user()->id)->find($id);
        if (!$address) {
            flash(translate('Something went wrong'))->error();
            return back();
        }

        foreach (Auth::user()->addresses as $key => $addressItem) {
            $addressItem->set_default = 0;
            $addressItem->save();
        }
        $address->set_default = 1;
        $address->save();

        return back();
    }
}
