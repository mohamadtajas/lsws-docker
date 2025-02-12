@extends('backend.layouts.app')

@section('content')
    <div class="aiz-titlebar text-left mt-2 mb-3">
        <div class="align-items-center">
            <h1 class="h3">{{ translate('All Customers') }}</h1>
        </div>
    </div>


    <div class="card">
        <form class="" id="sort_customers" action="" method="GET">
            <div class="card-header row gutters-5">
                <div class="col">
                    <h5 class="mb-0 h6">{{ translate('Customers') }}</h5>
                </div>

                <div class="dropdown mb-2 mb-md-0">
                    <button class="btn border dropdown-toggle" type="button" data-toggle="dropdown">
                        {{ translate('Bulk Action') }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a class="dropdown-item confirm-alert" href="javascript:void(0)"
                            data-target="#bulk-delete-modal">{{ translate('Delete selection') }}</a>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group mb-0">
                        <input type="text" class="aiz-date-range form-control" value="{{ $date }}" name="date"
                            placeholder="{{ translate('Filter by date') }}" data-format="DD-MM-Y" data-separator=" to "
                            data-advanced-range="true" autocomplete="off">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-control aiz-selectpicker" name="status">
                        <option value="">{{ translate('Verified / Unverified') }}</option>
                        <option value="verified"
                            @isset($status) @if ($status == 'verified') selected @endif @endisset>
                            {{ translate('Verified') }}</option>
                        <option value="unverified"
                            @isset($status) @if ($status == 'unverified') selected @endif @endisset>
                            {{ translate('Unverified') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-group mb-0">
                        <input type="text" class="form-control" id="search"
                            name="search"@isset($sort_search) value="{{ $sort_search }}" @endisset
                            placeholder="{{ translate('Type email or name & Enter') }}">
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

            <div class="card-body">
                <table class="table aiz-table mb-0">
                    <thead>
                        <tr>
                            <!--<th data-breakpoints="lg">#</th>-->
                            <th data-breakpoints="lg">#</th>
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
                            <th>{{ translate('Name') }}</th>
                            <th data-breakpoints="lg">{{ translate('Email Address') }}</th>
                            <th data-breakpoints="lg">{{ translate('Phone') }}</th>
                            <th data-breakpoints="lg">{{ translate('Defualt Address Phone') }}</th>
                            <th data-breakpoints="lg">{{ translate('Package') }}</th>
                            <th data-breakpoints="lg">{{ translate('Wallet Balance') }}</th>
                            <th data-breakpoints="lg">{{ translate('Number of Orders') }}</th>
                            <th data-breakpoints="lg">{{ translate('Number of Initations') }}</th>
                            <th data-breakpoints="xl">{{ translate('All Recharge Amount') }}</th>
                            <th data-breakpoints="xl">{{ translate('Total Expenditure') }}</th>
                            <th data-breakpoints="lg">{{ translate('Total Remaining') }}</th>
                            <th data-breakpoints="xl">{{ translate('Date Joined') }}</th>
                            <th data-breakpoints="lg">{{ translate('Status') }}</th>
                            <th class="text-right">{{ translate('Options') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $key => $user)
                            @if ($user != null)
                                <tr>
                                    <td>{{ $key + 1 + ($users->currentPage() - 1) * $users->perPage() }}</td>
                                    <td>
                                        <div class="form-group">
                                            <div class="aiz-checkbox-inline">
                                                <label class="aiz-checkbox">
                                                    <input type="checkbox" class="check-one" name="id[]"
                                                        value="{{ $user->id }}">
                                                    <span class="aiz-square-check"></span>
                                                </label>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($user->banned == 1)
                                            <i class="fa fa-ban text-danger" aria-hidden="true"></i>
                                        @endif {{ $user->name }}
                                    </td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->phone }}</td>
                                    <td>{{ $user->addresses->first()->phone ?? '' }}</td>
                                    <td>
                                        @if ($user->customer_package != null)
                                            {{ $user->customer_package->getTranslation('name') }}
                                        @endif
                                    </td>
                                    <td>{{ single_price($user->balance) }}</td>
                                    <td>{{ $user->orders->count() }}</td>
                                    <td>{{ $user->invitedUsers->count() }}</td>
                                    @php
                                        $all_recharge_amount = $user->wallets->sum('amount');
                                        $all_expenditure = $user->orders
                                            ->flatMap(function ($order) {
                                                return $order->orderDetails
                                                    ->where('payment_status', 'paid')
                                                    ->where('delivery_status', '!=', 'cancelled');
                                            })
                                            ->sum('price');
                                    @endphp
                                    <td>{{ single_price($all_recharge_amount) }}</td>
                                    <td>{{ single_price($all_expenditure) }}</td>
                                    <td>{{ single_price($all_recharge_amount - $all_expenditure) }}</td>
                                    <td>{{ date('d-m-Y', strtotime($user->created_at)) }}</td>
                                    <td>
                                        @if ($user->email_verified_at != null)
                                            <span
                                                class="badge badge-inline badge-success">{{ translate('Verified') }}</span>
                                        @else
                                            <span
                                                class="badge badge-inline badge-warning">{{ translate('Unverified') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-right">
                                        @can('login_as_customer')
                                            <a href="#" class="btn btn-soft-info btn-icon btn-circle btn-sm"
                                                onclick="customer_info({{ $user->id }});"
                                                title="{{ translate('Customer Info') }}">
                                                <i class="las la-info"></i>
                                            </a>
                                            <a href="{{ route('customers.login', encrypt($user->id)) }}"
                                                class="btn btn-soft-primary btn-icon btn-circle btn-sm"
                                                title="{{ translate('Log in as this Customer') }}">
                                                <i class="las la-edit"></i>
                                            </a>
                                        @endcan
                                        @can('ban_customer')
                                            @if ($user->banned != 1)
                                                <a href="#" class="btn btn-soft-danger btn-icon btn-circle btn-sm"
                                                    onclick="confirm_ban('{{ route('customers.ban', encrypt($user->id)) }}');"
                                                    title="{{ translate('Ban this Customer') }}">
                                                    <i class="las la-user-slash"></i>
                                                </a>
                                            @else
                                                <a href="#" class="btn btn-soft-success btn-icon btn-circle btn-sm"
                                                    onclick="confirm_unban('{{ route('customers.ban', encrypt($user->id)) }}');"
                                                    title="{{ translate('Unban this Customer') }}">
                                                    <i class="las la-user-check"></i>
                                                </a>
                                            @endif
                                        @endcan
                                        @can('delete_customer')
                                            <a href="#"
                                                class="btn btn-soft-danger btn-icon btn-circle btn-sm confirm-delete"
                                                data-href="{{ route('customers.destroy', $user->id) }}"
                                                title="{{ translate('Delete') }}">
                                                <i class="las la-trash"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <div class="aiz-pagination">
                    {{ $users->appends(request()->input())->links() }}
                </div>
            </div>
        </form>
    </div>


    <div class="modal fade" id="confirm-ban">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Do you really want to ban this Customer?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a type="button" id="confirmation" class="btn btn-primary">{{ translate('Proceed!') }}</a>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirm-unban">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title h6">{{ translate('Confirmation') }}</h5>
                    <button type="button" class="close" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>{{ translate('Do you really want to unban this Customer?') }}</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">{{ translate('Cancel') }}</button>
                    <a type="button" id="confirmationunban" class="btn btn-primary">{{ translate('Proceed!') }}</a>
                </div>
            </div>
        </div>
    </div>
    @can('login_as_customer')
        <div class="modal fade" id="CustomerInfo">
            <div class="modal-dialog modal-dialog-centered modal-dialog-zoom product-modal" id="modal-size" role="document">
                <div class="modal-content position-relative">
                    <div class="c-preloader text-center p-3">
                        <i class="las la-spinner la-spin la-3x"></i>
                    </div>
                    <button type="button"
                        class="close absolute-top-right btn-icon close z-1 btn-circle bg-gray mr-2 mt-2 d-flex justify-content-center align-items-center"
                        data-dismiss="modal" aria-label="Close"
                        style="background: #ededf2; width: calc(2rem + 2px); height: calc(2rem + 2px);">
                        <span aria-hidden="true" class="fs-24 fw-700" style="margin-left: 2px;">&times;</span>
                    </button>
                    <div id="customer-info-modal-body" class="overflow-auto"
                        style="max-height: 90vh; scrollbar-width: none; -ms-overflow-style: none;">

                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('modal')
    <!-- Delete modal -->
    @include('modals.delete_modal')
    <!-- Bulk Delete modal -->
    @include('modals.bulk_delete_modal')
@endsection

@section('script')
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

        function sort_customers(el) {
            $('#sort_customers').submit();
        }

        function confirm_ban(url) {
            $('#confirm-ban').modal('show', {
                backdrop: 'static'
            });
            document.getElementById('confirmation').setAttribute('href', url);
        }

        function confirm_unban(url) {
            $('#confirm-unban').modal('show', {
                backdrop: 'static'
            });
            document.getElementById('confirmationunban').setAttribute('href', url);
        }

        function bulk_delete() {
            var data = new FormData($('#sort_customers')[0]);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ route('bulk-customer-delete') }}",
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
    @can('login_as_customer')
        <script>
            function customer_info(userId) {
                $('#customer-info-modal-body').html(null);
                $('#CustomerInfo').modal();
                $('.c-preloader').show();
                let url = `{{ route('customer_info', ':userId') }}`.replace(':userId', userId);
                $.ajax({
                    type: "get",
                    url: url,
                    success: function(data) {
                        if (data.status > 0) {
                            $('.c-preloader').hide();
                            $('#customer-info-modal-body').html(data.modal_view);
                        } else {
                            setTimeout(function() {
                                $('#CustomerInfo').modal('hide');
                            }, 100);
                        }
                    }
                });
            }
        </script>
    @endcan
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

                const formData = $('#sort_customers').serialize();
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
                            link.setAttribute('download', 'customers.xlsx');
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
