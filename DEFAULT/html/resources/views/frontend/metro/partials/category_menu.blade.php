<div class="aiz-category-menu bg-white rounded-0 h-100" id="category-sidebar"
    style="width: 100%; background-color: transparent !important ;">
    <ul class="categories no-scrollbar mb-0 text-left d-flex justify-content-start flex-nowrap p-0 h-100 align-items-center"
        style="list-style: none;">
        @foreach (get_level_zero_categories()->take(13) as $key => $category)
            @php
                $category_name = $category->getTranslation('name');
            @endphp
            <li class="category-nav-element px-1 h-100" data-id="{{ $category->id }}">
                @if ($category->provider_id == null)
                    <a href="{{ route('products.category', $category->slug) }}"
                        class="text-truncate text-white px-xl-2 fs-xxl-14 fs-xl-12 fs-11 d-block hov-column-gap-1 h-100 d-flex align-items-center">
                        <span class="cat-name has-transition m-0">{{ $category_name }}</span>
                    </a>
                    <div class="sub-cat-menu more c-scrollbar-light border p-4 shadow-none"
                        style="top: 50px !important; right: 160px !important;">
                        <div class="c-preloader text-center absolute-center">
                            <i class="las la-spinner la-spin la-3x opacity-70"></i>
                        </div>
                    </div>
                @else
                    <a href="{{ route('products.category.provider', [strtolower($category->provider->name), $category->id ]) }}"
                        class="text-truncate text-white px-xl-2 fs-xxl-14 fs-xl-12 fs-11 d-block hov-column-gap-1 h-100 d-flex align-items-center">
                        <span class="cat-name has-transition m-0">{{ $category_name }}</span>
                    </a>
                    <div class="sub-cat-menu more c-scrollbar-light border p-4 shadow-none"
                        style="top: 50px !important; right: 160px !important;">
                        <div class="c-preloader text-center absolute-center">
                            <i class="las la-spinner la-spin la-3x opacity-70"></i>
                        </div>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
</div>
