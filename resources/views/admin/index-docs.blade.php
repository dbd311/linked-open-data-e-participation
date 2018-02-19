@extends('layouts.lodepart-fancy')

@section('title')
<title>Semantic Document Indexing service</title>
@stop

@section('adaptable-area')
<div id="page-content" ng-controller="elasticSearchCtrl" ng-cloak>
    <form ng-submit="indexDocuments()"> 
        <div class="row">
            <div class="col-xs-3">
                <div class="input-group">
                    <span class="input-group-addon">{{trans('lodepart.date-from')}}</span>
                    <input type="text" class="form-control" maxlength="40" 
                           ng-model="dateFrom" id="datepickerFrom" readonly="readonly" ng-click="resetDateFrom()">
                </div>
            </div>
            <div class="col-xs-3">
                <div class="input-group">
                    <span class="input-group-addon">{{trans('lodepart.date-to')}}</span>
                    <input type="text" class="form-control" maxlength="40" 
                           ng-model="dateTo" id="datepickerTo" ng-click="resetDateTo()">
                </div>
            </div>
            <div class="col-xs-1">
                <div class="input-group">
                    <span class="input-group-addon">
                        <label title="{{trans('lodepart.index-all')}}"><input type="checkbox" style="vertical-align: bottom" ng-model="dateAll" ng-click="resetDates()" ng-checked="true"> {{trans('lodepart.date-all')}}</label>
                    </span>
                </div>
            </div>
        </div>
        <br />
        <div>
            <input type="submit" value="{{trans('lodepart.index-start')}}" >
        </div>
    </form>
    <br />
    <div ng-show="counter >= 0">
        <div><span ng-bind="startTime" style="padding-right: 20px">[[startTime | date: "yy-MM-ddTHH:mm:ss"]]</span><span ng-if="startTime">{{trans('lodepart.index-starting')}} </span></div>
        <div><span ng-bind="endTime" style="padding-right: 20px">[[endTime | date: "yy-MM-ddTHH:mm:ss"]]</span><span ng-if="counter > 0" ng-bind="counter"></span><span ng-if="counter < 0">{{trans('lodepart.cannot-index')}}</span><span ng-if="counter > 0"> {{trans('lodepart.docs-index')}}</span></div>
        <div><span ng-show="counter === 0" style="color: red">{{trans('lodepart.not-able')}}</span></div>
    </div>


</div>
@stop