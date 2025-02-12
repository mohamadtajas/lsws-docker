@extends('frontend.layouts.app')

@section('content')
    <!-- Breadcrumb -->
    <section class="mb-4 pt-4">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 text-lg-left text-center">
                    <h1 class="fw-700 fs-20 fs-md-24 text-dark">{{ translate('All Brands') }}</h1>
                </div>
                <div class="col-lg-6">
                    <ul class="breadcrumb justify-content-center justify-content-lg-end bg-transparent p-0">
                        <li class="breadcrumb-item has-transition opacity-60 hov-opacity-100">
                            <a class="text-reset" href="{{ route('home') }}">{{ translate('Home') }}</a>
                        </li>
                        <li class="text-dark fw-600 breadcrumb-item">
                            "{{ translate('All Brands') }}"
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>
    <!-- All Brands -->
    <section class="mb-4">
        <div class="container" id="brands-container">
    @include('frontend.all_brand_load')
        </div>
    </section>
@endsection

@section('script')
<script>
        $(document).ready(function() {
            let nextPageUrl = '{!! $brands->nextPageUrl() !!}';
            $(window).scroll(function() {
                if ($(window).scrollTop() + $(window).height() >= $(document).height() - 1000) {
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
                        $('#brands-container').append(data.view);
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading more products:", error);
                    }
                });
            }
        });
    </script>
@endsection