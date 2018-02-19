@extends('layouts.lodepart-fancy')

@section('title')
<title>{{trans('lodepart.forbidden')}}</title>
@stop
@section('adaptable-area')
<div id="page-content">
    <div class="main-menu-content">
        <i class="fa fa-exclamation-triangle fa-2x" aria-hidden="true"></i> {{trans('lodepart.forbidden-msg')}}
    </div>
</div>
@stop