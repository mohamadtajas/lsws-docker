<div class="bg-white border">
    <div class="p-3 p-sm-4">
        <h3 class="fs-16 fw-700 mb-0">
            <span class="mr-4">{{ translate('Related products') }}</span>
        </h3>
    </div>
    <div class="row no-gutters">
        <div class="col-12">
            <div class="container px-4">
                <div class="aiz-carousel gutters-5 half-outside-arrow" data-items="5" data-xxl-items="6" data-xl-items="6" data-lg-items="5"
                    data-md-items="4" data-sm-items="3" data-xs-items="2" data-arrows='true' data-infinite='true'>
                    @foreach ($related_products as $key => $related_product)
                        @php
                            $product_url = route('product.provider', [$related_product['provider'] , $related_product['id']]);
                        @endphp
                        @if ($related_product['id'] != $detailedProduct['id'])
                            <div class="carousel-box">
                                <div class="aiz-card-box hov-shadow-md my-2 has-transition hov-scale-img">
                                    <div class="">
                                        <a href="{{ $product_url }}" class="d-block">
                                            <img class="lazyload mx-auto img-fit has-transition"
                                                src="{{ $related_product['thumbnail'] }}"
                                                alt="{{ $related_product['name'] }}"
                                                title="{{ $related_product['name'] }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                                        </a>
                                    </div>
                                    <div class="p-md-3 p-2 text-center">
                                        <h3 class="fw-400 fs-14 text-dark text-truncate-2 lh-1-4 mb-0">
                                            <a href="{{ $product_url }}"
                                                class="d-block text-reset hov-text-primary">{{ $related_product['name'] }}</a>
                                        </h3>
                                        <div class="fs-14 mt-3">
                                            <span
                                                class="fw-700 text-primary notranslate">{{ single_price($related_product['new_price']) }}</span>
                                            @if ($related_product['unit_price'] != $related_product['new_price'])
                                                <del
                                                    class="fw-700 opacity-60 ml-1 notranslate">{{ single_price($related_product['unit_price']) }}</del>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
