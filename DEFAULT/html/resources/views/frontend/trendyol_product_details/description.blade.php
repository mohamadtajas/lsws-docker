<div class="bg-white mb-4 border p-3 p-sm-4">
    <!-- Tabs -->
    @if (count($detailedProduct['descriptions']) > 0)
        <div class="nav aiz-nav-tabs">

            <a href="#tab_default_1" data-toggle="tab"
                class="mr-5 pb-2 fs-18 fw-900 text-reset active show">{{ translate('Description') }}</a>

        </div>
    @endif
    <!-- Description -->
    <div class="tab-content pt-0">
        @if (count($detailedProduct['descriptions']) > 0)
            <!-- Description -->
            <div class="tab-pane fade active show" id="tab_default_1">
                <div class="py-3">
                    <div class="mw-100 overflow-hidden text-left aiz-editor-data">
                        <div class="row">
                            <div class="col-lg-2 col-12">
                                <img src="{{ $detailedProduct['photos'][0] ?? '' }}" width="80%">
                            </div>
                            <div class="col-lg-10 col-12 d-flex align-items-center flex-wrap">
                                <ul>
                                    @foreach ($detailedProduct['descriptions'] as $description)
                                        @if (strpos($description->text, '[/page]') === false && strpos($description->text, 'Trendyol') === false)
                                            <li>{{ $description->text }}</li>
                                            <br>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
        @endif
        @if (count($detailedProduct['attributes']) > 0)
            <div class="nav aiz-nav-tabs">
                <a href="#tab_default_2" data-toggle="tab"
                    class="mr-5 pb-2 fs-18 fw-900 text-reset active show">{{ translate('Features') }}</a>
            </div>
            <!-- Features -->
            <div class="tab-pane fade active show" id="tab_default_2">
                <div class="py-3">
                    <div class="mw-100 overflow-hidden text-left aiz-editor-data">
                        <div class="row justify-content-center">
                            @foreach ($detailedProduct['attributes'] as $key => $attribute)
                                <div class="col-lg-6 col-12 px-4 my-2">
                                    <div class="row bg-light fw-900">
                                        <div class="col-6 px-4 py-3">{{ $key }}</div>
                                        <div class="col-6 px-3 py-3 d-flex justify-content-end text-muted">{{ $attribute }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
