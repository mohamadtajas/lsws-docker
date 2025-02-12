<form class="form-default" role="form" action="{{ route('addresses.update', $address_data->id) }}" method="POST">
    @csrf
    <div class="p-3">
        <!-- First Name -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('First Name') }}</label>
            </div>
            <div class="col-md-10">
                <input type="text" class="form-control mb-3 rounded-0" placeholder="John" name="first_name"
                    value="{{ $address_data->first_name }}" required>
            </div>
        </div>

        <!-- Last Name -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('Last Name') }}</label>
            </div>
            <div class="col-md-10">
                <input type="text" class="form-control mb-3 rounded-0" placeholder="Doe" name="last_name"
                    value="{{ $address_data->last_name }}" required>
            </div>
        </div>

        <!-- ID Number -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('ID Number') }}</label>
            </div>
            <div class="col-md-10">
                <input type="number" class="form-control mb-3 rounded-0" placeholder="999********" name="id_number"
                    value="{{ $address_data->id_number }}" required dir="ltr">
            </div>
        </div>

        <!-- Address -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('Address') }}</label>
            </div>
            <div class="col-md-10">
                <textarea class="form-control mb-3 rounded-0" placeholder="{{ translate('Your Address') }}" rows="2"
                    name="address" required>{{ $address_data->address }}</textarea>
            </div>
        </div>

        <!-- Country -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('Country') }}</label>
            </div>
            <div class="col-md-10">
                <div class="mb-3">
                    <select class="form-control aiz-selectpicker rounded-0" data-live-search="true"
                        data-placeholder="{{ translate('Select your country') }}" name="country_id" id="edit_country"
                        required>
                        <option value="">{{ translate('Select your country') }}</option>
                        @foreach (get_active_countries() as $key => $country)
                            <option value="{{ $country->id }}" @if ($address_data->country_id == $country->id) selected @endif>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- State -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('State') }}</label>
            </div>
            <div class="col-md-10">
                <select class="form-control mb-3 aiz-selectpicker rounded-0" name="state_id" id="edit_state"
                    data-live-search="true" required>
                    @foreach ($states as $key => $state)
                        <option value="{{ $state->id }}" @if ($address_data->state_id == $state->id) selected @endif>
                            {{ $state->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- City -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('City') }}</label>
            </div>
            <div class="col-md-10">
                <select class="form-control mb-3 aiz-selectpicker rounded-0" data-live-search="true" name="city_id"
                    required>
                    @foreach ($cities as $key => $city)
                        <option value="{{ $city->id }}" @if ($address_data->city_id == $city->id) selected @endif>
                            {{ $city->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        @if (get_setting('google_map') == 1)
            <!-- Google Map -->
            <div class="row mt-3 mb-3">
                <input id="edit_searchInput" class="controls" type="text" placeholder="Enter a location">
                <div id="edit_map"></div>
                <ul id="geoData">
                    <li style="display: none;">Full Address: <span id="location"></span></li>
                    <li style="display: none;">Country: <span id="country"></span></li>
                    <li style="display: none;">Latitude: <span id="lat"></span></li>
                    <li style="display: none;">Longitude: <span id="lon"></span></li>
                </ul>
            </div>
            <!-- Longitude -->
            <div class="row">
                <div class="col-md-2" id="">
                    <label for="exampleInputuname">{{ translate('Longitude') }}</label>
                </div>
                <div class="col-md-10" id="">
                    <input type="text" class="form-control mb-3 rounded-0" id="edit_longitude" name="longitude"
                        value="{{ $address_data->longitude }}" readonly="">
                </div>
            </div>
            <!-- Latitude -->
            <div class="row">
                <div class="col-md-2" id="">
                    <label for="exampleInputuname">{{ translate('Latitude') }}</label>
                </div>
                <div class="col-md-10" id="">
                    <input type="text" class="form-control mb-3 rounded-0" id="edit_latitude" name="latitude"
                        value="{{ $address_data->latitude }}" readonly="">
                </div>
            </div>
        @endif



        <!-- Phone -->
        <div class="row">
            <div class="col-md-2">
                <label>{{ translate('Phone') }}</label>
            </div>
            <div class="col-md-10">
                <input type="text" class="form-control mb-3 rounded-0" placeholder="{{ translate('+880') }}"
                    value="{{ $address_data->phone }}" name="phone" value="" required dir="ltr">
            </div>
        </div>

        <!-- Save button -->
        <div class="form-group text-right">
            <button type="submit" class="btn btn-primary rounded-0 w-150px">{{ translate('Save') }}</button>
        </div>
    </div>
</form>
