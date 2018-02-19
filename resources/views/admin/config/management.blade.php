@extends('layouts.lodepart-fancy')

@section('title')
<title>General management</title>
@stop

@section('adaptable-area')
<div id="page-content" ng-controller="configCtrl">
    <div class="container" ng-init="loadConfiguration()">
        <div class="row" ng-repeat="param in params">
            <div class="col-md-5">
                <span ng-bind="param.name">
            </div>
            <div class="col-md-5">
                <span ng-bind="param.value">
            </div>
        </div>        
    </div>
</div>
@stop