
@extends('errors.layout')
@section('title', __('An Error Occurred:Method not allowed'))
@section('code', '405')
@section('message-headline', __('Oops! An Error Occurred.'))
@section('message-title', __('The server returned a "405 Method Not Allowed".'))
@section('message', __('This action is not allowed on this URL.'))
