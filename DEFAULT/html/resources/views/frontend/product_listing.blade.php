@extends('frontend.layouts.app')

@if (isset($category_id))
    @php
        $meta_title = $category->meta_title;
        $meta_description = $category->meta_description;
    @endphp
@elseif (isset($brand_id))
    @php
        $meta_title = get_single_brand($brand_id)->meta_title;
        $meta_description = get_single_brand($brand_id)->meta_description;
    @endphp
@else
    @php
        $meta_title = get_setting('meta_title');
        $meta_description = get_setting('meta_description');
    @endphp
@endif

@section('meta_title'){{ $meta_title }}@stop
@section('meta_description'){{ $meta_description }}@stop

@section('meta')
    <!-- Schema.org markup for Google+ -->
    <meta itemprop="name" content="{{ $meta_title }}">
    <meta itemprop="description" content="{{ $meta_description }}">

    <!-- Twitter Card data -->
    <meta name="twitter:title" content="{{ $meta_title }}">
    <meta name="twitter:description" content="{{ $meta_description }}">

    <!-- Open Graph data -->
    <meta property="og:title" content="{{ $meta_title }}" />
    <meta property="og:description" content="{{ $meta_description }}" />
    <!-- google translate -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript">
        // Add a timestamp to the script URL to force reload
        function loadGoogleTranslateScript() {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit&_=' + new Date()
                .getTime();
            document.head.appendChild(script);
        }

        // Remove Google Translate cookies to reset the translation settings
        function deleteGoogleTranslateCookies() {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = cookies[i];
                var eqPos = cookie.indexOf('=');
                var name = eqPos > -1 ? cookie.substr(0, eqPos) : cookie;
                if (name.trim().startsWith('googtrans') || name.trim().startsWith('googtrans-')) {
                    document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/';
                }
            }
        }

        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'auto',
                autoDisplay: false
            }, 'google_translate_element');
        }

        $(document).ready(function() {
            deleteGoogleTranslateCookies();
            loadGoogleTranslateScript();

            function triggerTranslate() {
                var select = document.querySelector('select.goog-te-combo');
                if (select) {
                    select.value = '{{ App::getLocale() == 'sa' ? 'ar' : App::getLocale() }}';
                    select.dispatchEvent(new Event('change'));
                } else {
                    setTimeout(triggerTranslate, 1000); // Retry until the options are loaded
                }
            }
            triggerTranslate();
        });
    </script>
    <style>
        .skiptranslate {
            display: none !important;
        }

        body {
            top: 0px !important;
        }

        font {
            box-shadow: none !important;
            background-color: transparent !important;
        }
    </style>
@endsection

@section('content')
    <section class="mb-4 pt-4">
        <div class="container sm-px-0 pt-2">
            <form class="" id="search-form" action="" method="GET" autocomplete="off">
                <div class="row">

                    <!-- Sidebar Filters -->
                    <div class="col-xl-3">
                        <div
                            class="aiz-filter-sidebar collapse-sidebar-wrap sidebar-xl sidebar-right z-1045 sidebar-custom-xl">
                            <div class="overlay overlay-fixed dark c-pointer" data-toggle="class-toggle"
                                data-target=".aiz-filter-sidebar" data-same=".filter-sidebar-thumb"></div>
                            <div class="collapse-sidebar c-scrollbar-light text-left px-3 overflow-auto">
                                <div class="d-flex d-xl-none justify-content-between align-items-center pl-3 border-bottom">
                                    <h3 class="h6 mb-0 fw-600">{{ translate('Filters') }}</h3>
                                    <button type="button" class="btn btn-sm p-2 filter-sidebar-thumb"
                                        data-toggle="class-toggle" data-target=".aiz-filter-sidebar">
                                        <i class="las la-times la-2x"></i>
                                    </button>
                                </div>

                                <!-- Price range -->
                                <div class="bg-white border mb-3">
                                    <div class="fs-16 fw-700 p-3">
                                        {{ translate('Price range') }}
                                    </div>
                                    <div class="p-3 mr-3">
                                        @php
                                            $product_count = get_products_count();
                                            if (isset($min_price)) {
                                                $manimum = $min_price;
                                            } else {
                                                if ($product_count <= 1) {
                                                    $manimum = 0;
                                                } else {
                                                    $manimum = 0;
                                                }
                                            }
                                            if (isset($max_price)) {
                                                $maximum = $max_price;
                                            } else {
                                                if ($product_count <= 1) {
                                                    $maximum = 100000;
                                                } else {
                                                    $maximum = 100000;
                                                }
                                            }
                                        @endphp

                                        <div class="row mt-2">
                                            <div class="col-6">
                                                <input type="number"
                                                    class="border border-soft-light form-control fs-14 hov-animate-outline"
                                                    onkeyup="delayedFilter(this)" value="{{ round($manimum, 2) }}"
                                                    name="min_price">
                                            </div>

                                            <div class="col-6">
                                                <input type="number"
                                                    class="border border-soft-light form-control fs-14 hov-animate-outline"
                                                    onkeyup="delayedFilter(this)" value="{{ round($maximum, 2) }}"
                                                    name="max_price">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Categories -->
                                @if (count($categories) > 0 || isset($category_id))
                                    <div class="bg-white border mb-3 notranslate">
                                        <div class="fs-16 fw-700 p-3">
                                            <a href="#collapse_1"
                                                class="dropdown-toggle filter-section text-dark d-flex align-items-center justify-content-between"
                                                data-toggle="collapse">
                                                {{ translate('Categories') }}
                                            </a>
                                        </div>
                                        <div class="collapse {{ count($selected_categories) > 0 ? 'show' : '' }}"
                                            id="collapse_1">

                                            @if (!isset($category_id))
                                                @php
                                                    $prevParentId = 0;
                                                @endphp
                                                <div class="p-3 aiz-checkbox-list">
                                                    @foreach ($categories as $category)
                                                        @if (
                                                            $category->parent_id != 0 &&
                                                                get_single_category($category->parent_id) != null &&
                                                                $category->parent_id != $prevParentId)
                                                            <label class="aiz-checkbox mb-3">
                                                                <input type="checkbox" name="categories[]"
                                                                    value="{{ get_single_category($category->parent_id)->id }}"
                                                                    @if (in_array(get_single_category($category->parent_id)->id, $selected_categories)) checked @endif
                                                                    onchange="filter()">
                                                                <span class="aiz-square-check"></span>
                                                                <span
                                                                    class="text-reset fs-14 fw-600 hov-text-primary">{{ get_single_category($category->parent_id)->getTranslation('name') }}</span>
                                                            </label>
                                                            @php
                                                                $prevParentId = $category->parent_id;
                                                            @endphp
                                                        @endif
                                                        <label class="aiz-checkbox ml-4 mb-3">
                                                            <input type="checkbox" name="categories[]"
                                                                value="{{ $category->id }}"
                                                                @if (in_array($category->id, $selected_categories)) checked @endif
                                                                onchange="filter()">
                                                            <span class="aiz-square-check"></span>
                                                            <span
                                                                class="fs-14 fw-400 text-dark">{{ $category->name }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @else
                                                <ul class="p-3 mb-0 list-unstyled">
                                                    <li class="mb-3">
                                                        <a class="text-reset fs-14 fw-600 hov-text-primary"
                                                            href="{{ route('search') }}">
                                                            <i class="las la-angle-left"></i>
                                                            {{ translate('All Categories') }}
                                                        </a>
                                                    </li>

                                                    @if ($category->parent_id != 0 && get_single_category($category->parent_id) != null && $category->provider_id == null)
                                                        <li class="mb-3">
                                                            <a class="text-reset fs-14 fw-600 hov-text-primary"
                                                                href="{{ route('products.category', get_single_category($category->parent_id)->slug) }}">
                                                                <i class="las la-angle-left"></i>
                                                                {{ get_single_category($category->parent_id)->getTranslation('name') }}
                                                            </a>
                                                        </li>
                                                    @endif
                                                    @if ($category->provider_id == null)
                                                        <li class="mb-3">
                                                            <a class="text-reset fs-14 fw-600 hov-text-primary"
                                                                href="{{ route('products.category', $category->slug) }}">
                                                                <i class="las la-angle-left"></i>
                                                                {{ $category->getTranslation('name') }}
                                                            </a>
                                                        </li>
                                                        @foreach ($category->childrenCategories as $key => $immediate_children_category)
                                                            <li class="ml-4 mb-3">
                                                                <a class="text-reset fs-14 hov-text-primary"
                                                                    href="{{ route('products.category', $immediate_children_category->slug) }}">
                                                                    {{ $immediate_children_category->getTranslation('name') }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    @else
                                                        <li class="mb-3">
                                                            <a class="text-reset fs-14 fw-600 hov-text-primary"
                                                                href="{{ route('products.category.provider', [strtolower($category->provider->name), $category->external_id]) }}">
                                                                <i class="las la-angle-left"></i>
                                                                {{ $category->getTranslation('name') }}
                                                            </a>
                                                        </li>
                                                        @foreach ($category->provider->Service()->categories($category->external_id) as $key => $immediate_children_category)
                                                            <li class="ml-4 mb-3">
                                                                <a class="text-reset fs-14 hov-text-primary"
                                                                    href="{{ route('products.category.provider', [strtolower($immediate_children_category->provider->name), $immediate_children_category->external_id]) }}">
                                                                    {{ $immediate_children_category->getTranslation('name') }}
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    @endif
                                                </ul>
                                            @endif
                                        </div>
                                    </div>
                                @endif

                                <!-- Brands -->
                                @if (count($brands) > 0)
                                    <div class="bg-white border mb-3">
                                        <div class="fs-16 fw-700 p-3">
                                            <a href="#collapse_2"
                                                class="dropdown-toggle filter-section text-dark d-flex align-items-center justify-content-between"
                                                data-toggle="collapse">
                                                {{ translate('Brands') }}
                                            </a>
                                        </div>
                                        <div class="collapse {{ count($selected_brands) > 0 ? 'show' : '' }}"
                                            id="collapse_2">
                                            <div class="p-3 aiz-checkbox-list">
                                                @foreach ($brands as $brand)
                                                    <label class="aiz-checkbox mb-3">
                                                        <input type="checkbox" name="brands[]"
                                                            value="{{ $brand->id }}"
                                                            @if (in_array($brand->id, $selected_brands)) checked @endif
                                                            onchange="filter()">
                                                        <span class="aiz-square-check"></span>
                                                        <span
                                                            class="fs-14 fw-400 text-dark notranslate">{{ $brand->name }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif


                                <!-- Attributes -->
                                @foreach ($allTrendyolFilters as $attribute)
                                    <div class="bg-white border mb-3 ">
                                        <div class="fs-16 fw-700 p-3">
                                            <a href="#"
                                                class="dropdown-toggle text-dark filter-section collapsed d-flex align-items-center justify-content-between"
                                                data-toggle="collapse"
                                                data-target="#collapse_{{ str_replace(' ', '_', $attribute->trendyol_id) }}"
                                                style="white-space: normal;">
                                                {{ $attribute->getTranslation('name') }}
                                            </a>
                                        </div>
                                        @php
                                            $show = '';
                                            foreach ($attributeValues[$attribute->trendyol_id] as $attribute_value) {
                                                if (in_array($attribute_value->uniqueId, $selected_attribute_values)) {
                                                    $show = 'show';
                                                }
                                            }
                                        @endphp
                                        <div class="collapse {{ $show }}"
                                            id="collapse_{{ str_replace(' ', '_', $attribute->trendyol_id) }}">
                                            <div class="p-3 aiz-checkbox-list">
                                                @foreach ($attributeValues[$attribute->trendyol_id] as $attribute_value)
                                                    <label class="aiz-checkbox mb-3">
                                                        <input type="checkbox" name="selected_attribute_values[]"
                                                            value="{{ $attribute_value->uniqueId }}"
                                                            @if (in_array($attribute_value->uniqueId, $selected_attribute_values)) checked @endif
                                                            onchange="filter()">
                                                        <span class="aiz-square-check"></span>
                                                        <span
                                                            class="fs-14 fw-400 text-dark">{{ $attribute_value->value }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- Color -->
                                @if (get_setting('color_filter_activation') && count($colors) > 0)
                                    <div class="bg-white border mb-3">
                                        <div class="fs-16 fw-700 p-3">
                                            <a href="#"
                                                class="dropdown-toggle text-dark filter-section collapsed d-flex align-items-center justify-content-between"
                                                data-toggle="collapse" data-target="#collapse_color">
                                                {{ translate('Filter by color') }}
                                            </a>
                                        </div>
                                        @php
                                            $show = '';
                                            foreach ($colors as $key => $color) {
                                                if (isset($selected_color) && $selected_color == $color->code) {
                                                    $show = 'show';
                                                }
                                            }
                                        @endphp
                                        <div class="collapse {{ $show }}" id="collapse_color">
                                            <div class="py-3 px-0 aiz-radio-inline">
                                                @foreach ($colors as $key => $color)
                                                    <label class="aiz-megabox pl-0 mr-2" data-toggle="tooltip"
                                                        data-title="{{ $color->name }}">
                                                        <input type="radio" name="color" value="{{ $color->code }}"
                                                            onchange="filter()"
                                                            @if (isset($selected_color) && $selected_color == $color->code) checked @endif>
                                                        <span
                                                            class="aiz-megabox-elem rounded d-flex align-items-center justify-content-center p-1 mb-2">
                                                            <span class="size-30px d-inline-block rounded"
                                                                style="background: {{ $color->code }};"></span>
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Contents -->
                    <div class="col-xl-9">

                        <!-- Breadcrumb -->
                        <ul class="breadcrumb bg-transparent py-0 px-1">
                            <li class="breadcrumb-item has-transition opacity-50 hov-opacity-100">
                                <a class="text-reset" href="{{ route('home') }}">{{ translate('Home') }}</a>
                            </li>
                            @if (!isset($category_id))
                                <li class="breadcrumb-item fw-700  text-dark">
                                    "{{ translate('All Categories') }}"
                                </li>
                            @else
                                <li class="breadcrumb-item opacity-50 hov-opacity-100">
                                    <a class="text-reset"
                                        href="{{ route('search') }}">{{ translate('All Categories') }}</a>
                                </li>
                            @endif
                            @if (isset($category_id))
                                <li class="text-dark fw-600 breadcrumb-item">
                                    "{{ $category->getTranslation('name') }}"
                                </li>
                            @endif
                        </ul>

                        <!-- Top Filters -->
                        <div class="text-left">
                            <div class="row gutters-5 flex-wrap align-items-center">
                                <div class="col-lg col-10">
                                    <h1 class="fs-20 fs-md-24 fw-700 text-dark">
                                        @if (isset($category_id))
                                            {{ $category->getTranslation('name') }}
                                        @elseif(isset($query))
                                            {{ translate('Search result for ') }}"{{ $query }}"
                                        @else
                                            {{ translate('All Products') }}
                                        @endif
                                    </h1>
                                    <input type="hidden" name="keyword" value="{{ $query }}">
                                </div>
                                <div class="col-2 col-lg-auto d-xl-none mb-lg-3 text-right">
                                    <button type="button" class="btn btn-icon p-0" data-toggle="class-toggle"
                                        data-target=".aiz-filter-sidebar">
                                        <i class="la la-filter la-2x"></i>
                                    </button>
                                </div>
                                {{-- <div class="col-6 col-lg-auto mb-3 w-lg-200px mr-xl-4 mr-lg-3">
                                    @if (Route::currentRouteName() != 'products.brand')
                                        <select class="form-control form-control-sm aiz-selectpicker rounded-0" data-live-search="true" name="brand" onchange="filter()">
                                            <option value="">{{ translate('Brands')}}</option>
                                            @foreach (get_all_brands() as $brand)
                                                <option value="{{ $brand->slug }}" @isset($brand_id) @if ($brand_id == $brand->id) selected @endif @endisset>{{ $brand->getTranslation('name') }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div> --}}
                                <div class="col-6 col-lg-auto mb-3 w-lg-250px">
                                    <select class="form-control form-control-sm aiz-selectpicker rounded-0" name="sort_by"
                                        onchange="filter()">
                                        <option value="">{{ translate('Sort by') }}</option>
                                        <option value="newest"
                                            @isset($sort_by) @if ($sort_by == 'newest') selected @endif @endisset>
                                            {{ translate('Newest') }}</option>
                                        <option value="price-asc"
                                            @isset($sort_by) @if ($sort_by == 'price-asc') selected @endif @endisset>
                                            {{ translate('Price low to high') }}</option>
                                        <option value="price-desc"
                                            @isset($sort_by) @if ($sort_by == 'price-desc') selected @endif @endisset>
                                            {{ translate('Price high to low') }}</option>
                                        <option value="most-favourite"
                                            @isset($sort_by) @if ($sort_by == 'most-favourite') selected @endif @endisset>
                                            {{ translate('Most favourite') }}</option>
                                        <option value="top-rated"
                                            @isset($sort_by) @if ($sort_by == 'top-rated') selected @endif @endisset>
                                            {{ translate('Top rated') }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="px-3">
                                <div class="row gutters-16 row-cols-xxl-4 row-cols-xl-3 row-cols-lg-4 row-cols-md-3 row-cols-2 border-left"
                                    id="products-container">
                                    @include('frontend.product_listing_load')
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>

@endsection

@section('script')
    <script type="text/javascript">
        function filter() {
            $('#search-form').submit();
        }
    </script>
    <script>
        let typingTimerfilter;
        const doneTypingIntervalFilter = 600;

        function delayedFilter(input) {
            clearTimeout(typingTimerfilter);
            typingTimerfilter = setTimeout(function() {
                filter();
            }, doneTypingIntervalFilter);
        }
    </script>

    <script>
        $(document).ready(function() {
            let nextPageUrl = '{!! $products->nextPageUrl() !!}';
            $(window).scroll(function() {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 15000) {
                    if (nextPageUrl) {
                        loadMore();
                    }
                }
            });

            function loadMore() {
                $.ajax({
                    url: nextPageUrl,
                    type: 'get',
                    beforeSend: function() {
                        nextPageUrl = '';
                    },
                    success: function(data) {
                        nextPageUrl = data.nextPageUrl;
                        $('#products-container').append(data.view);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading more products:", error);
                    }
                });
            }
        });
    </script>
@endsection
