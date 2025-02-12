<?php

namespace App\Http\Controllers;

use App\Exports\CustomerExport;
use App\Http\Requests\Customer\BulkIdRequest;
use App\Http\Requests\Customer\IndexRequest;
use App\Models\Customer;
use App\Models\User;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    public function __construct()
    {
        // Staff Permission Check
        $this->middleware(['permission:view_all_customers'])->only('index');
        $this->middleware(['permission:login_as_customer'])->only('login');
        $this->middleware(['permission:ban_customer'])->only('ban');
        $this->middleware(['permission:delete_customer'])->only('destroy');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(IndexRequest $request)
    {
        $sort_search = null;
        $date = $request->date;
        $status = $request->status;
        $users = User::where('user_type', 'customer')->orderBy('created_at', 'desc');
        if ($request->has('search')) {
            $sort_search = $request->search;
            $users->where(function ($query) use ($sort_search) {
                $query->where('name', 'like', '%' . $sort_search . '%')
                    ->orWhere('email', 'like', '%' . $sort_search . '%');
            });
        }

        // Handle date range filter
        if ($date !== null) {
            $dates = explode(" to ", $date);
            $users->whereBetween('created_at', [
                date('Y-m-d', strtotime($dates[0])) . ' 00:00:00',
                date('Y-m-d', strtotime($dates[1])) . ' 23:59:59',
            ]);
        }
        if ($status !== null) {
            $users->when($status === 'verified', function ($query) {
                $query->whereNotNull('email_verified_at');
            }, function ($query) {
                $query->whereNull('email_verified_at');
            });
        }
        if ($request->has('export') && $request->export === 'excel') {
            return $this->configureExport($users);
        }
        $users = $users->paginate(15);
        return view('backend.customer.customers.index', compact('users', 'sort_search', 'date', 'status'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $id)
    {
        $customer = User::findOrFail($id);
        if ($customer->balance != 0) {
            flash(translate('You can not delete this account , balance must be zero .'))->warning();
            return back();
        }
        $customer->customer_products()->delete();

        User::destroy($id);
        flash(translate('Customer has been deleted successfully'))->success();
        return redirect()->route('customers.index');
    }

    public function bulk_customer_delete(BulkIdRequest $request)
    {
        if ($request->id) {
            foreach ($request->id as $customer_id) {
                $customer = User::findOrFail($customer_id);
                $this->destroy($customer_id);
            }
        }

        return 1;
    }

    public function login(string $id)
    {
        $user = User::findOrFail(decrypt($id));

        auth()->login($user, true);

        return redirect()->route('dashboard');
    }

    public function ban(string $id)
    {
        $user = User::findOrFail(decrypt($id));

        if ($user->banned == 1) {
            $user->banned = 0;
            flash(translate('Customer UnBanned Successfully'))->success();
        } else {
            $user->banned = 1;
            flash(translate('Customer Banned Successfully'))->success();
        }

        $user->save();

        return back();
    }

    public function customer_info(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return array(
                'status' => 0,
            );
        }
        return array(
            'status' => 1,
            'modal_view' => view('backend.customer.customers.info', compact('user'))->render()
        );
    }

    private function configureExport($users)
    {
        $users = $users->select(
            'users.id',
            'users.name',
            'users.email',
            \DB::raw('CONCAT("\t", users.phone) as phone'),
            \DB::raw('CONCAT("\t", MAX(CASE WHEN addresses.set_default = 1 THEN addresses.phone ELSE NULL END)) as default_address_number'),
            \DB::raw('FORMAT(users.balance, 2) as balance'),
            \DB::raw('FORMAT(COUNT(DISTINCT orders.id),0) AS total_orders'),
            \DB::raw('FORMAT(COUNT(DISTINCT invitations.id),0) AS total_invitations'),
            \DB::raw('FORMAT(COALESCE(wallet_totals.total_wallet_balance, 0), 2) AS total_wallet_balance'),
            \DB::raw('FORMAT(COALESCE(order_totals.total_expenditure, 0), 2) AS total_expenditure'),
            \DB::raw('FORMAT(COALESCE(wallet_totals.total_wallet_balance, 0) - COALESCE(order_totals.total_expenditure, 0), 2) AS total_remaining'),
            \DB::raw("'" . currency_symbol() . "' as currency"),
            \DB::raw("CASE WHEN users.email_verified_at IS NOT NULL THEN 'Verified' ELSE 'Unverified' END as email_status"),
            'users.created_at'
        )
            ->leftJoin('addresses', function ($join) {
                $join->on('addresses.user_id', '=', 'users.id')
                    ->where('addresses.set_default', '=', 1);
            })
            ->leftJoin('orders', 'orders.user_id', '=', 'users.id')
            ->leftJoin('invitations', 'invitations.invited_by_user', '=', 'users.email')
            ->leftJoin(
                \DB::raw('(SELECT user_id, SUM(amount) AS total_wallet_balance FROM wallets GROUP BY user_id) AS wallet_totals'),
                'wallet_totals.user_id',
                '=',
                'users.id'
            )
            ->leftJoin(
                \DB::raw('(SELECT orders.user_id, SUM(order_details.price) AS total_expenditure
                           FROM order_details
                           INNER JOIN orders ON orders.id = order_details.order_id
                           WHERE order_details.payment_status = "paid"
                             AND order_details.delivery_status != "cancelled"
                           GROUP BY orders.user_id) AS order_totals'),
                'order_totals.user_id',
                '=',
                'users.id'
            )
            ->groupBy(
                'users.id',
                'users.name',
                'users.email',
                'users.phone',
                'users.created_at',
                'users.email_verified_at'
            );
        $export = new CustomerExport($users);
        return Excel::download($export, 'customers.xlsx');
    }
}
