@can('trendyol_manual_order')
    <div class="row">
        <div class="col-10 mx-auto my-5">
            <div class="text-left">
                <form action="{{ route('bulk_trendyol_update_order_number') }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="orderIds" value="{{ json_encode($orderIds) }}">
                    <div class="form-group row" id="brand">
                        <label class="col-12 col-from-label fs-13">{{ translate('Trendyol Order Number') }}</label>
                        <label class="col-12 col-from-label fs-13">{{ translate('Num. of Items') }} : {{ $itemsCount }}</label>
                        <div class="col-12">
                            <input type="text" class="form-control" name="trendyol_order_number" required>
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
@endcan
