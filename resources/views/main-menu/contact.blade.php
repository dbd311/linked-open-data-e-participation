@extends('layouts.lodepart-fancy')

@section('title')
<title>{{trans('lodepart.contact-subject')}}</title>
@stop
@section('adaptable-area')
<div id="page-content">
    <div class="main-menu-content">
        {{trans('lodepart.contact-webmaster')}}: 
        <a href="mailto:OPDL-EPARTICIPATION@publications.europa.eu?subject={{trans('lodepart.contact-subject')}}">OPDL-EPARTICIPATION@publications.europa.eu</a>
    </div>
</div>
@stop