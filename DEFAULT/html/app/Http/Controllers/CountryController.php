<?php

namespace App\Http\Controllers;

use App\Http\Requests\Country\IndexRequest;
use App\Http\Requests\Country\UpdateRequest;
use App\Models\Country;

class CountryController extends Controller
{
    public function __construct() {
        // Staff Permission Check
        $this->middleware(['permission:shipping_country_setting'])->only('index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexRequest $request)
    {
        $sort_country = $request->sort_country;
        $country_queries = Country::query();
        if($request->sort_country) {
            $country_queries->where('name', 'like', "%$sort_country%");
        }
        $countries = $country_queries->orderBy('status', 'desc')->paginate(15);

        return view('backend.setup_configurations.countries.index', compact('countries', 'sort_country'));
    }

    public function updateStatus(UpdateRequest $request){
        $country = Country::findOrFail($request->id);
        $country->status = $request->status;
        if($country->save()){
            return 1;
        }
        return 0;
    }
}
