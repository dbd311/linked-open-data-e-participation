@extends('layouts.lodepart-fancy')
@section('title')
<title>{{trans('lodepart.about')}}</title>
@stop

@section('adaptable-area')
<div id="page-content">
    <div class="main-menu-content">
        <p>{{trans('lodepart.about-1')}}</p>
        <p>{{trans('lodepart.about-2')}}</p>
        <p>{{trans('lodepart.about-3')}}</p>
        <ul>
            <li>{{trans('lodepart.about-4')}}</li>
            <li>{{trans('lodepart.about-5')}}</li>
        </ul>
    </div>
</div>
@stop