@extends('frontend.layouts.app')

@section('meta_title'){{ $detailedProduct['name'] }}@stop

@section('meta_description'){{ $detailedProduct['name'] }}@stop

@section('meta_keywords'){{ $detailedProduct['name'] }}@stop

@section('meta')
    @php
        $availability = 'out of stock';
        $qty = $detailedProduct['stock'];
        if ($qty > 0) {
            $availability = 'in stock';
        }
    @endphp
    <!-- Schema.org markup for Google+ -->
    <meta itemprop="name" content="{{ $detailedProduct['name'] }}">
    <meta itemprop="description" content="{{ $detailedProduct['name'] }}">
    <meta itemprop="image" content="{{ $detailedProduct['thumbnail'] }}">

    <!-- Twitter Card data -->
    <meta name="twitter:card" content="product">
    <meta name="twitter:site" content="@publisher_handle">
    <meta name="twitter:title" content="{{ $detailedProduct['name'] }}">
    <meta name="twitter:description" content="{{ $detailedProduct['name'] }}">
    <meta name="twitter:creator" content="@author_handle">
    <meta name="twitter:image" content="{{ $detailedProduct['thumbnail'] }}">
    <meta name="twitter:data1" content="{{ single_price(str_replace(',', '', $detailedProduct['new_price'])) }}">
    <meta name="twitter:label1" content="Price">

    <!-- Open Graph data -->
    <meta property="og:title" content="{{ $detailedProduct['name'] }}" />
    <meta property="og:type" content="og:product" />
    <meta property="og:url" content="{{ route('product.provider', [$detailedProduct['provider'], $detailedProduct['id']]) }}" />
    <meta property="og:image" content="{{ $detailedProduct['thumbnail'] }}" />
    <meta property="og:description" content="{{ $detailedProduct['name'] }}" />
    <meta property="og:site_name" content="{{ get_setting('meta_title') }}" />
    <meta property="og:price:amount" content="{{ single_price(str_replace(',', '', $detailedProduct['new_price'])) }}" />
    <meta property="product:brand" content="{{ $detailedProduct['brandName'] ? $detailedProduct['brandName'] : env('APP_NAME') }}">
    <meta property="product:availability" content="{{ $availability }}">
    <meta property="product:condition" content="new">
    <meta property="product:price:amount" content="{{ number_format(str_replace(',', '', $detailedProduct['new_price']), 2) }}">
    <meta property="product:retailer_item_id" content="{{ $detailedProduct['id'] }}">
    <meta property="product:price:currency"
        content="{{ get_system_default_currency()->code }}" />
    <meta property="fb:app_id" content="{{ env('FACEBOOK_PIXEL_ID') }}">
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
                        @include('frontend.provider_product_details.image_gallery')
                    </div>

                    <!-- Product Details -->
                    <div class="col-xl-7 col-lg-6">
                        @include('frontend.provider_product_details.details')
                    </div>
                </div>
            </div>
        </div>
    </section>
    @if (count($detailedProduct['descriptions']) > 0 ||
            count($detailedProduct['attributes']) > 0 ||
            $detailedProduct['digital'] == 1)
    <section class="mb-4">
        <div class="container">
            <div class="row gutters-16">
                <div class="col-lg-12">
                    @include('frontend.provider_product_details.description')
                </div>
            </div>
        </div>
    </section>
    @endif
    <section class="mb-4">
        <div class="container">
            <div class="row gutters-16">
                @if (count($related_products) > 0)
                <div class="col-lg-12">
                    @include('frontend.provider_product_details.related_products')
                </div>
                @endif
            </div>
        </div>
    </section>
@endsection
