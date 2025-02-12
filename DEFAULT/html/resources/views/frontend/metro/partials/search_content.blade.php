@if(isset($searchResults))
<div class="">
    <div class="px-2 py-1 text-uppercase fs-10 text-right text-muted bg-soft-secondary">
            {{ translate('recent search') }}</div>
    @if (count($searchResults) > 0)
        <ul class="list-group list-group-raw">
            @foreach ($searchResults as $searchResult)
                <li class="list-group-item py-1" style="display: flex;justify-content: space-between;">
                <a class="text-reset hov-text-primary" style="width:355px; word-wrap:break-word;"
                        href="{{ route('suggestion.search', preg_replace('/[#\/\*]/', '', urlencode($searchResult->keyword))) }}">{{ $searchResult->keyword }}</a>
                    <a style="cursor: pointer; " onclick="deleteSearchResult({{ $searchResult->id }})">
                    <svg height="10px" id="Layer_1" style="enable-background:new 0 0 512 512; " version="1.1" viewBox="0 0 512 512" width="10px" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M437.5,386.6L306.9,256l130.6-130.6c14.1-14.1,14.1-36.8,0-50.9c-14.1-14.1-36.8-14.1-50.9,0L256,205.1L125.4,74.5  c-14.1-14.1-36.8-14.1-50.9,0c-14.1,14.1-14.1,36.8,0,50.9L205.1,256L74.5,386.6c-14.1,14.1-14.1,36.8,0,50.9  c14.1,14.1,36.8,14.1,50.9,0L256,306.9l130.6,130.6c14.1,14.1,36.8,14.1,50.9,0C451.5,423.4,451.5,400.6,437.5,386.6z"/>
                    </svg>
                    </a>
                </li>
            @endforeach
        </ul>
    @else
     <ul class="list-group list-group-raw">
        <li class="list-group-item py-1" style="display: flex;justify-content: space-between;">
            <strong>{{ translate('Sorry, nothing found ') }}</strong>
        </li>
    </ul>
   @endif
</div>
@else

<div class="">
    @if (sizeof($keywords) > 0)
        <div class="px-2 py-1 text-uppercase fs-10 text-right text-muted bg-soft-secondary">
            {{ translate('Popular Suggestions') }}</div>
        <ul class="list-group list-group-raw">
            @foreach ($keywords as $key => $keyword)
                <li class="list-group-item py-1">
                    <a class="text-reset hov-text-primary"
                        href="{{ route('suggestion.search', preg_replace('/[#\/\*]/', '', urlencode($keyword))) }}">{{ $keyword }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
<div class="">
    @if (count($categories) > 0)
        <div class="px-2 py-1 text-uppercase fs-10 text-right text-muted bg-soft-secondary">
            {{ translate('Category Suggestions') }}</div>
        <ul class="list-group list-group-raw">
            @foreach ($categories as $key => $category)
                <li class="list-group-item py-1">
                    <a class="text-reset hov-text-primary"
                        href="{{ route('products.category', $category->slug) }}">{{ $category->getTranslation('name') }}</a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
<div class="">
    @if (count($products) > 0)
        <div class="px-2 py-1 text-uppercase fs-10 text-right text-muted bg-soft-secondary">{{ translate('Products') }}
        </div>
        <ul class="list-group list-group-raw">
            @foreach ($products as $key => $product)
                <li class="list-group-item">
                    <a class="text-reset" href="{{ route('product', $product->slug) }}">
                        <div class="d-flex search-product align-items-center">
                            <div class="mr-3">
                                <img class="size-40px img-fit rounded"
                                    src="{{ uploaded_asset($product->thumbnail_img) }}">
                            </div>
                            <div class="flex-grow-1 overflow--hidden minw-0">
                                <div class="product-name cate fs-14 mb-5px">
                                    {{ $product->getTranslation('name') }}
                                </div>
                                <div class="">
                                    @if (home_base_price($product) != home_discounted_base_price($product))
                                        <del class="opacity-60 fs-15">{{ home_base_price($product) }}</del>
                                    @endif
                                    <span
                                        class="fw-600 fs-16 text-primary">{{ home_discounted_base_price($product) }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @elseif(count($trendyol_products) > 0 )
    <div class="px-2 py-1 text-uppercase fs-10 text-right text-muted bg-soft-secondary">{{ translate('Products') }}
    </div>
     <ul class="list-group list-group-raw">
            @foreach ($trendyol_products as $product)
                <li class="list-group-item">
                    <a class="text-reset" href="{{ route('trendyol-product', ['id' => $product['id'], 'urunNo' => $product['urunNo']]) }}">
                        <div class="d-flex search-product align-items-center">
                            <div class="mr-3">
                                <img class="size-40px img-fit rounded"
                                    src="{{ $product['thumbnail'] }}">
                            </div>
                            <div class="flex-grow-1 overflow--hidden minw-0">
                                <div class="product-name cate fs-14 mb-5px">
                                    {{ $product['name'] }}
                                </div>
                                <div class="">
                                     @if ( $product['unit_price'] !=  $product['new_price'] )
                                        <del class="opacity-60 fs-15">{{ $product['unit_price']}}</del>
                                    @endif
                                    <span
                                        class="fw-600 fs-16 text-primary">{{ $product['new_price'] }} TL</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@if (get_setting('vendor_system_activation') == 1)
    <div class="">
        @if (count($shops) > 0)
            <div class="px-2 py-1 text-uppercase fs-10 text-right text-muted bg-soft-secondary">
                {{ translate('Shops') }}</div>
            <ul class="list-group list-group-raw">
                @foreach ($shops as $key => $shop)
                    <li class="list-group-item">
                        <a class="text-reset" href="{{ route('shop.visit', $shop->slug) }}">
                            <div class="d-flex search-product align-items-center">
                                <div class="mr-3">
                                    <img class="size-40px img-fit rounded" src="{{ uploaded_asset($shop->logo) }}">
                                </div>
                                <div class="flex-grow-1 overflow--hidden">
                                    <div class="product-name cate fs-14 mb-5px">
                                        {{ $shop->name }}
                                    </div>
                                    <div class="opacity-60">
                                        {{ $shop->address }}
                                    </div>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif

@endif