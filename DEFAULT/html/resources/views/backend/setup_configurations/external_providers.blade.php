@extends('backend.layouts.app')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0 h6">{{ translate('External Providers') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-bordered demo-dt-basic text-center" id="tranlation-table"
                        cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th width="40%">{{ translate('Name') }}</th>
                                <th width="35%">{{ translate('Status') }}</th>
                                <th width="25%">{{ translate('Balance') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($providers as $provider)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $provider['name'] }}</td>
                                    <td>
                                        @if ($provider['status'] == 'active')
                                            <span class="badge badge-inline badge-success">{{ translate('Enabled') }}</span>
                                        @else
                                            <span class="badge badge-inline badge-danger">{{ translate('Disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ single_price($provider['balance']) }} </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
