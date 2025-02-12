@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3">{{ translate('All Trendyol Seller') }}</h1>
            </div>
        </div>
    </div>

    <div class="card">
        <form action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-md-0 h6">{{ translate('All Trendyol Seller') }}</h5>
                </div>

                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control"
                            name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset
                            placeholder="{{ translate('Type name or email & Enter') }}">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" name="tax_number"
                            @isset($tax_number) value="{{ $tax_number }}" @endisset
                            placeholder="{{ translate('Type Tax Number & Enter') }}">
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Name') }}</th>
                            <th data-breakpoints="lg">{{ translate('Official Name') }}</th>
                            <th data-breakpoints="lg">{{ translate('Email Address') }}</th>
                            <th data-breakpoints="lg">{{ translate('Tax Number') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trendyol_sellers as $key => $trendyol_seller)
                            <tr>
                                <td>
                                    {{ $key + 1 + ($trendyol_sellers->currentPage() - 1) * $trendyol_sellers->perPage() }}
                                </td>
                                <td>
                                    {{ $trendyol_seller->name }}
                                </td>
                                <td>{{ $trendyol_seller->official_name }}</td>
                                <td>{{ $trendyol_seller->email }}</td>
                                <td>
                                    {{ $trendyol_seller->tax_number }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $trendyol_sellers->appends(request()->input())->links() }}
                </div>
            </div>
        </form>
    </div>
@endsection
