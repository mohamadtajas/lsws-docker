@extends('frontend.layouts.app')
@section('content')
<section class="py-5">
    <div class="container">
        <div class="d-flex align-items-start">
			@include('frontend.inc.user_side_nav')
			<div class="aiz-user-panel overflow-hidden" style="width : 100% !important ; ">
			    <!-- google translate -->
                <div id="google_translate_element" style="display: none !important;"></div>
				@yield('panel_content')
            </div>
        </div>
    </div>
</section>
@endsection