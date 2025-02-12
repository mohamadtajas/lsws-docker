<div class="modal fade" id="wallet_SyCard_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('Recharge Wallet By SYCard') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body gry-bg px-3 pt-3" style="overflow-y: inherit;">
                <form class="" action="{{ route('wallet.recharge.sycard') }}" method="post" autocomplete="off">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <label>{{ translate('Card Number') }} ( 16 )<span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" lang="en" class="form-control mb-3 rounded-0" name="card_number" maxlength="19"
                                oninput="formatCard(this, 16 , 4)" placeholder="{{ translate('Card Number') }}" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label>{{ translate('Card Serial') }} ( 6 )<span class="text-danger">*</span></label>
                        </div>
                        <div class="col-md-8">
                            <input type="text" lang="en" class="form-control mb-3 rounded-0" name="card_serial" maxlength="7"
                                oninput="formatCard(this, 6 , 3)" placeholder="{{ translate('Card Serial') }}" required>
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
<script>
    function formatCard(input, maxLength , split) {

        let value = input.value.replace(/\D/g, '');
        if (value.length > maxLength) {
            value = value.slice(0, maxLength);
        }
        const regex = new RegExp(`(\\d{${split}})(?=\\d)`, 'g');
        input.value = value.replace(regex, '$1-');
    }
</script>
