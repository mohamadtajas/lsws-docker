@extends('backend.layouts.app')

@section('content')

<div class="aiz-titlebar text-left mt-2 mb-3">
    <div class=" align-items-center">
        <h1 class="h3">{{translate('Invitation Report')}}</h1>
    </div>
</div>

<div class="row">
    <div class="col-md-10 mx-auto">
        <div class="card">
            <form action="{{ route('invitation-report.index') }}" method="GET" id="sort_invitation">
                <div class="card-header row gutters-5">
                    <div class="col text-center text-md-left">
                        <h5 class="mb-md-0 h6">{{ translate('Invitation Report') }}</h5>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control form-control-sm aiz-date-range" name="date_range"@isset($date_range) value="{{ $date_range }}" @endisset placeholder="{{ translate('Daterange') }}">
                        </div>
                    </div>
                    <div class="col-lg-2 ml-auto">
                        <select class="form-control form-control-sm aiz-selectpicker mb-2 mb-md-0" name="used">
                            <option value="">{{ translate('Used / unused') }}</option>
                            <option value="used" @selected($used == 'used')  >{{ translate('Used') }}</option>
                            <option value="unused" @selected($used == 'unused') >{{ translate('Unused') }}</option>
                        </select>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control" id="invited_by_user" name="invited_by_user"
                                @isset($invited_by_user) value="{{ $invited_by_user }}" @endisset
                                placeholder="{{ translate('Type Invited By User') }}">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group mb-0">
                            <input type="text" class="form-control" id="invited_user" name="invited_user"
                                @isset($invited_user) value="{{ $invited_user }}" @endisset
                                placeholder="{{ translate('Type Invited User') }}">
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
                            <th>{{ translate('Invited User')}}</th>
                            <th>{{ translate('Invited By User')}}</th>
                            <th>{{ translate('Usage Status')}}</th>
                            <th>{{  translate('Date') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invitations as $key => $invitation)
                            <tr>
                                <td>{{ $loop->iteration + ($invitations->perPage() * ($invitations->currentPage() - 1)) }}</td>
                                <td>{{ $invitation->invited_user }}</td>
                                <td>{{ $invitation->invited_by_user }}</td>
                                <td>
                                    @if($invitation->used == 1)
                                        {{ translate('Used') }}
                                    @else
                                        {{ translate('Unused') }}
                                    @endif
                                </td>
                                <td>{{ $invitation->created_at }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination mt-4">
                    {{ $invitations->appends(request()->input())->links() }}
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

            const formData = $('#sort_invitation').serialize(); // Collects all input values in the form as a query string
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
                        link.setAttribute('download', 'invitation_report.xlsx');
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
@endsection
