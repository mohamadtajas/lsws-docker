@extends('seller.layouts.app')

@section('panel_content')
    <div class="aiz-titlebar mt-2 mb-4">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('Trendyol Product Bulk Upload') }}</h1>
            </div>
        </div>
    </div>

    <div class="row gutters-10 justify-content-center">

        <div class="col-md-4 mx-auto mb-3">
            <a href="javascript:void(0)" onclick="getTrendyolProduct()">
                <div class="p-3 rounded mb-3 c-pointer text-center bg-white shadow-sm hov-shadow-lg has-transition">
                    <span
                        class="size-60px rounded-circle mx-auto bg-secondary d-flex align-items-center justify-content-center mb-3">
                        <i class="las la-plus la-3x text-white"></i>
                    </span>
                    <div class="fs-18 text-primary">{{ translate('Get Trendyol Products') }}</div>
                </div>
            </a>
        </div>

    </div>

    <div class="card">
        <form class="" id="sort_products" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Products') }}</h5>
                </div>

                <div class="dropdown mb-2 mb-md-0">
                    <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                        {{ translate('Bulk Action') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item confirm-alert" href="javascript:void(0)" data-target="#bulk-delete-modal">
                            {{ translate('Import selection') }}</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>
                                <div class="form-group">
                                    <div class="aiz-checkbox-inline">
                                        <label class="aiz-checkbox">
                                            <input type="checkbox" class="check-all">
                                            <span class="aiz-square-check"></span>
                                        </label>
                                    </div>
                                </div>
                            </th>
                            <th width="20%">{{ translate('Name') }}</th>
                            <th width="30%">{{ translate('Image') }}</th>
                            <th data-breakpoints="md" width="20%">{{ translate('Current Qty') }}</th>
                            <th data-breakpoints="md" width="15%">{{ translate('Unit') }}</th>
                            <th width="15%">{{ translate('Base Price') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @if (isset($products))
                            @foreach ($products as $key => $product)
                                <tr>
                                    <td>
                                        <div class="form-group d-inline-block">
                                            <label class="aiz-checkbox">
                                                <input type="checkbox" class="check-one" name="id[]"
                                                    value="{{ $product }}">
                                                <span class="aiz-square-check"></span>
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $product->getTranslation('name') }}
                                    </td>
                                    <td>
                                        <img class="img-fluid h-auto lazyload mx-auto"
                                            src="{{ static_asset('assets/img/placeholder.webp') }}" width="50px"
                                            data-src="{{ $product->photos[0] ?? ''}}"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.webp') }}';">
                                    </td>
                                    <td>
                                        {{ $product->current_stock }}
                                    </td>
                                    <td>
                                        {{ $product->unit }}
                                    </td>
                                    <td>
                                        {{ $product->unit_price }}
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ isset($products) && count($products) > 0 ? $products->links() : '' }}
                </div>
            </div>
        </form>
    </div>
@endsection
@section('modal')
    <div class="modal fade" id="getTrendyolProduct">
        <div class="modal-dialog modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
            <div class="modal-content position-relative">
                <button type="button"
                    class="close absolute-top-right btn-icon close z-1 btn-circle bg-gray mr-2 mt-2 d-flex justify-content-center align-items-center"
                    data-dismiss="modal" aria-label="Close"
                    style="background: #ededf2; width: calc(2rem + 2px); height: calc(2rem + 2px);">
                    <span aria-hidden="true" class="fs-24 fw-700" style="margin-left: 2px;">&times;</span>
                </button>
                <div id="getTrendyolProduct-modal-body">
                    <div class="row">
                        <div class="col-10 mx-auto my-4">
                            <div class="text-left">
                                <form action="{{ route('seller.product_bulk_upload.trendyol.get_products') }}"
                                    method="get" autocomplete="off">
                                    <div class="form-group row" id="brand">
                                        <label
                                            class="col-12 col-from-label fs-13">{{ translate('Trendyol Seller ID') }}</label>
                                        <div class="col-12">
                                            <input type="text" dir="ltr" class="form-control" name="supplier_id"
                                                required>
                                        </div>
                                    </div>
                                    <div class="form-group row" id="brand">
                                        <label
                                            class="col-12 col-from-label fs-13">{{ translate('Trendyol Api Key') }}</label>
                                        <div class="col-12">
                                            <input type="text" dir="ltr" class="form-control" name="api_user_name"
                                                required>
                                        </div>
                                    </div>
                                    <div class="form-group row" id="brand">
                                        <label
                                            class="col-12 col-from-label fs-13">{{ translate('Trendyol API Secret') }}</label>
                                        <div class="col-12">
                                            <input type="text" dir="ltr" class="form-control" name="api_password"
                                                required>
                                        </div>
                                    </div>
                                    <div class="row gutters-5">
                                        <div class="col-12">
                                            <button class="btn btn-success mb-3 mb-sm-0 btn-block rounded-0 text-white"
                                                type="submit">{{ translate('Submit') }}</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="bulk-delete-modal" class="modal fade">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title h6">{{ translate('Import Confirmation') }}</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                </div>
                <div class="modal-body text-center">
                    <p class="mt-1">{{ translate('Are you sure to Import those products?') }}</p>
                    <button type="button" class="btn btn-link mt-2"
                        data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a href="javascript:void(0)" onclick="bulk_delete()"
                        class="btn btn-primary mt-2">{{ translate('Import') }}</a>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        function getTrendyolProduct() {
            $('#getTrendyolProduct').modal();
        }
    </script>
    <script type="text/javascript">
        $(document).on("change", ".check-all", function() {
            if (this.checked) {
                // Iterate each checkbox
                $('.check-one:checkbox').each(function() {
                    this.checked = true;
                });
            } else {
                $('.check-one:checkbox').each(function() {
                    this.checked = false;
                });
            }

        });

        function bulk_delete() {
            var data = new FormData($('#sort_products')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('seller.product_bulk_upload.trendyol.import') }}",
                type: 'POST',
                data: data,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    if (response == 1) {
                        location.reload();
                    }
                }
            });
        }
    </script>
@endsection
