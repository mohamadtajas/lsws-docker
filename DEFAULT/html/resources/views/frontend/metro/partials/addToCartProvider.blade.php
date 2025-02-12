@if(isset($null) && $null)
<div class="modal-body px-4 py-5 c-scrollbar-light">
    <div class="row">
        <div class="col-lg-6">
            <div class="row gutters-10 flex-row-reverse">
                <h2 class="mb-2 fs-16 fw-700 text-danger">
                    {{ translate('This product is not available!') }}
               </h2>
            </div>
        </div>
    </div>
</div>
@else
<div class="modal-body px-4 py-5 c-scrollbar-light">
    <div class="row">
        <!-- Product Image gallery -->
        <div class="col-lg-6">
            <div class="row gutters-10 flex-row-reverse">
                @php
                    $photos = $product['photos'];
                @endphp
                <div class="col">
                    <div class="aiz-carousel product-gallery" data-nav-for='.product-gallery-thumb' data-fade='true'
                        data-auto-height='false'>
                        @foreach ($photos as $photo)
                            <div class="carousel-box img-zoom rounded-0">
                                <img class="img-fluid lazyload" src="{{ static_asset('assets/img/placeholder.webp') }}"
                                    data-src="{{ $photo }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-auto w-90px">
                    <div class="aiz-carousel carousel-thumb product-gallery-thumb" data-items='5'
                        data-nav-for='.product-gallery' data-vertical='true' data-focus-select='true'>
                        @foreach ($photos as $photo)
                            <div class="carousel-box c-pointer border rounded-0">
                                <img class="lazyload mw-100 size-60px mx-auto"
                                    src="{{ static_asset('assets/img/placeholder.webp') }}"
                                    data-src="{{ $photo }}"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="text-left">
                <!-- Product name -->
                <h2 class="mb-2 fs-16 fw-700 text-dark">
                    {{ $product['name'] }}
                </h2>

                <!-- Product Price & Club Point -->
                @if ($product['unit_price'] != $product['new_price'])
                    <div class="row no-gutters mt-3 notranslate">
                        <div class="col-3">
                            <div class="text-secondary fs-14 fw-400">{{ translate('Price') }}</div>
                        </div>
                        <div class="col-9">
                            <div class="">
                                <input type="hidden" value="{{ $product['new_price'] }}" id="primary_price">
                                <strong class="fs-16 fw-700 text-primary">
                                    {{ single_price($product['new_price']) }}
                                </strong>
                                <del class="fs-14 opacity-60 ml-2">
                                    {{ single_price($product['unit_price']) }}
                                </del>
                                @if ($product['digital'] == 0 && $product['unit'] != null)
                                    <span class="opacity-70 ml-1">{{ currency_symbol() }} /
                                        {{ $product['unit'] }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="row no-gutters mt-3 notranslate">
                        <div class="col-3">
                            <div class="text-secondary fs-14 fw-400">{{ translate('Price') }}</div>
                        </div>
                        <div class="col-9">
                            <div class="">
                                <input type="hidden" value="{{ $product['new_price'] }}" id="primary_price">
                                <strong class="fs-16 fw-700 text-primary">
                                    {{ single_price($product['new_price']) }}
                                </strong>
                                @if ($product['digital'] == 0 && $product['unit'] != null)
                                    <span class="opacity-70 ml-1">{{ currency_symbol() }} /
                                        {{ single_price($product['unit']) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                @php
                    $qty = $product['stock'];
                @endphp

                <!-- Product Choice options form -->
                <form id="option-choice-form" class="notranslate">
                    @csrf
                    <input type="hidden" name="id" value="{{ $product['id'] }}">
                    <input type="hidden" name="provider" value="{{$product['provider_id']}}">
                    @if ($product['digital'] != 1)
                        <!-- Product Choice options -->
                        @if ($product['choice_options'] != null)
                            @if ($product['choice_options'][0]['values'] != null)
                            @foreach ($product['choice_options'] as $choice)
                                <div class="row no-gutters mt-3">
                                    <div class="col-3">
                                        <div class="text-secondary fs-14 fw-400 mt-2 ">{{ $choice['name'] }}</div>
                                    </div>
                                    <div class="col-9">
                                        <div class="aiz-radio-inline">
                                            @foreach ($choice['values'] as $key => $value)
                                                <label class="aiz-megabox pl-0 mr-2 mb-0">
                                                    <input type="radio" name="attribute_id_{{ $choice['id'] }}"
                                                        value="{{ $key }}"
                                                        @if ($product['varyantlar'] == $key) checked @endif
                                                        onchange="showAddToCartModal({{ $product['id'] }},1,{{ $value }})">
                                                    <span
                                                        class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center py-1 px-3 mt-1">
                                                        {{ $key }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            @endif
                        @endif
                        @if (count($propreties) > 0)
                            <div class="row no-gutters my-3">
                                <div class="col-12 mb-1">
                                    <div class="text-secondary fs-14 fw-400 mt-2 ">{{ translate('Property') }}</div>
                                </div>
                                <div class="col-12">
                                    <div class="container">
                                        <div class="aiz-radio-inline">
                                            <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                                                data-items="3" data-xxl-items="3" data-xl-items="3" data-lg-items="3"
                                                data-md-items="4" data-sm-items="4" data-xs-items="3" data-arrows="true"
                                                data-dots="false">
                                                @foreach ($propreties as $key => $value)
                                                    <div class="carousel-box overflow-hidden hov-scale-img px-0">
                                                        <label class="aiz-megabox pl-0 mr-2 mb-0">
                                                            <input type="radio" class="not"
                                                                @if ($product['id'] == $key) checked @endif
                                                                onchange="showAddToCartModal({{ $key }},1)">

                                                            <span
                                                                class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center py-1 px-3 mt-1 "
                                                                style="flex-wrap: wrap;flex-direction: column;">
                                                                <p class="m-0"><img src="{{ $value['img'] }} "
                                                                        width="50px"></p>
                                                                <p class="m-0"
                                                                    style="font-size:12px; text-align: center;">
                                                                    {{ $value['name'] }}</p>
                                                            </span>
                                                        </label>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Quantity -->
                        <div class="row no-gutters mt-3">
                            <div class="col-3">
                                <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Quantity') }}</div>
                            </div>
                            <div class="col-9">
                                <div class="product-quantity d-flex align-items-center">
                                    <div class="row no-gutters align-items-center aiz-plus-minus mr-3"
                                        style="width: 130px;">
                                        <button class="btn col-auto btn-icon btn-sm btn-light rounded-0"
                                            type="button" data-type="minus" data-field="quantity" disabled=""
                                            onclick="
                                       document.getElementById('chosen_price').innerHTML = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format((parseFloat((document.getElementById('primary_price').value).replace(/,/g, '')) * (parseFloat(document.getElementById('qty').value) - 1) ).toFixed(2)) ;">
                                            <i class="las la-minus"></i>
                                        </button>
                                        <input type="number" id="qty" name="quantity"
                                            class="col border-0 text-center flex-grow-1 fs-16 input-number"
                                            placeholder="1" value="{{$product['min_qty']}}" min="{{$product['min_qty']}}" max="{{ $qty }}"
                                            lang="en">
                                        <button class="btn col-auto btn-icon btn-sm btn-light rounded-0"
                                            type="button" data-type="plus" data-field="quantity"
                                            onclick="
                                        document.getElementById('chosen_price').innerHTML = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format((parseFloat((document.getElementById('primary_price').value).replace(/,/g, '')) * (parseFloat(document.getElementById('qty').value) + 1) ).toFixed(2)) ;">
                                            <i class="las la-plus"></i>
                                        </button>
                                    </div>
                                    <div class="avialable-amount opacity-60">
                                        <span id="available-quantity">{{ $qty }}</span>
                                        {{ translate('available') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Quantity -->
                        <input type="hidden" name="quantity" value="1">
                    @endif

                    <!-- Total Price -->
                    <div class="row no-gutters mt-3 pb-3" id="chosen_price_div">
                        <div class="col-3">
                            <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Total Price') }}</div>
                        </div>
                        <div class="col-9">
                            <div class="product-price">
                                <strong id="chosen_price" class="fs-20 fw-700 text-primary">
                                    {{ single_price($product['new_price']) }}
                                </strong>
                            </div>
                        </div>
                    </div>

                </form>

                <!-- Add to cart -->
                <div class="mt-3">
                    @if ($product['digital'] == 1)
                        <button type="button" class="btn btn-primary rounded-0 buy-now fw-600 add-to-cart"
                            onclick="addToCart()">
                            <i class="la la-shopping-cart"></i>
                            <span class="d-none d-md-inline-block">{{ translate('Add to cart') }}</span>
                        </button>
                    @elseif($qty > 0)
                        <button type="button" class="btn btn-primary rounded-0 buy-now fw-600 add-to-cart"
                            onclick="addToCart()">
                            <i class="la la-shopping-cart"></i>
                            <span class="d-none d-md-inline-block">{{ translate('Add to cart') }}</span>
                        </button>
                    @else
                        <button type="button" class="btn btn-secondary rounded-0 out-of-stock fw-600" disabled>
                            <i class="la la-cart-arrow-down"></i>{{ translate('Out of Stock') }}
                        </button>
                    @endif

                </div>

            </div>
        </div>
    </div>
</div>
@endif
