@extends('frontend.layouts.app')

@section('meta_title'){{ $detailedProduct['name'] }}@stop

@section('meta_description'){{ $detailedProduct['name'] }}@stop

@section('meta_keywords'){{ $detailedProduct['name'] }}@stop

@section('meta')
    @php
        $availability = "out of stock";
        $qty = $detailedProduct['stock'];
        if($qty > 0){
            $availability = "in stock";
        }
    @endphp
    <!-- Schema.org markup for Google+ -->
    <meta itemprop="name" content="{{ $detailedProduct['name'] }}">
    <meta itemprop="description" content="{{ $detailedProduct['name'] }}">
    <meta itemprop="image" content="{{ $detailedProduct['photos'][0] ?? ''}}">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="product">
    <meta name="twitter:site" content="@publisher_handle">
    <meta name="twitter:title" content="{{ $detailedProduct['name'] }}">
    <meta name="twitter:description" content="{{ $detailedProduct['name'] }}">
    <meta name="twitter:creator" content="@author_handle">
    <meta name="twitter:image" content="{{ $detailedProduct['photos'][0] ?? ''}}">
    <meta name="twitter:data1" content="{{ single_price(str_replace(',' , '', $detailedProduct['new_price'])) }}">
    <meta name="twitter:label1" content="Price">

    <!-- Open Graph data -->
    <meta property="og:title" content="{{ $detailedProduct['name'] }}" />
    <meta property="og:type" content="og:product" />
    <meta property="og:url" content="{{ route('trendyol-product', ['id' => $detailedProduct['id'], 'urunNo' => $detailedProduct['urunNo']]) }}" />
    <meta property="og:image" content="{{ $detailedProduct['photos'][0] ?? ''}}" />
    <meta property="og:description" content="{{ $detailedProduct['name'] }}" />
    <meta property="og:site_name" content="{{ get_setting('meta_title') }}" />
    <meta property="og:price:amount" content="{{ single_price(str_replace(',' , '', $detailedProduct['new_price'])) }}" />
    <meta property="product:brand" content="{{ $detailedProduct['brandName'] ? $detailedProduct['brandName'] : env('APP_NAME') }}">
    <meta property="product:availability" content="{{ $availability }}">
    <meta property="product:condition" content="new">
    <meta property="product:price:amount" content="{{ number_format(str_replace(',' , '', $detailedProduct['new_price']), 2) }}">
    <meta property="product:retailer_item_id" content="{{ $detailedProduct['id'] }}">
    <meta property="product:price:currency"
        content="{{ get_system_default_currency()->code }}" />
    <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">

    <!-- google translate -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript">
        // Add a timestamp to the script URL to force reload
        function loadGoogleTranslateScript() {
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = '//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit&_=' + new Date().getTime();
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
            new google.translate.TranslateElement({pageLanguage: 'auto', autoDisplay: false}, 'google_translate_element');
        }

        $(document).ready(function() {
            deleteGoogleTranslateCookies();
            loadGoogleTranslateScript();
            function triggerTranslate() {
                var select = document.querySelector('select.goog-te-combo');
                if (select) {
                    select.value = '{{App::getLocale() == 'sa' ? 'ar' :  App::getLocale() }}';
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
        body{
            top : 0px !important;
        }
        font{
            box-shadow : none !important;
            background-color: transparent !important;
        }
    </style>
@endsection

@section('content')
    <section class="mb-4 pt-3">
        <div class="container">
            <div class="bg-white py-3">
                <div class="row">
                    <!-- Product Image Gallery -->
                    <div class="col-xl-5 col-lg-6 mb-4">
                        @include('frontend.trendyol_product_details.image_gallery')
                    </div>

                    <!-- Product Details -->
                    <div class="col-xl-7 col-lg-6">
                        @include('frontend.trendyol_product_details.details')
                    </div>
                </div>
            </div>
        </div>
    </section>
    @if(count($detailedProduct['descriptions']) > 0 || count($detailedProduct['attributes']) > 0)
    <section class="mb-4">
        <div class="container">
            <div class="row gutters-16">
                <div class="col-lg-12">
                    @include('frontend.trendyol_product_details.description')
                </div>
            </div>
        </div>
    </section>
    @endif
    <section class="mb-4">
        <div class="container">
            <div class="row gutters-16">
                @if(count($relatedProducts['products']) > 0)
                <div class="col-lg-12">
                        <!-- Related products -->
                        @include('frontend.trendyol_product_details.related_products')
                </div>
                @endif
            </div>
        </div>
    </section>


@endsection
