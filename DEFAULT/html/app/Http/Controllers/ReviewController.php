<?php

namespace App\Http\Controllers;

use App\Http\Requests\Review\IndexRequest;
use App\Http\Requests\Review\ModalByProductRequest;
use App\Http\Requests\Review\ModalRequest;
use App\Http\Requests\Review\StoreRequest;
use App\Http\Requests\Review\UpdateStatusRequest;
use App\Models\OrderDetail;
use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use Auth;

class ReviewController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_product_reviews'])->only('index');
        $this->middleware(['permission:publish_product_review'])->only('updatePublished');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexRequest $request)
    {
        $rating = $request->rating;
        $reviews = Review::query();
        if ($request->rating) {
            $reviews->orderBy('rating', explode(",", $request->rating)[1]);
        }
        $reviews = $reviews->orderBy('created_at', 'desc')->paginate(15);
        return view('backend.product.reviews.index', compact('reviews', 'rating'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
        $orderDetail = OrderDetail::where('delivery_status', 'delivered')->findOrFail($request->order_id);
        if ($orderDetail->order->user_id != Auth::user()->id) {
            flash(translate('You are not allowed to review this product'))->success();
            return back();
        }
        $product_id = $orderDetail->product_id;
        $review = new Review;
        $review->product_id = $product_id;
        $review->user_id = Auth::user()->id;
        $review->rating = $request->rating;
        $review->comment = $request->comment;
        $review->photos = implode(',', $request->photos);
        $review->viewed = '0';
        $review->save();
        $product = Product::findOrFail($product_id);
        if (Review::where('product_id', $product->id)->where('status', 1)->count() > 0) {
            $product->rating = Review::where('product_id', $product->id)->where('status', 1)->sum('rating') / Review::where('product_id', $product->id)->where('status', 1)->count();
        } else {
            $product->rating = 0;
        }
        $product->save();

        if ($product->added_by == 'seller') {
            $seller = $product->user->shop;
            $seller->rating = (($seller->rating * $seller->num_of_reviews) + $review->rating) / ($seller->num_of_reviews + 1);
            $seller->num_of_reviews += 1;
            $seller->save();
        }

        flash(translate('Review has been submitted successfully'))->success();
        return back();
    }

    public function updatePublished(UpdateStatusRequest $request)
    {
        $review = Review::findOrFail($request->id);
        $review->status = $request->status;
        $review->save();

        $product = Product::findOrFail($review->product->id);
        if (Review::where('product_id', $product->id)->where('status', 1)->count() > 0) {
            $product->rating = Review::where('product_id', $product->id)->where('status', 1)->sum('rating') / Review::where('product_id', $product->id)->where('status', 1)->count();
        } else {
            $product->rating = 0;
        }
        $product->save();

        if ($product->added_by == 'seller') {
            $seller = $product->user->shop;
            if ($review->status) {
                $seller->rating = (($seller->rating * $seller->num_of_reviews) + $review->rating) / ($seller->num_of_reviews + 1);
                $seller->num_of_reviews += 1;
            } else {
                $seller->rating = (($seller->rating * $seller->num_of_reviews) - $review->rating) / max(1, $seller->num_of_reviews - 1);
                $seller->num_of_reviews -= 1;
            }

            $seller->save();
        }

        return 1;
    }

    public function product_review_modal(ModalRequest $request)
    {
        $orderDetail = OrderDetail::findOrFail($request->order_id);
        $product = $orderDetail->product;
        $review = Review::where('user_id', Auth::user()->id)->where('product_id', $product->id)->first();
        return view('frontend.user.product_review_modal', compact('product', 'review', 'orderDetail'));
    }

    public function product_review_modal_by_product(ModalByProductRequest $request)
    {
        $product = Product::findOrFail($request->product_id);
        $user = User::find(Auth::user()->id);

        $reviewable = false;
        foreach ($product->orderDetails as $key => $orderDetail) {
            if ($orderDetail->order != null && $orderDetail->order->user_id == $user->id && $orderDetail->delivery_status == 'delivered') {
                $reviewable = true;
            }
        }
        if ($reviewable) {
            $review = Review::where('user_id', Auth::user()->id)->where('product_id', $product->id)->first();
            return view('frontend.user.product_review_modal', compact('product', 'review', 'orderDetail'));
        }
    }
}
