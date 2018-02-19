@extends('elastic.main')

@section('title')
<title>{{trans('lodepart.search')}}</title>
@stop

@section('css-local')
<link rel="stylesheet" href="/css/pagination/zebra_pagination.css" />
@stop

@section('search-header')
@stop

@section('adaptable-area')
<div id="page-content" ng-controller="elasticSearchCtrl">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-7" ng-init="initSearchForm('{{str_replace("'", "\'", Request::get('q'))}}')"> 
                <form autocomplete="off" method="GET" action="/search">
                    <div class="input-group search-input">
                        <input type="text" class="form-control" placeholder="{{trans('lodepart.search')}}" name="q" ng-model="q">
                        <input type="hidden" value="{{Request::get('lang')}}" name="lang">

                        <div class="input-group-btn">
                            <button class="btn btn-xs search-btn" type="submit">
                                <i class="glyphicon glyphicon-search"></i>
                            </button>
                        </div>                
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-md-9 search-list-container" ng-init="search('{{urlencode(Request::get('q'))}}', '{{Request::get('lang')}}')">
                <div id="search-list-items">

                    <div class="row search-list-item"  ng-repeat="document in results">

                        <div class="col-lg-10 col-md-9 col-xs-9">
                            <a href="/lod/documents/displayDoc?path=[[document.path]]&hl=[[document._source.eli_lang_code]]&lang={{Request::get('lang')}}">
                                <span ng-if="isEmpty(document.highlight.title)" ng-bind-html="document._source.title"></span><span ng-bind-html="document.highlight.title[0]"></span>
                                <span ng-if="isEmpty(document.highlight.subject)" ng-bind-html="document._source.subject"></span><span ng-bind-html="document.highlight.subject[0]"></span>
                            </a>
                            <div  style="padding: 2px 0 2px 10px; margin-bottom: 10px">
                                <p>
                                    |
                                    <span ng-bind="document._source.year"></span> 
                                    <span ng-if="document._source.year && document.procedure"> | </span>
                                    <span ng-bind="document.procedure"></span>
                                    <span ng-if="document.themes"> | </span>                                
                                    <span ng-bind="document.themes"></span> 
                                </p>
                            </div>

                            <p>
                                <span ng-bind-html="document.summary"></span>
                            </p>
                        </div>

                        <div class="col-lg-2 col-md-3 col-xs-3 statist">
                            <div class="panel-default">
                                <div class="panel-heading">
                                    <i class="fa fa-comments-o"></i> 
                                    <span id="number-of-comments" ng-bind="document.nbOfComments"></span>
                                </div>
                                <div class="panel-body no-padding">
                                    <nvd3-pie-chart  
                                        data="document.dataPieChart"
                                        x="xFunction(document.dataPieChart)"
                                        y="yFunction(document.dataPieChart)"
                                        color="colorFunction()"                                    
                                        showLabels="true"
                                        pieLabelsOutside="false"
                                        tooltips="true"
                                        labelType="percent" width="280" height="320">
                                        <svg class="pie-chart-search-item"></svg>
                                    </nvd3-pie-chart>
                                </div>                                                
                            </div>                                            
                        </div>
                    </div>
                </div>

                <div class="row" ng-show="results != null && results.length === 0">
                    <div class="col-lg-10 col-md-9 col-xs-9">

                        {{trans('lodepart.your-search')}} - <strong>"{{Request::get('q')}}"</strong> - {{trans('lodepart.not-match')}}<br/><br/>
                        {{trans('lodepart.suggestions')}}:
                        <ul>
                            <li>{{trans('lodepart.search')}}</li>
                            <li>{{trans('lodepart.different-keywords')}}</li>
                            <li>{{trans('lodepart.general-keywords')}}</li>
                        </ul>
                    </div>

                </div>
            </div>

            <div ng-if="range().length > 1" class="center">
                <i ng-if="currentPage > 1" class="fa fa-angle-left item-page" aria-hidden="true" 
                   ng-click="previousPage()"></i>
                <span ng-repeat="item in range() track by $index" class="item-page" ng-click="changePage($index + 1)">
                    <span ng-if="$index + 1 != currentPage" ng-bind="$index + 1"></span>
                    <span ng-if="$index + 1 == currentPage" ng-bind="$index + 1" class="select-current-page"></span>
                </span>
                <i ng-if="currentPage < range().length" class="fa fa-angle-right item-page" 
                   aria-hidden="true" ng-click="nextPage()">
                </i>
            </div>
        </div>
    </div>
</div> 
@stop
