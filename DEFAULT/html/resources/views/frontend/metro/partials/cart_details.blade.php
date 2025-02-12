<div class="container">
    @if ($carts && count($carts) > 0)
        <div class="row">
            <div class="col-xxl-8 col-xl-10 mx-auto">
                <div class="border bg-white p-3 p-lg-4 text-left">
                    <div class="mb-4">
                        <!-- Headers -->
                        <div class="row gutters-5 d-none d-lg-flex border-bottom mb-3 pb-3 text-secondary fs-12">
                            <div class="col col-md-1 fw-600">{{ translate('Qty') }}</div>
                            <div class="col-md-5 fw-600">{{ translate('Product') }}</div>
                            <div class="col fw-600">{{ translate('Price') }}</div>
                            <div class="col fw-600">{{ translate('Tax') }}</div>
                            <div class="col fw-600">{{ translate('Total') }}</div>
                            <div class="col-auto fw-600">{{ translate('Remove') }}</div>
                        </div>
                        <!-- Cart Items -->
                        <ul class="list-group list-group-flush">
                            @php
                                $total = 0;
                            @endphp
                            @foreach ($carts as $key => $cartItem)
                                @php
                                    if ($cartItem->trendyol == 0 && $cartItem->provider_id == null) {
                                        $product = get_single_product($cartItem['product_id']);
                                        $product_stock = $product->stocks
                                            ->where('variant', $cartItem['variation'])
                                            ->first();
                                        $total =
                                            $total +
                                            cart_product_price($cartItem, $product, false) * $cartItem['quantity'];
                                        $product_name_with_choice = $product->getTranslation('name');
                                        if ($cartItem['variation'] != null) {
                                            $product_name_with_choice =
                                                $product->getTranslation('name') . ' - ' . $cartItem['variation'];
                                        }
                                    } elseif ($cartItem->trendyol == 1) {
                                        $accaessToken = trendyol_account_login();
                                        $product = trendyol_product_details(
                                            $accaessToken,
                                            $cartItem['product_id'],
                                            $cartItem['urunNo'],
                                        );
                                        $product_stock = $product['stock'] ?? 0;
                                        $total =
                                            $total +
                                            floatval(str_replace(',', '', $product['new_price'] ?? 0)) *
                                                $cartItem['quantity'];
                                        $product_name_with_choice = $product['name'] ?? translate('Product Not Found');
                                        if ($cartItem['variation'] != null) {
                                            $product_name_with_choice =
                                                $product['name'] ??
                                                translate('Product Not Found') . ' - ' . $cartItem['variation'];
                                        }
                                    } elseif ($cartItem->provider_id != null) {
                                        $product = $providerProducts[$cartItem->id];
                                        $product_stock = $product['stock'] ?? 0;
                                        $total = $total + $product['new_price'] ?? 0 * $cartItem['quantity'];
                                        $product_name_with_choice = $product['name'] ?? translate('Product Not Found');
                                        if ($cartItem['variation'] != null) {
                                            $product_name_with_choice =
                                                $product['name'] ??
                                                translate('Product Not Found') . ' - ' . $cartItem['variation'];
                                        }
                                    }
                                @endphp
                                <li class="list-group-item px-0">
                                    <div class="row gutters-5 align-items-center">
                                        <!-- Quantity -->
                                        <div class="col-md-1 col order-1 order-md-0">
                                            @if ($product['digital'] != 1 && isset($product['auction_product']) && $product['auction_product'] == 0)
                                                <div
                                                    class="d-flex flex-column align-items-start aiz-plus-minus mr-2 ml-0">
                                                    @if ($cartItem->trendyol == 0 && $cartItem->provider_id == null)
                                                        <button
                                                            class="btn col-auto btn-icon btn-sm btn-circle btn-light"
                                                            type="button" data-type="plus"
                                                            data-field="quantity[{{ $cartItem['id'] }}]">
                                                            <i class="las la-plus"></i>
                                                        </button>
                                                        <input type="number" name="quantity[{{ $cartItem['id'] }}]"
                                                            class="col border-0 text-left px-0 flex-grow-1 fs-14 input-number"
                                                            placeholder="1" value="{{ $cartItem['quantity'] }}"
                                                            min="{{ $product->min_qty }}"
                                                            max="{{ $product_stock->qty }}"
                                                            onchange="updateQuantity({{ $cartItem['id'] }}, this)"
                                                            style="padding-left:0.75rem !important;">
                                                        <button
                                                            class="btn col-auto btn-icon btn-sm btn-circle btn-light"
                                                            type="button" data-type="minus"
                                                            data-field="quantity[{{ $cartItem['id'] }}]">
                                                            <i class="las la-minus"></i>
                                                        </button>
                                                    @elseif($cartItem->trendyol == 1)
                                                        <button
                                                            class="btn col-auto btn-icon btn-sm btn-circle btn-light"
                                                            type="button" data-type="plus"
                                                            data-field="quantity[{{ $cartItem['id'] }}]">
                                                            <i class="las la-plus"></i>
                                                        </button>
                                                        <input type="number" name="quantity[{{ $cartItem['id'] }}]"
                                                            class="col border-0 text-left px-0 flex-grow-1 fs-14 input-number"
                                                            placeholder="1" value="{{ $cartItem['quantity'] }}"
                                                            min="{{ $product['min_qty'] }}" max="{{ $product_stock }}"
                                                            onchange="updateQuantity({{ $cartItem['id'] }}, this)"
                                                            style="padding-left:0.75rem !important;">
                                                        <button
                                                            class="btn col-auto btn-icon btn-sm btn-circle btn-light"
                                                            type="button" data-type="minus"
                                                            data-field="quantity[{{ $cartItem['id'] }}]">
                                                            <i class="las la-minus"></i>
                                                        </button>
                                                    @elseif($cartItem->provider_id != null)
                                                        <button
                                                            class="btn col-auto btn-icon btn-sm btn-circle btn-light"
                                                            type="button" data-type="plus"
                                                            data-field="quantity[{{ $cartItem['id'] }}]">
                                                            <i class="las la-plus"></i>
                                                        </button>
                                                        <input type="number" name="quantity[{{ $cartItem['id'] }}]"
                                                            class="col border-0 text-left px-0 flex-grow-1 fs-14 input-number"
                                                            placeholder="1" value="{{ $cartItem['quantity'] }}"
                                                            min="{{ $product['min_qty'] }}" max="{{ $product_stock }}"
                                                            onchange="updateQuantity({{ $cartItem['id'] }}, this)"
                                                            style="padding-left:0.75rem !important;">
                                                        <button
                                                            class="btn col-auto btn-icon btn-sm btn-circle btn-light"
                                                            type="button" data-type="minus"
                                                            data-field="quantity[{{ $cartItem['id'] }}]">
                                                            <i class="las la-minus"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            @elseif(isset($product['auction_product']) && $product['auction_product'] == 1)
                                                <span class="fw-700 fs-14">1</span>
                                            @endif
                                        </div>
                                        <!-- Product Image & name -->
                                        <div class="col-md-5 d-flex align-items-center mb-2 mb-md-0">

                                            @if ($cartItem->trendyol == 0 && $cartItem->provider_id == null)
                                                <a href="{{ route('product', $product->slug) }}" target="_blank"
                                                    class="text-reset d-flex align-items-center flex-grow-1">
                                                    <span class="mr-2 ml-0">
                                                        <img src="{{ uploaded_asset($product->thumbnail_img) }}"
                                                            class="img-fit size-70px"
                                                            alt="{{ $product->getTranslation('name') }}"
                                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                                                    </span>
                                                    <span class="fs-14">{{ $product_name_with_choice }}</span>
                                                </a>
                                            @elseif($cartItem->trendyol == 1)
                                                <a href="{{ route('trendyol-product', ['id' => $cartItem['product_id'], 'urunNo' => $cartItem->urunNo]) }}"
                                                    target="_blank"
                                                    class="text-reset d-flex align-items-center flex-grow-1">
                                                    <span class="mr-2 ml-0">
                                                        <img src="{{ $product['photos'][0] ?? static_asset('assets/img/placeholder.webp') }}"
                                                            class="img-fit size-70px"
                                                            alt="{{ $product['name'] ?? '' }}"
                                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                                                    </span>
                                                    <span class="fs-14">{{ $product_name_with_choice }}</span>
                                                </a>
                                            @elseif($cartItem->provider_id != null)
                                                <a href="{{ route('product.provider', [$product['provider'], $product['id']]) }}"
                                                    target="_blank"
                                                    class="text-reset d-flex align-items-center flex-grow-1">
                                                    <span class="mr-2 ml-0">
                                                        <img src="{{ $product['thumbnail'] ?? static_asset('assets/img/placeholder.webp') }}"
                                                            class="img-fit size-70px"
                                                            alt="{{ $product['name'] ?? '' }}"
                                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                                                    </span>
                                                    <span class="fs-14">{{ $product_name_with_choice }}</span>
                                                </a>
                                            @endif

                                        </div>
                                        <!-- Price -->
                                        <div class="col-md col-4 order-2 order-md-0 my-3 my-md-0">
                                            <span
                                                class="opacity-60 fs-12 d-block d-md-none">{{ translate('Price') }}</span>
                                            <span class="fw-700 fs-14">
                                                @if ($cartItem->trendyol == 0 && $cartItem->provider_id == null)
                                                    {{ cart_product_price($cartItem, $product, true, false) }}
                                                @elseif($cartItem->trendyol == 1)
                                                    {{ single_price(floatval(str_replace(',', '', $product['new_price'] ?? 0))) }}
                                                @elseif($cartItem->provider_id != null)
                                                    {{ single_price($product['new_price'] ?? 0) }}
                                                @endif
                                            </span>
                                        </div>
                                        <!-- Tax -->
                                        <div class="col-md col-4 order-3 order-md-0 my-3 my-md-0">
                                            <span
                                                class="opacity-60 fs-12 d-block d-md-none">{{ translate('Tax') }}</span>
                                            <span class="fw-700 fs-14">
                                                @if ($cartItem->trendyol == 0 && $cartItem->provider_id == null)
                                                    {{ cart_product_tax($cartItem, $product) }}
                                                @elseif($cartItem->trendyol == 1)
                                                    {{ single_price($product['tax'] ?? 0) }}
                                                @elseif($cartItem->provider_id != null)
                                                    {{ single_price($product['tax'] ?? 0) }}
                                                @endif
                                            </span>
                                        </div>
                                        <!-- Total -->
                                        <div class="col-md col-5 order-4 order-md-0 my-3 my-md-0">
                                            <span
                                                class="opacity-60 fs-12 d-block d-md-none">{{ translate('Total') }}</span>
                                            <span class="fw-700 fs-16 text-primary">
                                                @if ($cartItem->trendyol == 0 && $cartItem->provider_id == null)
                                                    {{ single_price(cart_product_price($cartItem, $product, false) * $cartItem['quantity']) }}
                                                @elseif($cartItem->trendyol == 1)
                                                    {{ single_price(floatval(str_replace(',', '', $product['new_price'] ?? 0)) * $cartItem['quantity']) }}
                                                @elseif($cartItem->provider_id != null)
                                                    {{ single_price($product['new_price'] ?? 0 * $cartItem['quantity']) }}
                                                @endif
                                            </span>
                                        </div>
                                        <!-- Remove From Cart -->
                                        <div class="col-md-auto col-6 order-5 order-md-0 text-right">
                                            <a href="javascript:void(0)"
                                                onclick="removeFromCartView(event, {{ $cartItem['id'] }})"
                                                class="btn btn-icon btn-sm btn-soft-primary bg-soft-secondary-base hov-bg-primary btn-circle">
                                                <i class="las la-trash fs-16"></i>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Subtotal -->
                    <div class="px-0 py-2 mb-4 border-top d-flex justify-content-between">
                        <span class="opacity-60 fs-14">{{ translate('Subtotal') }}</span>
                        <span class="fw-700 fs-16">{{ single_price($total) }}</span>
                    </div>
                    <div class="row align-items-center">
                        <!-- Return to shop -->
                        <div class="col-md-6 text-center text-md-left order-1 order-md-0">
                            <a href="{{ route('home') }}" class="btn btn-link fs-14 fw-700 px-0">
                                <i class="las la-arrow-left fs-16"></i>
                                {{ translate('Return to shop') }}
                            </a>
                        </div>
                        <!-- Continue to Shipping -->
                        <div class="col-md-6 text-center text-md-right">
                            @if (Auth::check())
                                <a href="{{ route('checkout.shipping_info') }}"
                                    class="btn btn-primary fs-14 fw-700 rounded-0 px-4">
                                    {{ translate('Continue to Shipping') }}
                                </a>
                            @else
                                <button class="btn btn-primary fs-14 fw-700 rounded-0 px-4"
                                    onclick="showLoginModal()">{{ translate('Continue to Shipping') }}</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-xl-8 mx-auto">
                <div class="border bg-white p-4">
                    <!-- Empty cart -->
                    <div class="text-center p-3">
                        <i class="las la-frown la-3x opacity-60 mb-3"></i>
                        <h3 class="h4 fw-700">{{ translate('Your Cart is empty') }}</h3>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<script type="text/javascript">
    AIZ.extra.plusMinus();
</script>
