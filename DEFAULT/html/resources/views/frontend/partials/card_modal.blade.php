<!-- New Card Modal -->
<div class="modal fade" id="new-card-modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">{{ translate('Add New Card') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form class="form-default" role="form" action="{{ route('stb.card.add') }}" method="POST">
                @csrf
                <div class="modal-body c-scrollbar-light">
                    <div class="p-3">
                        <div class="row">
                            <div class="col-md-4">
                                <label>{{ translate('Owner') }}</label>
                            </div>
                            <div class="col-md-12">
                                <input type="text" class="form-control mb-3 rounded-0" id="owner" name="owner"
                                    required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <label>{{ translate('Card Number') }}</label>
                            </div>
                            <div class="col-md-12">
                                <input type="text" class="form-control mb-3 rounded-0" id="card_number" name="card_number" required
    oninput="formatCardNumber(this)" maxlength="19" minlength="16" dir="ltr">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="col-md-2">
                                    <label>{{ translate('CVV/CVC') }}</label>
                                </div>
                                <div class="col-md-12">
                                    <input type="text" class="form-control mb-3 rounded-0" id="cvv"
                                        name="cvv" required
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..?)\../g, '$1');"
                                        maxlength="3" minlength="3">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="col-md-2">
                                    <label>{{ translate('Month') }}</label>
                                </div>
                                <div class="col-md-12">
                                    <select name="month" id="month"
                                        class="form-control aiz-selectpicker rounded-0" required>
                                        <option value="01">01</option>
                                        <option value="02">02</option>
                                        <option value="03">03</option>
                                        <option value="04">04</option>
                                        <option value="05">05</option>
                                        <option value="06">06</option>
                                        <option value="07">07</option>
                                        <option value="08">08</option>
                                        <option value="09">09</option>
                                        <option value="10">10</option>
                                        <option value="11">11</option>
                                        <option value="12">12</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="col-md-2">
                                    <label>{{ translate('Year') }}</label>
                                </div>
                                <div class="col-md-12">
                                    <select name="year" id="year"
                                        class="form-control aiz-selectpicker rounded-0" required>
                                        <option value="2021">2021</option>
                                        @php
                                            for ($i = date('Y'); $i < date('Y', strtotime('+10 years')); $i++) {
                                                echo '<option value="' . $i . ' ">' . $i . '</option>';
                                            }
                                        @endphp

                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="pt-3 px-4 fs-14">
                            <label class="aiz-checkbox">
                                <input type="checkbox" id="save_data" name="save_data">
                                <span class="aiz-square-check"></span>
                                <span>{{ translate('Do you want to save card info') }}</span>
                            </label>

                        </div>
                        <!-- Save button -->
                        <div class="form-group text-right">
                            <button type="submit"
                                class="btn btn-primary rounded-0 w-150px">{{ translate('Save') }}</button>
                        </div>
                    </div>
                </div>


            </form>
        </div>
    </div>
</div>

@section('script')
    <script type="text/javascript">
        function add_new_card() {
            $('#new-card-modal').modal('show');
        }
        function formatCardNumber(input) {
    // Remove non-numeric characters and spaces
    var cardNumber = input.value.replace(/[^0-9]/g, '');

    // Add spaces every 4 digits
    cardNumber = cardNumber.replace(/(\d{4})(?=\d)/g, '$1 ');

    // Update the input value
    input.value = cardNumber;
}

// Attach an event listener to the form submission to remove spaces before submitting
$(function() {
    $('form').submit(function() {
        // Remove spaces before submitting
        $('#card_number').val(function(i, value) {
            return value.replace(/\s/g, '');
        });
    });
});
    </script>
@endsection
