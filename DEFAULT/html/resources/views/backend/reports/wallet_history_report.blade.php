@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class=" align-items-center">
        <h1 class="h3">{{translate('Wallet Transaction Report')}}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <form action="{{ route('wallet-history.index') }}" method="GET" id="sort_wallet_history">
                <div class="card-header row gutters-5">
                    <div class="col text-center text-md-left">
                        <h5 class="mb-md-0 h6">{{ translate('Wallet Transaction') }}</h5>
                    </div>
                    @if(Auth::user()->user_type != 'seller')
                    <div class="col-lg-4">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control" id="customer" name="customer"
                                @isset($customer) value="{{ $customer }}" @endisset
                                placeholder="{{ translate('Type email or name & Enter') }}">
                        </div>
                    </div>
                    <div class="col-md-3 ml-auto">
                        <select id="demo-ease" class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0" name="payment_method">
                            <option value="">{{ translate('Choose Payment Method') }}</option>
                            @foreach ($payment_methods as $key => $value)
                                <option value="{{ $value }}" @if($value == $payment_method) selected @endif >
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control form-control-sm aiz-date-range" id="search" name="date_range"@isset($date_range) value="{{ $date_range }}" @endisset placeholder="{{ translate('Daterange') }}">
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary">{{ translate('Filter') }}</button>
                            <button id="exportBtn" class="btn btn-primary">
                                <div class="row">
                                    <div class="col-12 d-flex justify-content-center">
                                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"
                                            style="display: none; width: 1.3rem; height: 1.3rem; border-width: 0.2em; border-right-color: transparent;"></span>
                                        <span class="button-text">{{ translate('Excel') }}</span>
                                    </div>
                                </div>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <div class="card-body">

                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ translate('Customer')}}</th>
                            <th data-breakpoints="lg">{{  translate('Date') }}</th>
                            <th>{{ translate('Amount')}}</th>
                            <th data-breakpoints="lg">{{ translate('Payment Method')}}</th>
                            <th data-breakpoints="lg">{{ translate('Payment Details')}}</th>
                            <th data-breakpoints="lg" class="text-right">{{ translate('Approval')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($wallets as $key => $wallet)
                            <tr>
                                <td>{{ $loop->iteration + ($wallets->perPage() * ($wallets->currentPage() - 1)) }}</td>
                                @if ($wallet->user != null)
                                    <td>{{ $wallet->user->name }}</td>
                                @else
                                    <td>{{ translate('User Not found') }}</td>
                                @endif
                                <td>{{ date('d-m-Y', strtotime($wallet->created_at)) }}</td>
                                <td>{{ single_price($wallet->amount) }}</td>
                                <td>{{ ucfirst(str_replace('_', ' ', $wallet ->payment_method)) }}</td>
                                <td>{{ ucfirst($wallet->payment_details) }}</td>
                                <td class="text-right">
                                    @if ($wallet->offline_payment)
                                    @if ($wallet->approval)
                                        <span class="badge badge-inline badge-success p-3 fs-12" style="border-radius: 25px; min-width: 80px !important;">{{ translate('Approved') }}</span>
                                    @else
                                        <span class="badge badge-inline badge-info p-3 fs-12" style="border-radius: 25px; min-width: 80px !important;">{{ translate('Pending') }}</span>
                                    @endif
                                @else
                                    @if($wallet->payment_method == 'SY Card' || $wallet->payment_method == 'inner transfer' || $wallet->payment_method == 'invite')
                                    <span class="badge badge-inline badge-success p-3 fs-12" style="border-radius: 25px; min-width: 80px !important;">{{ translate('Approved') }}</span>
                                    @else
                                    N/A
                                    @endif
                                @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination mt-4">
                    {{ $wallets->appends(request()->input())->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
@section('script')
<script>
    // Create a Web Worker inline to handle the blob download process
    const blob = new Blob([`
        onmessage = function(e) {
            const url = URL.createObjectURL(e.data);
            postMessage(url);
        }
    `], {
        type: "application/javascript"
    });

    const worker = new Worker(URL.createObjectURL(blob));

    $(document).ready(function() {
        $('#exportBtn').click(function(event) {
            event.preventDefault();

            const $exportBtn = $(this);
            $exportBtn.prop('disabled', true);
            $exportBtn.find('.spinner-border').css('display', 'inline-block');
            $exportBtn.find('.button-text').text('');

            const formData = $('#sort_wallet_history').serialize(); // Collects all input values in the form as a query string
            const exportUrl = "{{ request()->url() }}?" + formData + "&export=excel";
            fetch(exportUrl)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.blob();
                })
                .then(blob => {
                    worker.postMessage(blob);
                    worker.onmessage = function(event) {
                        const downloadUrl = event.data;
                        const link = document.createElement('a');
                        link.href = downloadUrl;
                        link.setAttribute('download', 'wallet_report.xlsx');
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);

                        URL.revokeObjectURL(downloadUrl);
                        worker.terminate();

                        $exportBtn.removeClass('btn-primary').addClass('btn-success');
                        $exportBtn.find('.spinner-border').css('display', 'none');
                        $exportBtn.find('.button-text').text('{{ translate('Completed') }}');
                        $exportBtn.prop('disabled', true);
                    };
                })
                .catch(error => {
                    alert('Export failed. Please try again.');

                    $exportBtn.prop('disabled', false);
                    $exportBtn.removeClass('btn-success').addClass('btn-primary');
                    $exportBtn.find('.spinner-border').css('display', 'none');
                    $exportBtn.find('.button-text').text('{{ translate('Excel') }}');
                });
        });
    });
</script>
@endSection
