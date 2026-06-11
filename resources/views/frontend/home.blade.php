@extends('frontend.layouts.master')

@section('title')
    {{ __('levels.home') }} | {{ @settings()->name }}
@endsection

@section('content')
    @include('frontend.section.banner')
    @include('frontend.section.partner')
    @include('frontend.section.why_courier')

    @if (tenant())
        @include('frontend.section.service')
    @endif

    @include('frontend.section.achievement')
    @include('frontend.section.testimonials')

    @include('frontend.section.cta')
    @include('frontend.section.blogs')
@endsection
