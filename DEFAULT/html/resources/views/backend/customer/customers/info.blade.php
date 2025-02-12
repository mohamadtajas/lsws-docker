@can('login_as_customer')
    <div class="text-center my-3">
        <span class="avatar avatar-xxl mb-3">
            <img src="{{ uploaded_asset($user->avatar_original) }}"
                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/avatar-place.png') }}';">
        </span>
        <h1 class="h5 mb-1">{{ $user->name }}</h1>
        <span class="text-muted">{{ $user->email }}</span>
        <p class="text-muted">{{ $user->phone }}</p>
    </div>
    <hr>
    <div class="text-center mb-2 px-2">
        <h1 class="h5 mb-2">{{ translate('Address') }}</h1>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ translate('Name') }}</th>
                    <th>{{ translate('ID Number') }}</th>
                    <th>{{ translate('Address') }}</th>
                    <th>{{ translate('Phone') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($user->addresses as $key => $address)
                    <tr>
                        <td>{{ $key + 1 }}</td>
                        <td>{{ $address->first_name . ' ' . $address->last_name }}</td>
                        <td>{{ $address->id_number }}</td>
                        <td>{{ $address->city->name . ', ' . $address->state->name . ', ' . $address->country->name . ', ' . $address->address }}
                        </td>
                        <td>{{ $address->phone }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <hr>
    <div class="text-center mb-2 px-2">
        <div class="table-responsive">
            <table class="table table-striped mar-no">
                <tbody>
                    <tr>
                        <td>{{ translate('Wallet Balance') }}</td>
                        <td>{{ single_price($user->balance) }}</td>
                    </tr>
                    <tr>
                        <td>{{ translate('Wallet Transaction') }}</td>
                        <td><a class="text-muted"
                                href="{{ route('wallet-history.index') }}?customer={{ $user->email }}"
                                target="_blank">{{ translate('View') }}</a></td>
                    </tr>
                    <tr>
                        <td>{{ translate('Total Expenditure') }}</td>
                        <td>{{ single_price(
                            $user->orders->flatMap(function ($order) {
                                    return $order->orderDetails->where('payment_status', 'paid')->where('delivery_status', '!=', 'cancelled');
                                })->sum('price'),
                        ) }}
                        </td>
                    </tr>
                    <tr>
                        <td>{{ translate('Total Orders') }}</td>
                        <td><a class="text-muted"
                            href="{{ route('all_orders.index') }}?customer={{ $user->email }}"
                            target="_blank">{{ translate('View') }}
                        </a></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <hr>
@endcan
