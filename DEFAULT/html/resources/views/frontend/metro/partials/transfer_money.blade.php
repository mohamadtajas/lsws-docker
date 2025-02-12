<div class="modal fade" id="transfer_money" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('Transfer Money') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body gry-bg px-3 pt-3" style="overflow-y: inherit;">
                <form class="" action="{{ route('wallet.transfer') }}" method="post" autocomplete="off">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <label>{{ translate('Email') }}<span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-8">
                            <input type="email" lang="en" class="form-control mb-3 rounded-0" name="email" 
                                placeholder="{{ translate('Email') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>{{ translate('Amount') }}<span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-8">
                            <input type="number" lang="en" class="form-control mb-3 rounded-0" name="amount" 
                                placeholder="{{ translate('Amount') }}" required>
                        </div>
                    </div>
                    <div class="form-group text-right">
                        <button type="submit"
                            class="btn btn-sm btn-primary rounded-0 transition-3d-hover mr-1">{{ translate('Confirm') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>