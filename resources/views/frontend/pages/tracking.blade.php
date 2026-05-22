@extends('frontend.layouts.master')
@section('title')
    {{ __('levels.parcel_tracking') }} | {{ @settings()->name }}
@endsection 
@section('content') 
<section class="container-fluid pb-5  ">
    <div class="container pt-5 pb-5 ">
        <div class="row align-items-center mt-3">
            <div class="col-lg-8 m-auto"> 
                <form action="{{ route('tracking.index') }}" method="GET">
                   
                    <div class="input-group mb-3 tracking-page tracking-form">
                        <input type="text" class="form-control" placeholder="{{ __('levels.enter_tracking_id') }}" name="tracking_id" value="{{ $request->tracking_id }}"  >
                        <div class="input-group-append">
                            <button type="submit" class="input-group-text bg-primary"  >{{ __('levels.track_now') }}</button>
                        </div>
                    </div>
                </form>
                <h3 class="font-size-1-5rem display-6 font-weight-bold text-center my-4">{{ __('levels.parcel_tracking_no') }}: <span class="text-primary"># {{ $request->tracking_id }}</span></h3>
            </div>  
        </div> 
        <div class="parcel-oprations"> 
            @if(!empty($request->tracking_id) && $parcel)
            <section class="cd-timeline js-cd-timeline">
                <div class="cd-timeline__container">  
                    @foreach ($parcelevents as $key=>$log)
                        @php
                            if(!empty($log->cancel_parcel_id)): 
                                $cancel   = ' cancel';
                                $danger   = 'danger'; 
                            else:
                                $cancel   = null;
                                $danger   = null; 
                            endif; 
                           
                        @endphp
         
                                <div class="cd-timeline__block js-cd-block">
                                    <div class="cd-timeline__img cd-timeline__img--picture js-cd-img {{ isset($danger)? 'bg-danger':'' }}">
                                        <i class="timeline_icon fas {{ isset($danger)? 'fa-close':'fa-check' }}" aria-hidden="true"></i>
                                    </div>
                                    <!-- cd-timeline__img -->
                                    <div class="cd-timeline__content js-cd-content">
                                        <strong>{{__('parcelLogs.'.$log->parcel_status)}} {{ isset($danger)? $cancel:'' }}</strong><br>
          
        
                                        <div class="cd-timeline__date">
                                            <strong>{!! dateFormat($log->created_at) !!}</strong><br>
                                            <small>{!! date('h:i a', strtotime($log->created_at)) !!}</small>
                                        </div>
                                    </div>
                                    <!-- cd-timeline__content -->
                                </div>
                      
                    @endforeach 
                    <div class="cd-timeline__block js-cd-block">
                        <div class="cd-timeline__img cd-timeline__img--picture js-cd-img">
                            <i class="timeline_icon fas fa-check" aria-hidden="true"></i>
                        </div>
                        <!-- cd-timeline__img -->
                        <div class="cd-timeline__content js-cd-content">
                            <strong>{{__('parcel.parcel_create')}}</strong><br>



        
                            <div class="cd-timeline__date">
                                <strong>{!! dateFormat($parcel->created_at) !!}</strong><br>
                                <small>{!! date('h:i a', strtotime($parcel->created_at)) !!}</small>
                            </div>
                        </div>
                        <!-- cd-timeline__content -->
                    </div> 
                </div>
            </section>
            <!-- cd-timeline -->
            @elseif(!empty($request->tracking_id) && !$parcel) 
                <div class="row my-5">
                    <div class="col-lg-6 m-auto">
                        <img src="{{ static_asset('frontend/images/parcel-was-not-found.png') }}" width="100%"/>
                    </div>
                </div> 
            @endif
        </div>
    </div>
</section> 
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ static_asset('frontend/css/timeline.css') }}"/>
@endpush