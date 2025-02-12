@extends('backend.layouts.app')

@section('content')
    <div class="card">
        <form class="" action="" id="sort_orders" method="GET">
            <div class="card-header row gutters-5 border-0 pb-0">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Orders') }}</h5>
                </div>

                @canany(['delete_order', 'trendyol_manual_order'])
                    <div class="dropdown mb-2 mb-md-0">
                        <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                            {{ translate('Bulk Action') }}
                        </button>

                        <div class="dropdown-menu dropdown-menu-right">
                            @can('delete_order')
                                <a class="dropdown-item confirm-alert" href="javascript:void(0)" data-target="#bulk-delete-modal">
                                    {{ translate('Delete selection') }}</a>
                            @endcan
                            @can('trendyol_manual_order')
                                <a class="dropdown-item confirm-alert" href="javascript:void(0)"
                                    onclick="bulk_trendyol_manual_order()">
                                    {{ translate('Trendyol manual order selection') }}</a>
                            @endcan
                        </div>
                    </div>
                @endcanany

                <div class="col-lg-2">
                    <select class="form-control aiz-selectpicker" name="delivery_status" id="delivery_status">
                        <option value="">{{ translate('Filter by Delivery Status') }}</option>
                        <option value="pending" @if ($delivery_status == 'pending') selected @endif>{{ translate('Pending') }}
                        </option>
                        <option value="confirmed" @if ($delivery_status == 'confirmed') selected @endif>
                            {{ translate('Confirmed') }}</option>
                        <option value="picked_up" @if ($delivery_status == 'picked_up') selected @endif>
                            {{ translate('Picked Up') }}</option>
                        <option value="on_the_way" @if ($delivery_status == 'on_the_way') selected @endif>
                            {{ translate('On The Way') }}</option>
                        <option value="delivered" @if ($delivery_status == 'delivered') selected @endif>
                            {{ translate('Delivered') }}</option>
                        <option value="cancelled" @if ($delivery_status == 'cancelled') selected @endif>
                            {{ translate('Cancel') }}</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <select class="form-control aiz-selectpicker" name="payment_status" id="payment_status">
                        <option value="">{{ translate('Filter by Payment Status') }}</option>
                        <option value="paid"
                            @isset($payment_status) @if ($payment_status == 'paid') selected @endif @endisset>
                            {{ translate('Paid') }}</option>
                        <option value="unpaid"
                            @isset($payment_status) @if ($payment_status == 'unpaid') selected @endif @endisset>
                            {{ translate('Unpaid') }}</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="aiz-date-range form-control" value="{{ $date }}"
                            name="date" placeholder="{{ translate('Filter by date') }}" data-format="DD-MM-Y"
                            data-separator=" to " data-advanced-range="true" autocomplete="off">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="search"
                            name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset
                            placeholder="{{ translate('Type Order code & hit Enter') }}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="customer"
                            name="customer"@isset($customer) value="{{ $customer }}" @endisset
                            placeholder="{{ translate('Type email or name & Enter') }}">
                    </div>
                </div>
            </div>
            <div class="card-header row gutters-5 justify-content-end">
                <div class="col-auto">
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                        <button id="exportBtn" class="btn btn-primary">
                            <div class="row">
                                <div class="col-12 d-flex justify-content-center">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"
                                        style="display: none; width: 1.3rem; height: 1.3rem; border-width: 0.2em; border-right-color: transparent;"></span>
                                    <span class="button-text">{{ translate('Excel') }}</span>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <!--<th>#</th>-->
                            @if (auth()->user()->can('delete_order'))
                                <th>
                                    <div class="form-group">
                                        <div class="aiz-checkbox-inline">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" class="check-all">
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                    </div>
                                </th>
                            @else
                                <th data-breakpoints="lg">#</th>
                            @endif

                            <th>{{ translate('Order Code') }}</th>
                            <th>{{ translate('Trendyol Order Code') }}</th>
                            <th data-breakpoints="md">{{ translate('Num. of Products') }}</th>
                            <th data-breakpoints="md">{{ translate('Num. of Items') }}</th>
                            <th data-breakpoints="md">{{ translate('Customer') }}</th>
                            <th data-breakpoints="md">{{ translate('Phone') }}</th>
                            <th data-breakpoints="md">{{ translate('Seller') }}</th>
                            <th data-breakpoints="md">{{ translate('Amount') }}</th>
                            @can('trendyol_earning')
                                <th data-breakpoints="md">{{ translate('Trendyol Amount') }}</th>
                                <th data-breakpoints="md">{{ translate('Earning') }}</th>
                            @endcan
                            <th data-breakpoints="lg">{{ translate('Delivery Status') }}</th>
                            <th data-breakpoints="lg">{{ translate('Payment method') }}</th>
                            <th data-breakpoints="lg">{{ translate('Payment Status') }}</th>
                            @if (addon_is_activated('refund_request'))
                                <th>{{ translate('Refund') }}</th>
                            @endif
                            <th class="text-right" width="15%">{{ translate('options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $key => $order)
                            <tr>
                                @if (auth()->user()->can('delete_order'))
                                    <td>
                                        <div class="form-group">
                                            <div class="aiz-checkbox-inline">
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" class="check-one" name="id[]"
                                                        value="{{ $order->id }}">
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                @else
                                    <td>{{ $key + 1 + ($orders->currentPage() - 1) * $orders->perPage() }}</td>
                                @endif
                                <td>
                                    {{ $order->code }}
                                    @if ($order->viewed == 0)
                                        <span class="badge badge-inline badge-info">{{ translate('New') }}</span>
                                    @endif
                                    @if (addon_is_activated('pos_system') && $order->order_from == 'pos')
                                        <span class="badge badge-inline badge-danger">{{ translate('POS') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $trendyol_orders = $order->trendyol_order->where(
                                            'trendyol_orderNumber',
                                            '>',
                                            0,
                                        );

                                        if (count($trendyol_orders) > 0) {
                                            $unique_order_numbers = [];
                                            foreach ($trendyol_orders as $trendyol_order) {
                                                if ($trendyol_order) {
                                                    $order_number = $trendyol_order->trendyol_orderNumber ?? '';
                                                    if (!in_array($order_number, $unique_order_numbers)) {
                                                        $unique_order_numbers[] = $order_number;
                                                        echo $order_number . '<br>';
                                                    }
                                                }
                                            }
                                        } else {
                                            $trendyol_order = $order->trendyol_order->first();
                                            echo $trendyol_order->trendyol_orderNumber ?? '';
                                        }
                                    @endphp
                                </td>
                                <td>
                                    {{ count($order->orderDetails) }}
                                </td>
                                <td>
                                    {{ $order->orderDetails->pluck('quantity')->sum() }}
                                </td>
                                <td>
                                    @if ($order->user != null)
                                        {{ $order->user->name }}
                                    @else
                                        Guest ({{ $order->guest_id }})
                                    @endif
                                </td>
                                <td>{{ json_decode($order->shipping_address)->phone ?? '' }}</td>
                                <td>
                                    @if ($order->shop)
                                        {{ $order->shop->name }}
                                    @else
                                        {{ translate('Inhouse Order') }}
                                    @endif
                                </td>
                                <td>
                                    {{ single_price($order->grand_total) }}
                                </td>
                                @can('trendyol_earning')
                                    <td>
                                        {{ single_price($order->grand_total_trendyol) }}
                                    </td>
                                    <td>
                                        {{ single_price($order->grand_total - $order->grand_total_trendyol) }}
                                    </td>
                                @endcan
                                <td>
                                    {{ translate(ucfirst(str_replace('_', ' ', $order->delivery_status))) }}
                                </td>
                                <td>
                                    {{ translate(ucfirst(str_replace('_', ' ', $order->payment_type))) }}
                                </td>
                                <td>
                                    @if ($order->payment_status == 'paid')
                                        <span class="badge badge-inline badge-success"
                                            style="width: 100% !important;">{{ translate('Paid') }}</span>
                                    @else
                                        <span class="badge badge-inline badge-danger"
                                            style="width: 100% !important;">{{ translate('Unpaid') }}</span>
                                    @endif
                                </td>
                                @if (addon_is_activated('refund_request'))
                                    <td>
                                        @if (count($order->refund_requests) > 0)
                                            {{ count($order->refund_requests) }} {{ translate('Refund') }}
                                        @else
                                            {{ translate('No Refund') }}
                                        @endif
                                    </td>
                                @endif
                                <td class="text-right">
                                    @if (addon_is_activated('pos_system') && $order->order_from == 'pos')
                                        <a class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                            href="{{ route('admin.invoice.thermal_printer', $order->id) }}"
                                            target="_blank" title="{{ translate('Thermal Printer') }}">
                                            <i class="las la-print"></i>
                                        </a>
                                    @endif

                                    @can('trendyol_manual_order')
                                        @php
                                            $trendyol_order = $order->trendyol_order->first();
                                        @endphp
                                        @if ($trendyol_order && $trendyol_order->trendyol_success == 0 && $order->delivery_status == 'pending')
                                            <a class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                                href="javascript:void(0)" onclick="addToCartTrendyol({{ $order->id }})"
                                                title="{{ translate('Trendyol manual order') }}">
                                                <i class="las la-cart-arrow-down"></i>
                                            </a>
                                        @endif
                                    @endcan

                                    @can('view_order_details')
                                        @php
                                            $order_detail_route = route('orders.show', encrypt($order->id));
                                            if (Route::currentRouteName() == 'seller_orders.index') {
                                                $order_detail_route = route('seller_orders.show', encrypt($order->id));
                                            } elseif (Route::currentRouteName() == 'pick_up_point.index') {
                                                $order_detail_route = route(
                                                    'pick_up_point.order_show',
                                                    encrypt($order->id),
                                                );
                                            }
                                            if (Route::currentRouteName() == 'inhouse_orders.index') {
                                                $order_detail_route = route('inhouse_orders.show', encrypt($order->id));
                                            }
                                        @endphp
                                        <a class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                            href="{{ $order_detail_route }}" title="{{ translate('View') }}">
                                            <i class="las la-eye"></i>
                                        </a>
                                    @endcan
                                    <a class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                        href="{{ route('invoice.download', $order->id) }}"
                                        title="{{ translate('Download Invoice') }}">
                                        <i class="las la-download"></i>
                                    </a>
                                    @can('delete_order')
                                        <a href="#"
                                            class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                            data-href="{{ route('orders.destroy', $order->id) }}"
                                            title="{{ translate('Delete') }}">
                                            <i class="las la-trash"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="aiz-pagination">
                    {{ $orders->appends(request()->input())->links() }}
                </div>

            </div>
        </form>
    </div>
@endsection

@section('modal')
    <!-- Delete modal -->
    @include('modals.delete_modal')
    <!-- Bulk Delete modal -->
    @include('modals.bulk_delete_modal')
    <!-- Trendyol manual order -->
    @can('trendyol_manual_order')
        <div class="modal fade" id="addToCartTrendyol">
            <div class="modal-dialog modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
                <div class="modal-content position-relative">
                    <div class="c-preloader text-center p-3">
                        <i class="las la-spinner la-spin la-3x"></i>
                    </div>
                    <button type="button"
                        class="close absolute-top-right btn-icon close z-1 btn-circle bg-gray mr-2 mt-2 d-flex justify-content-center align-items-center"
                        data-dismiss="modal" aria-label="Close"
                        style="background: #ededf2; width: calc(2rem + 2px); height: calc(2rem + 2px);">
                        <span aria-hidden="true" class="fs-24 fw-700" style="margin-left: 2px;">&times;</span>
                    </button>
                    <div id="addToCartTrendyol-modal-body">

                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('script')
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

        //        function change_status() {
        //            var data = new FormData($('#order_form')[0]);
        //            $.ajax({
        //                headers: {
        //                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //                },
        //                url: "{{ route('bulk-order-status') }}",
        //                type: 'POST',
        //                data: data,
        //                cache: false,
        //                contentType: false,
        //                processData: false,
        //                success: function (response) {
        //                    if(response == 1) {
        //                        location.reload();
        //                    }
        //                }
        //            });
        //        }

        function bulk_delete() {
            var data = new FormData($('#sort_orders')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('bulk-order-delete') }}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        location.reload();
                    }
                }
            });
        }
    </script>
    @can('trendyol_manual_order')
        <script>
            function addToCartTrendyol(orderId) {
                $('#addToCartTrendyol-modal-body').html(null);
                $('#addToCartTrendyol').modal();
                $('.c-preloader').show();
                $.ajax({
                    type: "get",
                    url: `{{ route('trendyol_manual_order') }}?orderId=${orderId}`,
                    success: function(data) {
                        if (data.status > 0) {
                            $('.c-preloader').hide();
                            $('#addToCartTrendyol-modal-body').html(data.modal_view);
                        } else {
                            setTimeout(function() {
                                $('#addToCartTrendyol').modal('hide');
                            }, 100);
                        }
                    }
                });
            }

            function bulk_trendyol_manual_order() {
                var data = new FormData($('#sort_orders')[0]);
                $('#addToCartTrendyol-modal-body').html(null);
                $('#addToCartTrendyol').modal();
                $('.c-preloader').show();
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: `{{ route('bulk_trendyol_manual_order') }}`,
                    type: "POST",
                    data: data,
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        if (data.status > 0) {
                            $('.c-preloader').hide();
                            $('#addToCartTrendyol-modal-body').html(data.modal_view);
                        } else {
                            setTimeout(function() {
                                $('#addToCartTrendyol').modal('hide');
                            }, 100);
                        }
                    }
                });
            }
        </script>
    @endcan
    <script>
        // Create a Web Worker inline to handle the blob download process
        const blob = new Blob([`
            onmessage = function(e) {
                const url = URL.createObjectURL(e.data);
                postMessage(url);
            }
        `], {
            type: "application/javascript"
        });

        const worker = new Worker(URL.createObjectURL(blob));

        $(document).ready(function() {
            $('#exportBtn').click(function(event) {
                event.preventDefault();

                const $exportBtn = $(this);
                $exportBtn.prop('disabled', true);
                $exportBtn.find('.spinner-border').css('display', 'inline-block');
                $exportBtn.find('.button-text').text('');

                const formData = $('#sort_orders')
                    .serialize(); // Collects all input values in the form as a query string
                const exportUrl = "{{ request()->url() }}?" + formData + "&export=excel";
                fetch(exportUrl)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.blob();
                    })
                    .then(blob => {
                        worker.postMessage(blob);
                        worker.onmessage = function(event) {
                            const downloadUrl = event.data;
                            const link = document.createElement('a');
                            link.href = downloadUrl;
                            link.setAttribute('download', 'orders.xlsx');
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);

                            URL.revokeObjectURL(downloadUrl);
                            worker.terminate();

                            $exportBtn.removeClass('btn-primary').addClass('btn-success');
                            $exportBtn.find('.spinner-border').css('display', 'none');
                            $exportBtn.find('.button-text').text('{{ translate('Completed') }}');
                            $exportBtn.prop('disabled', true);
                        };
                    })
                    .catch(error => {
                        alert('Export failed. Please try again.');

                        $exportBtn.prop('disabled', false);
                        $exportBtn.removeClass('btn-success').addClass('btn-primary');
                        $exportBtn.find('.spinner-border').css('display', 'none');
                        $exportBtn.find('.button-text').text('{{ translate('Excel') }}');
                    });
            });
        });
    </script>
@endsection
