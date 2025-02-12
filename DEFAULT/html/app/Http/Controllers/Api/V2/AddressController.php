<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\Address\AddressIdRequest;
use App\Http\Requests\Address\AddressRequest;
use App\Http\Requests\Address\IdRequest;
use App\Http\Requests\Address\ShippingAddressLocationRequest;
use App\Http\Requests\Address\UpdateShippingTypeInCartRequest;
use App\Http\Requests\Address\NameRequest;
use App\Models\City;
use App\Models\Country;
use App\Http\Resources\V2\AddressCollection;
use App\Models\Address;
use App\Http\Resources\V2\CitiesCollection;
use App\Http\Resources\V2\StatesCollection;
use App\Http\Resources\V2\CountriesCollection;
use App\Models\Carrier;
use App\Models\Cart;
use App\Models\PickupPoint;
use App\Models\State;
use Auth;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function addresses()
    {
        return new AddressCollection(Address::where('user_id', auth()->user()->id)->get());
    }

    public function createShippingAddress(AddressRequest $request)
    {
        $address = new Address;
        $address->user_id = auth()->user()->id;
        $address->first_name    = $request->first_name;
        $address->last_name     = $request->last_name;
        $address->id_number     = $request->id_number;
        $address->address = $request->address;
        $address->country_id = $request->country_id;
        $address->state_id = $request->state_id;
        $address->city_id = $request->city_id;
        $address->postal_code = $request->postal_code;
        $address->phone = $request->phone;
        $address->save();

        if (Auth::user()->phone == null) {
            $user = Auth::user();
            $user->phone = $request->phone;
            $user->save();
        }

        return response()->json([
            'result' => true,
            'message' => translate('Shipping information has been added successfully')
        ]);
    }

    public function updateShippingAddress(AddressRequest $request)
    {
        $address = Address::where('user_id', auth()->user()->id)->find($request->id);
        if (!$address) {
            return response()->json([
                'result' => false,
                'message' => translate('Address not found')
            ]);
        }
        $address->first_name    = $request->first_name;
        $address->last_name     = $request->last_name;
        $address->id_number     = $request->id_number;
        $address->address = $request->address;
        $address->country_id = $request->country_id;
        $address->state_id = $request->state_id;
        $address->city_id = $request->city_id;
        $address->postal_code = $request->postal_code;
        $address->phone = $request->phone;
        $address->save();

        return response()->json([
            'result' => true,
            'message' => translate('Shipping information has been updated successfully')
        ]);
    }

    public function updateShippingAddressLocation(ShippingAddressLocationRequest $request)
    {
        $address = Address::where('user_id', auth()->user()->id)->find($request->id);
        if (!$address) {
            return response()->json([
                'result' => false,
                'message' => translate('Address not found')
            ]);
        }
        $address->latitude = $request->latitude;
        $address->longitude = $request->longitude;
        $address->save();

        return response()->json([
            'result' => true,
            'message' => translate('Shipping location in map updated successfully')
        ]);
    }


    public function deleteShippingAddress(string $id)
    {
        $address = Address::where('user_id', auth()->user()->id)->find($id);
        if (!$address) {
            return response()->json([
                'result' => false,
                'message' => translate('Address not found')
            ]);
        }
        $address->delete();
        return response()->json([
            'result' => true,
            'message' => translate('Shipping information has been deleted')
        ]);
    }

    public function makeShippingAddressDefault(IdRequest $request)
    {
        $address = Address::where('user_id', auth()->user()->id)->find($request->id);
        if (!$address) {
            return response()->json([
                'result' => false,
                'message' => translate('Address not found')
            ]);
        }

        Address::where('user_id', auth()->user()->id)->update(['set_default' => 0]); //make all user addressed non default first
        $address->set_default = 1;
        $address->save();
        return response()->json([
            'result' => true,
            'message' => translate('Default shipping information has been updated')
        ]);
    }

    public function updateAddressInCart(AddressIdRequest $request)
    {
        try {
            $address = Address::where('user_id', auth()->user()->id)->find($request->address_id);
            if (!$address) {
                return response()->json([
                    'result' => false,
                    'message' => translate('Address not found')
                ]);
            }
            Cart::where('user_id', auth()->user()->id)->update(['address_id' => $request->address_id]);
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => translate('Could not save the address')
            ]);
        }
        return response()->json([
            'result' => true,
            'message' => translate('Address is saved')
        ]);
    }


    public function getShippingInCart()
    {

        $cart = Cart::where('user_id', auth()->user()->id)->first();

        $address = $cart->address;
        return new AddressCollection(Address::where('id', $address->id)->get());
    }


    public function updateShippingTypeInCart(UpdateShippingTypeInCartRequest $request)
    {
        try {
            $carts = Cart::where('user_id', auth()->user()->id)->get();


            foreach ($carts as $key => $cart) {

                $cart->shipping_cost = 0;

                if ($request->shipping_type == "pickup_point") {
                    $pickup_point = PickupPoint::where('id', $request->shipping_id)->find();
                    if (!$pickup_point) {
                        return response()->json([
                            'result' => false,
                            'message' => translate('Could not save the address')
                        ]);
                    }
                    $cart->shipping_type = "pickup_point";
                    $cart->pickup_point = $pickup_point->id;
                    $cart->carrier_id = 0;
                } else if ($request->shipping_type == "home_delivery") {
                    $cart->shipping_cost = getShippingCost($carts, $key);
                    $cart->shipping_type = "home_delivery";
                    $cart->pickup_point = 0;
                    $cart->carrier_id = 0;
                } else if ($request->shipping_type == "carrier_base") {
                    $carrier = Carrier::where('id', $request->shipping_id)->find();
                    if (!$carrier) {
                        return response()->json([
                            'result' => false,
                            'message' => translate('Could not save the address')
                        ]);
                    }
                    $cart->shipping_cost = getShippingCost($carts, $key, $cart->carrier_id);
                    $cart->shipping_type = "carrier";
                    $cart->carrier_id = $carrier->id;
                    $cart->pickup_point = 0;
                } else {
                    return response()->json([
                        'result' => false,
                        'message' => translate('Could not save the address')
                    ]);
                }
                $cart->save();
            }
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'message' => translate('Could not save the address')
            ]);
        }
        return response()->json([
            'result' => true,
            'message' => translate('Delivery address is saved')
        ]);
    }


    public function getCities()
    {
        return new CitiesCollection(City::where('status', 1)->get());
    }

    public function getStates()
    {
        return new StatesCollection(State::where('status', 1)->get());
    }

    public function getCountries(NameRequest $request)
    {
        $country_query = Country::where('status', 1);
        if ($request->name != "" || $request->name != null) {
            $country_query->where('name', 'like', '%' . $request->name . '%');
        }
        $countries = $country_query->get();

        return new CountriesCollection($countries);
    }

    public function getCitiesByState(string $state_id, NameRequest $request)
    {
        $city_query = City::where('status', 1)->where('state_id', $state_id);
        if ($request->name != "" || $request->name != null) {
            $city_query->where('name', 'like', '%' . $request->name . '%');
        }
        $cities = $city_query->get();
        return new CitiesCollection($cities);
    }

    public function getStatesByCountry(string $country_id, NameRequest $request)
    {
        $state_query = State::where('status', 1)->where('country_id', $country_id);
        if ($request->name != "" || $request->name != null) {
            $state_query->where('name', 'like', '%' . $request->name . '%');
        }
        $states = $state_query->get();
        return new StatesCollection($states);
    }
}
