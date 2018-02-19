@extends('layouts.lodepart-fancy')

@section('title')
<title>Clean index</title>
@stop

@section('adaptable-area')
<div id="page-content" ng-controller="elasticSearchCtrl" ng-cloak>
    <div class="container" ng-init="loadIndices()">
        <div class="row" ng-show="indices.length > 0">
            <div class="col-md-8" >
                <form ng-submit="cleanIndex(currentIndex.name, deleteAll)">
                    <div class="row">
                        <div class="col-md-8">
                            {{trans('lodepart.select-index')}} :
                            <select ng-init="currentIndex = currentIndex || indices[0].eli_lang_code" ng-model="currentIndex" 
                                    ng-options="index.eli_lang_code for index in indices">
                            </select>   
                            
                        </div>
                        <div class="col-md-8">
                            <div>{{trans('lodepart.index-name')}} : <span ng-bind="currentIndex.name"></span></div>
                            <div>{{trans('lodepart.language')}} : <span ng-bind="currentIndex.eli_lang_code"></span></div>
                        </div>                       
                    </div>
                    <div class="row">                       
                        <div class="col-md-4">
                            <label><input type="checkbox" title="{{trans('lodepart.delete-all')}}" ng-model="deleteAll"/> {{trans('lodepart.delete-all')}}</label>
                        </div>
                    </div>
                    <div class="row"> 
                        <div class="col-md-2">
                            <input type="submit" value="{{trans('lodepart.clean')}}" />
                        </div>
                    </div>
                </form>
            </div>

        </div>
        <div class="row" ng-hide="indices.length > 0">
            <div class="col-md-5">
                There is nothing to clean because your index is empty.
                Click <a href="/cpanel/elastic/document-indexing">here</a> for re-index.
            </div>
        </div>
    </div>

    <div><span ng-bind="endTime" style="padding-right: 20px">[[endTime | date: "yy-MM-ddTHH:mm:ss"]]</span><span ng-bind="counter"></span> <span ng-if="counter > 0"> {{trans('lodepart.indices-deleted')}}.</span></div>
</div>
@stop