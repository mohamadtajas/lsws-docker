<div class="text-left">
    <!-- Product Name -->
    <h2 class="mb-4 fs-16 fw-700 text-dark">
        {{ $detailedProduct['name'] }}
    </h2>

    <div class="row align-items-center mb-3">
        <!-- Review -->
        @if ($detailedProduct['auction_product'] != 1)
            <div class="col-12">
                @php
                    $total = 0;
                    $total += $detailedProduct['contRating'];
                @endphp
                <span class="rating rating-mr-1">
                    {{ renderStarRating($detailedProduct['rating']) }}
                </span>
                <span class="ml-1 opacity-50 fs-14">({{ $total }}
                    {{ translate('reviews') }})</span>
            </div>
        @endif
        <!-- Estimate Shipping Time -->
        @if ($detailedProduct['digital'] == 0 && $detailedProduct['est_shipping_days'] > 0)
            <div class="col-auto fs-14 mt-1">
                <small class="mr-1 opacity-50 fs-14">{{ translate('Estimate Shipping Time') }}:</small>
                <span class="fw-500">{{ $detailedProduct['est_shipping_days'] }} {{ translate('Days') }}</span>
            </div>
        @endif
        <!-- In stock -->
        @if ($detailedProduct['digital'] == 1)
            <div class="col-12 mt-1">
                <span class="badge badge-md badge-inline badge-pill badge-success" style="width :100px">{{ translate('In stock') }}</span>
            </div>
        @endif
    </div>
    <div class="row align-items-center">
        @if (get_setting('product_query_activation') == 1)
            <!-- Ask about this product -->
            <div class="col-xl-3 col-lg-4 col-md-3 col-sm-4 mb-3">
                <a href="javascript:void();" onclick="goToView('product_query')"
                    class="text-primary fs-14 fw-600 d-flex">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 32 32">
                        <g id="Group_25571" data-name="Group 25571" transform="translate(-975 -411)">
                            <g id="Path_32843" data-name="Path 32843" transform="translate(975 411)" fill="#fff">
                                <path
                                    d="M 16 31 C 11.9933500289917 31 8.226519584655762 29.43972969055176 5.393400192260742 26.60659980773926 C 2.560270071029663 23.77347946166992 1 20.00665092468262 1 16 C 1 11.9933500289917 2.560270071029663 8.226519584655762 5.393400192260742 5.393400192260742 C 8.226519584655762 2.560270071029663 11.9933500289917 1 16 1 C 20.00665092468262 1 23.77347946166992 2.560270071029663 26.60659980773926 5.393400192260742 C 29.43972969055176 8.226519584655762 31 11.9933500289917 31 16 C 31 20.00665092468262 29.43972969055176 23.77347946166992 26.60659980773926 26.60659980773926 C 23.77347946166992 29.43972969055176 20.00665092468262 31 16 31 Z"
                                    stroke="none" />
                                <path
                                    d="M 16 2 C 12.26045989990234 2 8.744749069213867 3.456249237060547 6.100500106811523 6.100500106811523 C 3.456249237060547 8.744749069213867 2 12.26045989990234 2 16 C 2 19.73954010009766 3.456249237060547 23.2552490234375 6.100500106811523 25.89949989318848 C 8.744749069213867 28.54375076293945 12.26045989990234 30 16 30 C 19.73954010009766 30 23.2552490234375 28.54375076293945 25.89949989318848 25.89949989318848 C 28.54375076293945 23.2552490234375 30 19.73954010009766 30 16 C 30 12.26045989990234 28.54375076293945 8.744749069213867 25.89949989318848 6.100500106811523 C 23.2552490234375 3.456249237060547 19.73954010009766 2 16 2 M 16 0 C 24.8365592956543 0 32 7.163440704345703 32 16 C 32 24.8365592956543 24.8365592956543 32 16 32 C 7.163440704345703 32 0 24.8365592956543 0 16 C 0 7.163440704345703 7.163440704345703 0 16 0 Z"
                                    stroke="none" fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                            </g>
                            <path id="Path_32842" data-name="Path 32842"
                                d="M28.738,30.935a1.185,1.185,0,0,1-1.185-1.185,3.964,3.964,0,0,1,.942-2.613c.089-.095.213-.207.361-.344.735-.658,2.252-2.032,2.252-3.555a2.228,2.228,0,0,0-2.37-2.37,2.228,2.228,0,0,0-2.37,2.37,1.185,1.185,0,1,1-2.37,0,4.592,4.592,0,0,1,4.74-4.74,4.592,4.592,0,0,1,4.74,4.74c0,2.577-2.044,4.432-3.028,5.333l-.284.255a1.89,1.89,0,0,0-.243.948A1.185,1.185,0,0,1,28.738,30.935Zm0,3.561a1.185,1.185,0,0,1-.835-2.026,1.226,1.226,0,0,1,1.671,0,1.061,1.061,0,0,1,.148.184,1.345,1.345,0,0,1,.113.2,1.41,1.41,0,0,1,.065.225,1.138,1.138,0,0,1,0,.462,1.338,1.338,0,0,1-.065.219,1.185,1.185,0,0,1-.113.207,1.06,1.06,0,0,1-.148.184A1.185,1.185,0,0,1,28.738,34.5Z"
                                transform="translate(962.004 400.504)"
                                fill="{{ get_setting('secondary_base_color', '#ffc519') }}" />
                        </g>
                    </svg>
                    <span class="ml-2 text-primary animate-underline-blue">{{ translate('Product Inquiry') }}</span>
                </a>
            </div>
        @endif
        <div class="col mb-3">
            @if ($detailedProduct['auction_product'] != 1)
                <div class="d-flex">
                    <!-- Add to wishlist button -->
                    <a href="javascript:void(0)"
                        onclick="addToWishListProvider({{ $detailedProduct['id'] }} , {{ $detailedProduct['provider_id'] }})"
                        class="mr-3 fs-14 text-dark opacity-60 has-transitiuon hov-opacity-100">
                        <i class="la la-heart-o mr-1"></i>
                        {{ translate('Add to Wishlist') }}
                    </a>
                    <!-- Add to compare button -->
                    <a href="javascript:void(0)"
                        onclick="addToCompareProvider({{ $detailedProduct['id'] }} , {{ $detailedProduct['provider_id'] }})"
                        class="fs-14 text-dark opacity-60 has-transitiuon hov-opacity-100">
                        <i class="las la-sync mr-1"></i>
                        {{ translate('Add to Compare') }}
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Brand Logo & Name -->
    @if ($detailedProduct['brandId'] != null)
        <div class="d-flex flex-wrap align-items-center mb-3">
            <span class="text-secondary fs-14 fw-400 mr-4 w-50px">{{ translate('Brand') }}</span><br>
            <a href="{{ $detailedProduct['brandLink'] }}"
                class="text-reset hov-text-primary fs-14 fw-700 notranslate">{{ $detailedProduct['brandName'] }}</a>
        </div>
    @endif

    <!-- Category Name -->
    @if ($detailedProduct['categoryName'] != null)
        <div class="d-flex flex-wrap align-items-center mb-3">
            <span class="text-secondary fs-14 fw-400 mr-4 w-50px">{{ translate('Main category') }}</span><br>
            <a href="{{ route('products.category.provider', [strtolower($category->provider->name), $category->id]) }}"
                class="text-reset hov-text-primary fs-14 fw-700">{{ $category->name }}</a>
        </div>
    @endif

    <hr>

    <!-- For auction product -->
    @if ($detailedProduct['auction_product'] == 1)
        <div class="row no-gutters mb-3">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Auction Will End') }}</div>
            </div>
            <div class="col-sm-10">
                @if ($detailedProduct->auction_end_date > strtotime('now'))
                    <div class="aiz-count-down align-items-center"
                        data-date="{{ date('Y/m/d H:i:s', $detailedProduct->auction_end_date) }}"></div>
                @else
                    <p>{{ translate('Ended') }}</p>
                @endif

            </div>
        </div>

        <div class="row no-gutters mb-3">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Starting Bid') }}</div>
            </div>
            <div class="col-sm-10">
                <span class="opacity-50 fs-20">
                    {{ single_price($detailedProduct->starting_bid) }}
                </span>
                @if ($detailedProduct->unit != null)
                    <span class="opacity-70">/{{ $detailedProduct->getTranslation('unit') }}</span>
                @endif
            </div>
        </div>

        @if (Auth::check() &&
                Auth::user()->product_bids->where('product_id', $detailedProduct->id)->first() != null)
            <div class="row no-gutters mb-3">
                <div class="col-sm-2">
                    <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('My Bidded Amount') }}</div>
                </div>
                <div class="col-sm-10">
                    <span class="opacity-50 fs-20">
                        {{ single_price(Auth::user()->product_bids->where('product_id', $detailedProduct->id)->first()->amount) }}
                    </span>
                </div>
            </div>
            <hr>
        @endif

        @php $highest_bid = $detailedProduct->bids->max('amount'); @endphp
        <div class="row no-gutters my-2 mb-3">
            <div class="col-sm-2">
                <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Highest Bid') }}</div>
            </div>
            <div class="col-sm-10">
                <strong class="h3 fw-600 text-primary">
                    @if ($highest_bid != null)
                        {{ single_price($highest_bid) }}
                    @endif
                </strong>
            </div>
        </div>
    @else
        <!-- Without auction product -->
        @if ($detailedProduct['wholesale_product'] == 1)
            <!-- Wholesale -->
            <table class="table mb-3">
                <thead>
                    <tr>
                        <th class="border-top-0">{{ translate('Min Qty') }}</th>
                        <th class="border-top-0">{{ translate('Max Qty') }}</th>
                        <th class="border-top-0">{{ translate('Unit Price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detailedProduct->stocks->first()->wholesalePrices as $wholesalePrice)
                        <tr>
                            <td>{{ $wholesalePrice->min_qty }}</td>
                            <td>{{ $wholesalePrice->max_qty }}</td>
                            <td>{{ single_price($wholesalePrice->price) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <!-- Without Wholesale -->
            @if ($detailedProduct['unit_price'] != $detailedProduct['new_price'])
                <div class="row no-gutters mb-3">
                    <div class="col-sm-2">
                        <div class="text-secondary fs-14 fw-400">{{ translate('Price') }}</div>
                    </div>
                    <div class="col-sm-10">
                        <div class="d-flex align-items-center">
                            <!-- Discount Price -->
                            <input type="hidden" value="{{ $detailedProduct['new_price'] }}" id="primary_price">
                            <strong class="fs-16 fw-700 text-primary">
                                {{ single_price($detailedProduct['new_price']) }}
                            </strong>
                            <!-- Home Price -->
                            <del class="fs-14 opacity-60 ml-2">
                                {{ single_price($detailedProduct['unit_price']) }}
                            </del>
                            <!-- Unit -->
                            @if ($detailedProduct['digital'] == 0 && $detailedProduct['unit'] != null)
                                <span
                                    class="opacity-70 ml-1">{{ currency_symbol() }}/{{ $detailedProduct['unit'] }}</span>
                            @endif
                            <!-- Discount percentage -->
                            @if ($detailedProduct['discount_price'] > 0)
                                <span class="bg-primary ml-2 fs-11 fw-700 text-white w-35px text-center p-1"
                                    style="padding-top:2px;padding-bottom:2px;">-{{ $detailedProduct['discount_price'] }}%</span>
                            @endif
                            <!-- Club Point -->
                        </div>
                    </div>
                </div>
            @else
                <div class="row no-gutters mb-3">
                    <div class="col-sm-2">
                        <div class="text-secondary fs-14 fw-400">{{ translate('Price') }}</div>
                    </div>
                    <div class="col-sm-10">
                        <div class="d-flex align-items-center">
                            <!-- Discount Price -->
                            <input type="hidden" value="{{ $detailedProduct['new_price'] }}" id="primary_price">
                            <strong class="fs-16 fw-700 text-primary">
                                {{ single_price($detailedProduct['new_price']) }}
                            </strong>
                            <!-- Unit -->
                            @if ($detailedProduct['digital'] == 0 && $detailedProduct['unit'] != null)
                                <span
                                    class="opacity-70">{{ currency_symbol() }}/{{ $detailedProduct['unit'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endif

    @if ($detailedProduct['auction_product'] != 1)
        <form id="option-choice-form" class="notranslate">
            @csrf
            <input type="hidden" name="id" value="{{ $detailedProduct['id'] }}">
            <input type="hidden" name="provider" value="{{ $detailedProduct['provider_id'] }}">

            @if ($detailedProduct['digital'] == 0)
                <!-- Choice Options -->
                @if ($detailedProduct['choice_options'] != null)
                    @if ($detailedProduct['choice_options'][0]['values'] != null)
                        @foreach ($detailedProduct['choice_options'] as $choice)
                            <div class="row no-gutters my-3">
                                <div class="col-3">
                                    <div class="text-secondary fs-14 fw-400 mt-2 ">{{ $choice['name'] }}</div>
                                </div>
                                <div class="col-9">
                                    <div class="aiz-radio-inline">
                                        @foreach ($choice['values'] as $key => $value)
                                            <label class="aiz-megabox pl-0 mr-2 mb-0">
                                                <input type="radio" name="attribute_id_{{ $choice['id'] }}"
                                                    value="{{ $key }}">
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
                        <div class="col-2">
                            <div class="text-secondary fs-14 fw-400 mt-2 ">{{ translate('Property') }}</div>
                        </div>
                        <div class="col-10">
                            <div class="container">
                                <div class="aiz-radio-inline">
                                    <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                                        data-items="7" data-xxl-items="7" data-xl-items="5" data-lg-items="4"
                                        data-md-items="6" data-sm-items="4" data-xs-items="3" data-arrows="true"
                                        data-dots="false">
                                        @foreach ($propreties as $key => $value)
                                            <div class="carousel-box overflow-hidden hov-scale-img px-0">
                                                <label class="aiz-megabox pl-0 mr-2 mb-0">
                                                    <input type="radio" class="not">
                                                    <span
                                                        class="aiz-megabox-elem rounded-0 d-flex align-items-center justify-content-center py-1 px-3 mt-1 "
                                                        style="flex-wrap: wrap;flex-direction: column;">
                                                        <p class="m-0"><img src="{{ $value['img'] }} "
                                                                width="50px"></p>
                                                        <p class="m-0" style="font-size:12px; text-align: center;">
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
                <!-- Quantity + Add to cart -->
                <div class="row no-gutters mb-3">
                    <div class="col-sm-2">
                        <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Quantity') }}</div>
                    </div>
                    <div class="col-sm-10">
                        <div class="product-quantity d-flex align-items-center">
                            <div class="row no-gutters align-items-center aiz-plus-minus mr-3" style="width: 130px;">
                                <button class="btn col-auto btn-icon btn-sm btn-light rounded-0" type="button"
                                    data-type="minus" data-field="quantity" disabled=""
                                    onclick="
                                       document.getElementById('chosen_price').innerHTML = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format((parseFloat((document.getElementById('primary_price').value).replace(/,/g, '')) * (parseFloat(document.getElementById('qty').value) - 1) ).toFixed(2)) ;">
                                    <i class="las la-minus"></i>
                                </button>
                                <input type="number" id="qty" name="quantity"
                                    class="col border-0 text-center flex-grow-1 fs-16 input-number" placeholder="1"
                                    value="{{ $detailedProduct['min_qty'] }}"
                                    min="{{ $detailedProduct['min_qty'] }}" max="{{ $qty }}"
                                    lang="en">
                                <button class="btn col-auto btn-icon btn-sm btn-light rounded-0" type="button"
                                    data-type="plus" data-field="quantity"
                                    onclick="
                                        document.getElementById('chosen_price').innerHTML = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format((parseFloat((document.getElementById('primary_price').value).replace(/,/g, '')) * (parseFloat(document.getElementById('qty').value) + 1) ).toFixed(2)) ;">
                                    <i class="las la-plus"></i>
                                </button>
                            </div>
                            @php
                                $qty = $detailedProduct['stock'];
                            @endphp
                            <div class="avialable-amount opacity-60">
                                (<span id="available-quantity">{{ $qty }}</span>
                                {{ translate('available') }})
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Quantity -->
                <input type="hidden" name="quantity" value="1">
            @endif

            <!-- Total Price -->
            <div class="row no-gutters pb-3" id="chosen_price_div">
                <div class="col-sm-2">
                    <div class="text-secondary fs-14 fw-400 mt-1">{{ translate('Total Price') }}</div>
                </div>
                <div class="col-sm-10">
                    <div class="product-price">
                        <strong id="chosen_price" class="fs-20 fw-700 text-primary">
                            {{ single_price($detailedProduct['new_price']) }}
                        </strong>
                    </div>
                </div>
            </div>

        </form>
    @endif

    <!-- Add to cart & Buy now Buttons -->
    <div class="mt-3">
        @if ($detailedProduct['digital'] == 0)
            @if ($qty > 0)
                <button type="button"
                    class="btn btn-secondary-base mr-2 add-to-cart fw-600 min-w-150px rounded-0 text-white"
                    @if (Auth::check()) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                    <i class="las la-shopping-bag"></i> {{ translate('Add to cart') }}
                </button>
            @else
                <button type="button" class="btn btn-secondary out-of-stock fw-600">
                    <i class="la la-cart-arrow-down"></i> {{ translate('Out of Stock') }}
                </button>
            @endif
        @elseif ($detailedProduct['digital'] == 1)
            <button type="button"
                class="btn btn-secondary-base mr-2 add-to-cart fw-600 min-w-150px rounded-0 text-white"
                @if (Auth::check()) onclick="addToCart()" @else onclick="showLoginModal()" @endif>
                <i class="las la-shopping-bag"></i> {{ translate('Add to cart') }}
            </button>
        @endif
    </div>

    <!-- Share -->
    <div class="row no-gutters mt-4">
        <div class="col-sm-2">
            <div class="text-secondary fs-14 fw-400 mt-2">{{ translate('Share') }}</div>
        </div>
        <div class="col-sm-10">
            <div class="aiz-share"></div>
        </div>
    </div>
</div>
