@extends('seller.layouts.app')

@section('panel_content')

    <div class="card">
        <form id="sort_orders" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col text-center text-md-left">
                    <h5 class="mb-md-0 h6">{{ translate('Orders Details') }}</h5>
                </div>
                <div class="col-md-3 ml-auto">
                    <select class="form-control aiz-selectpicker"
                        data-placeholder="{{ translate('Filter by Payment Status') }}" name="payment_status"
                        onchange="sort_orders()">
                        <option value="">{{ translate('Filter by Payment Status') }}</option>
                        <option value="paid"
                            @isset($payment_status) @if ($payment_status == 'paid') selected @endif @endisset>
                            {{ translate('Paid') }}</option>
                        <option value="unpaid"
                            @isset($payment_status) @if ($payment_status == 'unpaid') selected @endif @endisset>
                            {{ translate('Unpaid') }}</option>
                    </select>
                </div>

                <div class="col-md-3 ml-auto">
                    <select class="form-control aiz-selectpicker"
                        data-placeholder="{{ translate('Filter by Payment Status') }}" name="delivery_status"
                        onchange="sort_orders()">
                        <option value="">{{ translate('Filter by Deliver Status') }}</option>
                        <option value="pending"
                            @isset($delivery_status) @if ($delivery_status == 'pending') selected @endif @endisset>
                            {{ translate('Pending') }}</option>
                        <option value="confirmed"
                            @isset($delivery_status) @if ($delivery_status == 'confirmed') selected @endif @endisset>
                            {{ translate('Confirmed') }}</option>
                        <option value="picked_up"
                            @isset($delivery_status) @if ($delivery_status == 'picked_up') selected @endif @endisset>
                            {{ translate('Picked Up') }}</option>
                        <option value="on_the_way"
                            @isset($delivery_status) @if ($delivery_status == 'on_the_way') selected @endif @endisset>
                            {{ translate('On The Way') }}</option>
                        <option value="delivered"
                            @isset($delivery_status) @if ($delivery_status == 'delivered') selected @endif @endisset>
                            {{ translate('Delivered') }}</option>
                        <option value="cancelled"
                            @isset($delivery_status) @if ($delivery_status == 'cancelled') selected @endif @endisset>
                            {{ translate('Cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="from-group mb-0">
                        <input type="text" class="form-control" id="search" name="search"
                            @isset($sort_search) value="{{ $sort_search }}" @endisset
                            placeholder="{{ translate('Type Order code & hit Enter') }}">
                    </div>
                </div>
            </div>
        </form>

        @if (count($ordersDetails) > 0)
            <div class="card-body p-3">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Order Code') }}</th>
                            <th>{{ translate('Product Name') }}</th>
                            <th>{{ translate('Product Image') }}</th>
                            <th>{{ translate('Category Name') }}</th>
                            <th>{{ translate('Num. of Items') }}</th>
                            <th>{{ translate('Customer') }}</th>
                            <th>{{ translate('Amount') }}</th>
                            <th>{{ translate('Delivery Status') }}</th>
                            <th>{{ translate('Payment method') }}</th>
                            <th>{{ translate('Payment Status') }}</th>
                            <th>{{ translate('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($ordersDetails as $key => $orderDetails)
                            @php
                                $order = $orderDetails->order;
                            @endphp
                            @if ($order)
                                <tr>
                                    <td>{{ $loop->iteration + ($ordersDetails->currentPage() - 1) * $ordersDetails->perPage() }}
                                    </td>
                                    <td>
                                        {{ $order->code }}
                                    </td>
                                    <td>
                                        <strong>
                                            {{ $orderDetails->product_name }}
                                        </strong>
                                        <br>
                                        <small>
                                            {{ $orderDetails->variation }}
                                        </small>
                                    </td>
                                    <td>
                                        <img src=" {{ $orderDetails->trendyol == 1 || $orderDetails->provider_id != null ? $orderDetails->product_image : uploaded_asset($orderDetails->product_image) }}"
                                            height="100" width="85" alt="Image"
                                            onerror="this.src='{{ static_asset('assets/img/placeholder.webp') }}'">
                                    </td>
                                    <td>
                                        {{ $orderDetails->category_name }}
                                    </td>
                                    <td>
                                        {{ $orderDetails->quantity }}
                                    </td>
                                    <td>
                                        @if ($order->user != null)
                                            {{ $order->user->name }}
                                        @else
                                            Guest ({{ $order->guest_id }})
                                        @endif
                                    </td>
                                    <td>
                                        {{ single_price($orderDetails->price) }}
                                    </td>
                                    <td>
                                        {{ translate(ucfirst(str_replace('_', ' ', $orderDetails->delivery_status))) }}
                                    </td>
                                    <td>
                                        {{ translate(ucfirst(str_replace('_', ' ', $order->payment_type))) }}
                                    </td>
                                    <td>
                                        @if ($orderDetails->payment_status == 'paid')
                                            <span class="badge badge-inline badge-success"
                                                style="width: 100% !important;">{{ translate('Paid') }}</span>
                                        @else
                                            <span class="badge badge-inline badge-danger"
                                                style="width: 100% !important;">{{ translate('Unpaid') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ date('Y-m-d', $order->date) }}
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $ordersDetails->appends(request()->input())->links() }}
                </div>
            </div>
        @endif
    </div>

@endsection

@section('script')
    <script type="text/javascript">
        function sort_orders(el) {
            $('#sort_orders').submit();
        }
    </script>
@endsection
