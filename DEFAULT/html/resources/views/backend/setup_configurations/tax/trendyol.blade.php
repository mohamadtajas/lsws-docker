@extends('backend.layouts.app')

@section('content')
    <div class="card">
        <div class="card-header row gutters-5">
            <div class="col text-center text-md-left">
                <h5 class="mb-md-0 h6">{{ translate('Trendyol tax') }}</h5>
            </div>
            <div class="col-md-4">
                <form class="" id="sort_keys" action="" method="GET">
                    <div class="input-group input-group-sm">
                        <input type="text" class="form-control" id="search"
                            name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset
                            placeholder="{{ translate('Type category name') }}">
                    </div>
                </form>
            </div>
        </div>
        <form class="form-horizontal" action="{{ route('tax.trendyol.key_value_store') }}" method="POST">
            @csrf
            <div class="card-body">
                <table class="table table-striped table-bordered demo-dt-basic" id="tranlation-table" cellspacing="0"
                    width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th width="20%">{{ translate('Category Name') }}</th>
                            <th width="20%">{{ translate('Arabic Name') }}</th>
                            <th width="15%">{{ translate('Percent vlaue') }}</th>
                            <th width="15%">{{ translate('Flat vlaue') }}</th>
                            <th width="15%">{{ translate('Percent discount') }}</th>
                            <th width="15%">{{ translate('Flat discount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trendyolCategories as $key => $category)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td class="key">{{ $category->name }}</td>
                                <td class="key">{{ $category->ar_name }}</td>
                                <td>
                                    <input type="text" class="form-control value" style="width:100%"
                                        name="values[{{ $category->id }}][percent]" value="{{ $category->percent_tax }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control value" style="width:100%"
                                        name="values[{{ $category->id }}][flat]" value="{{ $category->flat_tax }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control value" style="width:100%"
                                        name="values[{{ $category->id }}][percent_discount]"
                                        value="{{ $category->percent_discount }}">
                                </td>
                                <td>
                                    <input type="text" class="form-control value" style="width:100%"
                                        name="values[{{ $category->id }}][flat_discount]"
                                        value="{{ $category->flat_discount }}">
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $trendyolCategories->withPath(url()->current())->appends(request()->input())->links() }}
                </div>

                <div class="form-group mb-0 text-right">
                    <button type="submit" class="btn btn-primary">{{ translate('Save') }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection
@section('script')
    <script type="text/javascript">
        function sort_keys(el) {
            $('#sort_keys').submit();
        }
    </script>
@endsection
