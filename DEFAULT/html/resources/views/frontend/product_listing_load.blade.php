<!-- Products -->
@if (count($products) > 0)
    @foreach ($products as $key => $product)
        <div class="col border-right border-bottom has-transition hov-shadow-out z-1">
            @include('frontend.' . get_setting('homepage_select') . '.partials.product_box_1', [
                'product' => $product,
            ])
        </div>
    @endforeach
@endif
@if (count($trendyolProducts) > 0)
    @foreach ($trendyolProducts as $key => $product)
        <div class="col border-right border-bottom has-transition hov-shadow-out z-1">
            @include('frontend.' . get_setting('homepage_select') . '.partials.trendyol_product_box_1', [
                'product' => $product,
            ])
        </div>
    @endforeach
@endif
@if ($category && $category->provider && $category->provider->name == 'LikeCard')
    @if (count($products_provider) > 0)
        @foreach ($products_provider as $key => $product)
            <div class="col border-right border-bottom has-transition hov-shadow-out z-1">
                @include(
                    'frontend.' . get_setting('homepage_select') . '.partials.provider_product_box_1',
                    [
                        'product' => $product,
                    ])
            </div>
        @endforeach
    @else
        @foreach ($category->provider->Service()->categories($category->external_id) as $key => $category)
            <div class="col border-right border-bottom has-transition hov-shadow-out z-1">
                <div class="aiz-card-box h-auto bg-white py-3 hov-scale-img">
                    <div class="position-relative h-140px h-md-200px img-fit overflow-hidden">
                        <a href="{{ route('products.category.provider', [strtolower($category->provider->name), $category->external_id ]) }}" class="d-block h-100">
                            <img class="lazyload mx-auto img-fit has-transition" src="{{ $category->icon }}"
                                alt="{{ $category->icon }}" title="{{ $category->icon }}"
                                style="background-color: #bdbdbd; border-radius: 21px;"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                        </a>
                    </div>
                </div>
                <div class="p-2 p-md-3 text-left">
                    <!-- Category name -->
                    <h3 class="fw-400 fs-13 text-truncate-2 lh-1-4 mb-0 h-35px text-center">
                        <a href="{{ route('products.category.provider', [strtolower($category->provider->name), $category->external_id ]) }}"
                            class="d-block text-reset hov-text-primary" title="{{ $category->name }}">
                            {{ $category->name }}</a>
                    </h3>
                </div>
            </div>
        @endforeach
    @endif
@endif
