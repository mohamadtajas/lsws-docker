<div class="modal-body px-4 py-5 c-scrollbar-light">
    <!-- Item added to your cart -->
    <div class="text-center text-success mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
            <g id="Group_23957" data-name="Group 23957" transform="translate(-6269 7766)">
              <path id="Path_28713" data-name="Path 28713" d="M12.8,32.8a3.6,3.6,0,1,0,3.6,3.6A3.584,3.584,0,0,0,12.8,32.8ZM2,4V7.6H5.6l6.471,13.653-2.43,4.41A3.659,3.659,0,0,0,9.2,27.4,3.6,3.6,0,0,0,12.8,31H34.4V27.4H13.565a.446.446,0,0,1-.45-.45.428.428,0,0,1,.054-.216L14.78,23.8H28.19a3.612,3.612,0,0,0,3.15-1.854l6.435-11.682A1.74,1.74,0,0,0,38,9.4a1.8,1.8,0,0,0-1.8-1.8H9.587L7.877,4H2ZM30.8,32.8a3.6,3.6,0,1,0,3.6,3.6A3.584,3.584,0,0,0,30.8,32.8Z" transform="translate(6267 -7770)" fill="#85b567"/>
              <rect id="Rectangle_18068" data-name="Rectangle 18068" width="9" height="3" rx="1.5" transform="translate(6284.343 -7757.879) rotate(45)" fill="#fff"/>
              <rect id="Rectangle_18069" data-name="Rectangle 18069" width="3" height="13" rx="1.5" transform="translate(6295.657 -7760.707) rotate(45)" fill="#fff"/>
            </g>
        </svg>
        <h3 class="fs-28 fw-500">{{ translate('Item added to your cart!')}}</h3>
    </div>

    <!-- Product Info -->
    <div class="media mb-1">
        <img src="{{ static_asset('assets/img/placeholder.webp') }}" data-src="{{ $product->photos[0] }}"
            class="mr-4 lazyload size-90px img-fit rounded-0" alt="Product Image" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
        <div class="media-body mt-2 text-left d-flex flex-column justify-content-between">
            <h6 class="fs-14 fw-700 text-truncate-2">
                {{  $product->name  }}
            </h6>
            <div class="row mt-2">
                <div class="col-sm-3 fs-14 fw-400 text-secondary">
                    <div>{{ translate('Price')}}</div>
                </div>
                <div class="col-sm-9">
                    <div class="fs-16 fw-700 text-primary notranslate">
                        <strong>
                            {{ single_price($product->getAttributes()['unit_price'] * $cart->quantity) }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to shopping & Checkout buttons -->
    <div class="row gutters-5">
        <div class="col-sm-6">
            <button class="btn btn-secondary-base mb-3 mb-sm-0 btn-block rounded-0 text-white" data-dismiss="modal">{{ translate('Back to shopping')}}</button>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('cart') }}" class="btn btn-primary mb-3 mb-sm-0 btn-block rounded-0">{{ translate('Proceed to Checkout')}}</a>
        </div>

    </div>
</div>
