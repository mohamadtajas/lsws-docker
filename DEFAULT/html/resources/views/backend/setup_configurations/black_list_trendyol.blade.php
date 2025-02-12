@extends('backend.layouts.app')

@section('content')

    <div class="card">
        <div class="card-header row gutters-5">
         <div class="col text-center text-md-left">
           <h5 class="mb-md-0 h6">{{ translate('Trendyol Black List') }}</h5>
         </div>
         <div class="col-md-4">
           <form class="" id="sort_keys" action="" method="GET">
             <div class="input-group input-group-sm">
                 <input type="text" class="form-control" id="search" name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset placeholder="{{ translate('Type category name') }}">
             </div>
           </form>
         </div>
       </div>
            <div class="card-body text-center">
                <table class="table table-striped table-bordered demo-dt-basic" id="tranlation-table" cellspacing="0" width="100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th width="45%">{{ translate('Category Name') }}</th>
                            <th width="45%">{{ translate('Active') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($trendyolCategories as $key => $category)
                            <tr>
                                <td>{{ ($key+1) }}</td>
                                <td class="key">{{ $category->name }}</td>
                                <td>
                                    <label class="aiz-switch aiz-switch-success">
                                        <input type="checkbox" name="checked[]" class="form-control demo-sw" value="{{ $category->id }}"
                                        onchange="updateCategoryStatus(this.value)"
                                        @if ($category->active)
                                            checked
                                        @endif
                                        >
                                        <span class="slider round"></span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                   {{ $trendyolCategories->withPath(url()->current())->appends(request()->input())->links() }}
                </div>
            </div>
    </div>

@endsection
@section('script')
    <script type="text/javascript">
    
        function sort_keys(el){
            $('#sort_keys').submit();
        }
        function updateCategoryStatus(categoryId){
            $.ajax({
                    type:"POST",
                    url: '{{ route('trendyol.black_list.update') }}',
                    data: {'categoryId': categoryId , _token: AIZ.data.csrf},
                    success: function(data){
                        if(data.status){
                            AIZ.plugins.notify('success', "{{ translate('Category has been updated successfully') }}");
                        }
                        else{
                            AIZ.plugins.notify('warning', "{{ translate('Something went wrong') }}");
                        }
                    }
            });
        }
    </script>
@endsection
