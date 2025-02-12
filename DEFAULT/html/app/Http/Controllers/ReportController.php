<?php

namespace App\Http\Controllers;

use App\Exports\InvitationReportExport;
use App\Exports\WalletReportExport;
use App\Http\Requests\Report\CategoryRequest;
use App\Http\Requests\Report\CommissionRequest;
use App\Http\Requests\Report\InvitationRequest;
use App\Http\Requests\Report\SellerSaleRequest;
use App\Http\Requests\Report\WalletTransactionRequest;
use App\Models\Product;
use App\Models\CommissionHistory;
use App\Models\Invitation;
use App\Models\Wallet;
use App\Models\Search;
use App\Models\Shop;
use Auth;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:in_house_product_sale_report'])->only('in_house_sale_report');
        $this->middleware(['permission:seller_products_sale_report'])->only('seller_sale_report');
        $this->middleware(['permission:products_stock_report'])->only('stock_report');
        $this->middleware(['permission:product_wishlist_report'])->only('wish_report');
        $this->middleware(['permission:user_search_report'])->only('user_search_report');
        $this->middleware(['permission:commission_history_report'])->only('commission_history');
        $this->middleware(['permission:wallet_transaction_report'])->only('wallet_transaction_history', 'invitation_report');
    }

    public function stock_report(CategoryRequest $request)
    {
        $sort_by = null;
        $products = Product::orderBy('created_at', 'desc');
        if ($request->has('category_id')) {
            $sort_by = $request->category_id;
            $products = $products->where('category_id', $sort_by);
        }
        $products = $products->paginate(15);
        return view('backend.reports.stock_report', compact('products', 'sort_by'));
    }

    public function in_house_sale_report(CategoryRequest $request)
    {
        $sort_by = null;
        $products = Product::orderBy('num_of_sale', 'desc')->where('added_by', 'admin');
        if ($request->has('category_id')) {
            $sort_by = $request->category_id;
            $products = $products->where('category_id', $sort_by);
        }
        $products = $products->paginate(15);
        return view('backend.reports.in_house_sale_report', compact('products', 'sort_by'));
    }

    public function seller_sale_report(SellerSaleRequest $request)
    {
        $sort_by = null;
        // $sellers = User::where('user_type', 'seller')->orderBy('created_at', 'desc');
        $sellers = Shop::with('user')->orderBy('created_at', 'desc');
        if ($request->has('verification_status')) {
            $sort_by = $request->verification_status;
            $sellers = $sellers->where('verification_status', $sort_by);
        }
        $sellers = $sellers->paginate(10);
        return view('backend.reports.seller_sale_report', compact('sellers', 'sort_by'));
    }

    public function wish_report(CategoryRequest $request)
    {
        $sort_by = null;
        $products = Product::orderBy('created_at', 'desc');
        if ($request->has('category_id')) {
            $sort_by = $request->category_id;
            $products = $products->where('category_id', $sort_by);
        }
        $products = $products->paginate(10);
        return view('backend.reports.wish_report', compact('products', 'sort_by'));
    }

    public function user_search_report()
    {
        $searches = Search::orderBy('count', 'desc')->paginate(10);
        return view('backend.reports.user_search_report', compact('searches'));
    }

    public function commission_history(CommissionRequest $request)
    {
        $seller_id = null;
        $date_range = null;

        if (Auth::user()->user_type == 'seller') {
            $seller_id = Auth::user()->id;
        }
        if ($request->seller_id) {
            $seller_id = $request->seller_id;
        }

        $commission_history = CommissionHistory::orderBy('created_at', 'desc');

        if ($request->date_range) {
            $date_range = $request->date_range;
            $date_range1 = explode(" / ", $request->date_range);
            $commission_history = $commission_history->where('created_at', '>=', $date_range1[0]);
            $commission_history = $commission_history->where('created_at', '<=', $date_range1[1]);
        }
        if ($seller_id) {

            $commission_history = $commission_history->where('seller_id', '=', $seller_id);
        }

        $commission_history = $commission_history->paginate(10);
        if (Auth::user()->user_type == 'seller') {
            return view('seller.reports.commission_history_report', compact('commission_history', 'seller_id', 'date_range'));
        }
        return view('backend.reports.commission_history_report', compact('commission_history', 'seller_id', 'date_range'));
    }

    public function wallet_transaction_history(WalletTransactionRequest $request)
    {
        $payment_method = null;
        $date_range = null;
        $customer = null;

        if ($request->payment_method) {
            $payment_method = $request->payment_method;
        }

        if ($request->customer) {
            $customer = $request->customer;
        }

        $payment_methods = Wallet::distinct()->pluck('payment_method')->toArray();

        $wallet_history = Wallet::orderBy('created_at', 'desc');

        if ($request->date_range) {
            $date_range = $request->date_range;
            $date_range1 = explode(" / ", $request->date_range);
            $wallet_history = $wallet_history->where('created_at', '>=', $date_range1[0]);
            $wallet_history = $wallet_history->where('created_at', '<=', $date_range1[1]);
        }
        if ($payment_method) {
            $wallet_history = $wallet_history->where('payment_method', 'Like', $payment_method);
        }
        if ($customer) {
            $wallet_history = $wallet_history->whereHas('user', function ($q) use ($customer) {
                $q->where('name', 'like', '%' . $customer . '%');
                $q->orWhere('email', 'like', '%' . $customer . '%');
            });
        }

        if ($request->has('export') && $request->export == 'excel') {
            $export = new WalletReportExport($wallet_history);
            return Excel::download($export, 'wallet_report.xlsx');
        }

        $wallets = $wallet_history->paginate(10);

        return view('backend.reports.wallet_history_report', compact('wallets', 'payment_methods', 'payment_method', 'date_range', 'customer'));
    }

    public function invitation_report(InvitationRequest $request)
    {
        $date_range = null;
        $invited_by_user = null;
        $invited_user = null;
        $used = null;
        $invitations = Invitation::orderBy('created_at', 'desc');
        if ($request->date_range) {
            $date_range = $request->date_range;
            $date_range1 = explode(" / ", $request->date_range);
            $invitations = $invitations->where('created_at', '>=', $date_range1[0]);
            $invitations = $invitations->where('created_at', '<=', $date_range1[1]);
        }
        if ($request->used) {
            $used = $request->used;
            $invitations = $invitations->where('used', $request->used == 'used' ? 1 : 0);
        }
        if($request->invited_by_user){
            $invited_by_user = $request->invited_by_user;
            $invitations = $invitations->where('invited_by_user', $request->invited_by_user);
        }
        if($request->invited_user){
            $invited_user = $request->invited_user;
            $invitations = $invitations->where('invited_user', $request->invited_user);
        }
        if ($request->has('export') && $request->export == 'excel') {
            $export = new InvitationReportExport($invitations);
            return Excel::download($export, 'invitation_report.xlsx');
        }
        $invitations = $invitations->paginate(10);
        return view('backend.reports.invitation_report', compact('invitations', 'date_range', 'invited_by_user' , 'invited_user' , 'used'));
    }
}
