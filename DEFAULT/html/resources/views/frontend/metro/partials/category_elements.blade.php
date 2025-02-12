<div class="card-columns notranslate">
    @foreach ($category->childrenCategories as $key => $category)
        <div class="card shadow-none border-0">
            <ul class="list-unstyled mb-3">
                <li class="fs-14 fw-700 mb-3">
                    <a class="text-reset hov-text-primary"
                        href="@if ($category->provider_id != null) {{ route('products.category.provider', [strtolower($category->provider->name), $category->external_id]) }} @else {{ route('products.category', $category->slug) }} @endif">
                        {{ $category->getTranslation('name') }}
                    </a>
                </li>
                @php
                    $child_categories = isset($category->children_categories)
                        ? $category->children_categories
                        : $category->childrenCategories;
                    $count = isset($category->children_categories)
                        ? count($category->children_categories)
                        : $category->childrenCategories->count();
                @endphp
                @if ($count)
                    @foreach ($child_categories as $key => $child_category)
                        @if (isset($child_category->provider))
                            <li class="mb-2 fs-14 pl-2">
                                <a class="text-reset hov-text-primary animate-underline-primary"
                                    href="{{ route('products.category.provider', [strtolower($child_category->provider->name), $child_category->external_id]) }}">
                                    {{ $child_category->getTranslation('name') }}
                                </a>
                            </li>
                        @else
                            <li class="mb-2 fs-14 pl-2">
                                <a class="text-reset hov-text-primary animate-underline-primary"
                                    href="{{ route('products.category', $child_category->slug) }}">
                                    {{ $child_category->getTranslation('name') }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endif
            </ul>
        </div>
    @endforeach
</div>
