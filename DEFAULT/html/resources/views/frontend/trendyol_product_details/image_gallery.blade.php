<div class="sticky-top z-3 row gutters-10">
    @php
        $photos = [];
    @endphp
    @if ($detailedProduct['photos'] != null)
        @php
            $photos = $detailedProduct['photos'];
        @endphp
    @endif
    <!-- Gallery Images -->
    <div class="col-12">
        <div class="aiz-carousel product-gallery arrow-inactive-transparent arrow-lg-none"
            data-nav-for='.product-gallery-thumb' data-fade='true' data-auto-height='true' data-arrows='true'>
            @if ($detailedProduct['digital'] == 0)
                @foreach ($photos as $photo )
                    @if ($photo != null)
                        <div class="carousel-box img-zoom rounded-0">
                            <img class="img-fluid h-auto lazyload mx-auto"
                                src="{{ static_asset('assets/img/placeholder.webp') }}"
                                data-src="{{ $photo }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                        </div>
                    @endif
                @endforeach
            @endif



        </div>
    </div>
    <!-- Thumbnail Images -->
    <div class="col-12 mt-3 d-none d-lg-block">
        <div class="aiz-carousel half-outside-arrow product-gallery-thumb" data-items='7' data-nav-for='.product-gallery'
            data-focus-select='true' data-arrows='true' data-vertical='false' data-auto-height='true'>

            @if ($detailedProduct['digital'] == 0)
                @foreach ($photos as $photo)
                    @if ($photo != null)
                        <div class="carousel-box c-pointer rounded-0">
                            <img class="lazyload mw-100 size-60px mx-auto border p-1"
                                src="{{ static_asset('assets/img/placeholder.webp') }}"
                                data-src="{{ $photo }}"
                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                        </div>
                    @endif
                @endforeach
            @endif



        </div>
    </div>


</div>
