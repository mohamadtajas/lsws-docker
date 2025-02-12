@extends('backend.layouts.app')

@section('content')
    <div class="card">
        <form class="" action="" id="sort_orders" method="GET" autocomplete="off">
            <div class="card-header row gutters-5 border-0">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('Orders Details') }}</h5>
                </div>

                <div class="col-lg-2 ml-auto">
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
                <div class="col-lg-2 ml-auto">
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
                            placeholder="{{ translate('Type Order code') }}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="product_name" name="product_name"
                            @isset($product_name) value="{{ $product_name }}" @endisset
                            placeholder="{{ translate('Type Product Name') }}">
                    </div>
                </div>
            </div>

            <div class="card-header row gutters-5 border-0">
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="category_name" name="category_name"
                            @isset($category_name) value="{{ $category_name }}" @endisset
                            placeholder="{{ translate('Type Category Name') }}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="seller_name" name="seller_name"
                            @isset($seller_name) value="{{ $seller_name }}" @endisset
                            placeholder="{{ translate('Type Seller Name') }}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="seller_email" name="seller_email"
                            @isset($seller_email) value="{{ $seller_email }}" @endisset
                            placeholder="{{ translate('Type Seller Email') }}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="seller_tax_number" name="seller_tax_number"
                            @isset($seller_tax_number) value="{{ $seller_tax_number }}" @endisset
                            placeholder="{{ translate('Type Seller Tax Number') }}">
                    </div>
                </div>
                <div class="col-lg-2">
                    <div class="form-group mb-0">
                        <select class="form-control aiz-selectpicker" id="invoice_option" name="invoice_option"
                            onchange="toggleInvoiceInput(this)">
                            <option value="">{{ translate('-- Invoice Number --') }}</option>
                            <option value="null" @if ($invoice_option == 'null') selected @endif>
                                {{ translate('Null Invoice Number') }}</option>
                            <option value="not_null" @if ($invoice_option == 'not_null') selected @endif>
                                {{ translate('Not Null Invoice Number') }}</option>
                            <option value="add_number" @if ($invoice_option == 'add_number') selected @endif>
                                {{ translate('search Invoice Number') }}</option>
                        </select>
                        <input type="text"
                            class="form-control mt-2 @if ($invoice_option == 'add_number') d-block @else d-none @endif "
                            id="invoice_number" name="invoice_number"
                            @isset($invoice_number) value="{{ $invoice_number }}" @endisset
                            placeholder="{{ translate('Type Invoice Number') }}">
                    </div>
                </div>
                <div class="col">
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
                            <th>#</th>
                            <th>{{ translate('Order Code') }}</th>
                            <th>{{ translate('Trendyol Order Code') }}</th>
                            <th>{{ translate('Tracking Code') }}</th>
                            <th>{{ translate('Product Name') }}</th>
                            <th data-breakpoints="md">{{ translate('Product Image') }}</th>
                            <th data-breakpoints="md">{{ translate('Category Name') }}</th>
                            <th data-breakpoints="md">{{ translate('Num. of Items') }}</th>
                            <th data-breakpoints="md">{{ translate('Customer') }}</th>
                            <th data-breakpoints="md">{{ translate('Seller Name') }}</th>
                            <th data-breakpoints="lg">{{ translate('Seller Official Name') }}</th>
                            <th data-breakpoints="xl">{{ translate('Seller Email') }}</th>
                            <th data-breakpoints="xl">{{ translate('Seller Tax Number') }}</th>
                            <th data-breakpoints="xl">{{ translate('Amount') }}</th>
                            <th data-breakpoints="xl">{{ translate('invoice Number') }}</th>
                            <th data-breakpoints="xl">{{ translate('Delivery Status') }}</th>
                            <th data-breakpoints="xl">{{ translate('Payment method') }}</th>
                            <th data-breakpoints="xl">{{ translate('Payment Status') }}</th>
                            <th data-breakpoints="xl">{{ translate('Date') }}</th>
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
                                        {{ $orderDetails->trendyol_order->trendyol_orderNumber ?? '' }}
                                    </td>
                                    <td>
                                        {{ $orderDetails->tracking_code }}
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

                                    @if ($orderDetails->trendyol == 1)
                                        @php
                                            $merchant = $orderDetails->trendyolMerchant;
                                        @endphp
                                        <td>
                                            {{ $merchant->name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $merchant->official_name ?? '' }}
                                        </td>
                                        <td>
                                            {{ $merchant->email ?? '' }}
                                        </td>
                                        <td>
                                            {{ $merchant->tax_number ?? '' }}
                                        </td>
                                    @else
                                        <td>
                                            @if ($order->shop)
                                                {{ $order->shop->user->name }}
                                            @else
                                                {{ translate('Inhouse Order') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($order->shop)
                                                {{ $order->shop->name }}
                                            @else
                                                {{ translate('Inhouse Order') }}
                                            @endif
                                        </td>
                                        <td>
                                            @if ($order->shop)
                                                {{ $order->shop->user->email }}
                                            @endif
                                        </td>
                                        <td></td>
                                    @endif
                                    <td>
                                        {{ single_price($orderDetails->price) }}
                                    </td>
                                    <td>
                                        {{ $orderDetails->invoice_number }}
                                        <a href="#" class="btn btn-soft-info btn-icon btn-circle btn-sm "
                                            onclick="editInvoiceNumber(`{{ route('orders.edit_invoice_number', $orderDetails->id) }}`);"
                                            title="{{ translate('Edit invoice number') }}">
                                            <i class="las la-pencil-alt"></i>
                                        </a>
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
        </form>
    </div>
@endsection
@section('modal')
    <div id="editInvoiceNumber-modal" class="modal fade">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Edit Invoice Number') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <form action="" method="post" id="editInvoiceNumber-link">
                        @csrf
                        <input type="text" class="form-control mb-3 rounded-0" name="invoice_number" required>
                        <p class="mt-1 fs-14">{{ translate('Enter invoice number') }}</p>
                        <button type="button" class="btn btn-secondary rounded-0 mt-2"
                            data-dismiss="modal">{{ translate('Cancel') }}</button>
                        <button class="btn btn-primary rounded-0 mt-2">{{ translate('Save') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        function editInvoiceNumber(url) {
            $("#editInvoiceNumber-modal").modal("show");
            $("#editInvoiceNumber-link").attr("action", url);
        };
    </script>
    <script>
        function toggleInvoiceInput(select) {
            var input = document.getElementById('invoice_number');
            if (select.value === 'add_number') {
                input.classList.remove('d-none');
            } else {
                input.classList.add('d-none');
                input.value = '';
            }
        }
    </script>
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

                const formData = $('#sort_orders').serialize(); // Collects all input values in the form as a query string
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
                            link.setAttribute('download', 'ordersDetails.xlsx');
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
