<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductQuery\ReplyRequest;
use App\Models\ProductQuery;
use Auth;

class ProductQueryController extends Controller
{
    /**
     * Retrieve queries that belongs to current seller
     */
    public function index()
    {
        $queries = ProductQuery::where('seller_id', Auth::id())->latest()->paginate(20);
        return view('seller.product_query.index', compact('queries'));
    }
    /**
     * Retrieve specific query using query id.
     */
    public function show(string $id)
    {
        $query = ProductQuery::find(decrypt($id));
        return view('seller.product_query.show', compact('query'));
    }
    /**
     * Store reply against the question from seller panel
     */

    public function reply(ReplyRequest $request, $id)
    {
        $query = ProductQuery::find($id);
        $query->reply = $request->reply;
        $query->save();
        flash(translate('Replied successfully!'))->success();
        return redirect()->route('seller.product_query.index');
    }
}
