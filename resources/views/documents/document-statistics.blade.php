<?php
use App\Concepts\Container;
$path = Request::get('path');
$id_fmx_element = str_replace("_","",Request::get('id_fmx_element'));
$eli_lang_code = Request::get('eli_lang_code');
$doc_code = Request::get('doc_code');
$year = Request::get('year');
$num = Request::get('num');
$containerURI = Container::get_complete_URI_container(Request::get('doc_code'), Request::get('year'), Request::get('num'), $id_fmx_element, $eli_lang_code);
?>

@extends('layouts.lodepart-fancy')

@section('title')
<title><?php echo $id_fmx_element; ?></title>
@stop

@section('page-name')
<li><a title="{{trans('lodepart.preparatory-act')}}" href="/lod/documents/displayDoc?path={{$path}}&hl={{Config::get('app.eli_lang_code')}}&lang={{Config::get('app.locale')}}">{{trans('lodepart.preparatory-act')}}</a></li>
<li>{{trans('lodepart.more-stats')}}</li>
@stop

@section('adaptable-area')
<div id="page-content">
    <div class="container-fluid" ng-controller="moreStatisticsCtrl" ng-init="loadBarCharts('{{$containerURI}}',false,'{{trans('lodepart.no-data-comments')}}','{{trans('lodepart.no-data-amendments')}}')">
        <div id="loading" class="act center">
            <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.loading-stats')}}
        </div>
        <div class="container-fluid doc-table act" id="statsPage">
            <div class="header-document row" ng-init="loadDocument('{{$path}}')">  
                <div class="col-md-8 col-header-metadata">
                    <div class="title-document">
                        <span ng-bind-html="document.titleHtml | htmlFilter"></span>
                        <span ng-bind-html="document.subjectHtml | htmlFilter"></span>
                    </div>
                    <br />
                    <p ng-if="document.idCelex">
                        {{trans('lodepart.title-procedure-code')}} : <span ng-bind="document.procedureCode"></span> 
                    </p>
                    <p ng-if="document.idCelex">
                        {{trans('lodepart.celex')}} : <span ng-bind="document.idCelex"></span> 
                        <i class="fa fa-question-circle" aria-hidden="true" title="{{trans('lodepart.title-id-celex')}}"></i>
                    </p>
                    <p ng-if="document.dateAdopted">
                        {{trans('lodepart.title-date-adopted')}} : <span ng-bind="document.dateAdopted"></span> 
                    </p>
                    <p ng-if="document.procedureTypeLabel">
                        {{trans('lodepart.title-type-label')}} : <strong><span ng-bind="document.procedureTypeLabel"></span></strong> 
                    </p>
                    <p ng-if="document.directoryCode">
                        {{trans('lodepart.title-directory-code')}} : 
                        <span ng-bind="document.directoryCode"></span> 
                    </p>
                    <p>
                        {{trans('lodepart.topics')}} : <i ng-bind="document.topics"></i>
                    </p>
                    <div ng-repeat="annexe in document.annexes">
                        <i class="fa fa-file-pdf-o fa-lg" aria-hidden="true"></i> 
                        <a target="_blank" href="/../collection/formex-documents-json/[[document.folder]]/annexes/[[annexe]]">
                            <span ng-bind="annexe"></span>
                        </a>
                    </div>
                </div>

                <div class="col-md-2 col-header-comments">
                    <div ng-if="nbTotalComments != 0">
                        <nvd3-pie-chart
                            ng-if="nbTotalComments != 0"
                            data="dataPieChartDocument"
                            x="xFunctionPC()"
                            y="yFunctionPC()"
                            color="colorFunctionPC()"                        
                            width="300"
                            height="300" 
                            showLabels="true"
                            pieLabelsOutside="false"
                            tooltips="true"
                            tooltipcontent="toolTipContentFunctionPC()"
                            labelType="percent">
                            <svg class="pie-chart-document">
                            </svg>
                        </nvd3-pie-chart>
                    </div>
                </div>
                <div class="col-md-2 col-header-comments">
                    <div ng-if="nbTotalComments != 0">
                        <div class="center">
                            <span class="fa fa-comment fa-2x btn-inbody right" ng-bind="nbTotalComments"></span>
                            <br/><br/> 
                        </div>
                    </div>
                </div>
            </div>

            <div class="margin-fifty">
                <div class="btn-group">
                    <label class="btn btn-default [[noAggregated]]"  ng-if="showNoAgrregated('{{$id_fmx_element}}')"
                           title="{{trans('lodepart.stats-act')}}" ng-click="loadBarCharts('{{$containerURI}}',false,'{{trans('lodepart.no-data-comments')}}','{{trans('lodepart.no-data-amendments')}}')">
                        {{trans('lodepart.stats-act')}}
                    </label>
                    <label class="btn btn-default [[noAggregated]]"  ng-if="!showNoAgrregated('{{$id_fmx_element}}')"
                           title="{{trans('lodepart.stats-section')}}" ng-click="loadBarCharts('{{$containerURI}}',false,'{{trans('lodepart.no-data-comments')}}','{{trans('lodepart.no-data-amendments')}}')">
                        {{trans('lodepart.stats-section')}}
                    </label>
                    <label class="btn btn-default [[aggregated]]" ng-if="showNoAgrregated('{{$id_fmx_element}}')"
                           title="{{trans('lodepart.stats-act-section')}}" ng-click="loadBarCharts('{{$containerURI}}',true,'{{trans('lodepart.no-data-comments')}}','{{trans('lodepart.no-data-amendments')}}')">
                        {{trans('lodepart.stats-act-section')}}
                    </label>
                </div>
                &nbsp;&nbsp;
                <div class="btn-group">
                    <label class="btn btn-default [[commentsStats]]" title="{{trans('lodepart.comments-first')}}" 
                           ng-click="changeStatsType('C')">
                        {{trans('lodepart.comments-first')}}
                    </label>
                    <label class="btn btn-default [[noCommentsStats]]" title="{{trans('lodepart.amendments')}}" 
                           ng-click="changeStatsType('A')">
                        {{trans('lodepart.amendments')}}
                    </label>
                </div>

                <div class="pull-left" ng-if="dataBarChartCommentsByNationality.length == 0">
                    <i>{{trans('lodepart.stats-nothing')}}</i>
                </div>

                <a class="btn btn-default right" title="{{trans('lodepart.back-doc')}}" 
                   href="/lod/documents/displayDoc?path={{$path}}&hl={{Config::get('app.eli_lang_code')}}&lang={{Config::get('app.locale')}}">
                    {{trans('lodepart.back-doc')}}&nbsp;<i class="fa fa-lg fa-level-up" aria-hidden="true"></i>
                </a>
            </div><br />
            
            <div class="row">
                <div class="col-md-6">
                    <div class="group-donut-chart">
                        <div class="lodepart-dochierarchy donut-chart"
                            data-sparql_endpoint="{{env('VIRTUOSO.SPARQL.ENDPOINT')}}"
                            data-src="{{ url('/collection/formex-documents-json/' . $path) }}"
                            data-height="400"
                            data-width="400"
                            data-viz="circlepack"
                            data-color_min="#d74848"
                            data-color_med="#afafaf"
                            data-color_max="#8fa155"
                            data-eli_prefix="{{ '/eli/' . $doc_code . '/' }}"
                            data-options="debug"
                            ></div>
                        <br /><br /><br />
                        <div class="legend-donut-chart">
                            {{trans('lodepart.sentiments-sections')}}
                        </div>
                   </div>
                </div>
                <script>
                    function handleClick(arg) {
                        var id = arg.split("/")[2].replace("_","").toUpperCase();
                        if(arg.split("/").length < 4) {
                            id = 'ACT';
                        }
                        window.location.href = "/show-more-statistics?doc_code={{$doc_code}}&year={{$year}}&num={{$num}}&path={{$path}}&eli_lang_code={{Config::get('app.eli_lang_code')}}&id_fmx_element=" + id + "&lang={{Config::get('app.locale')}}";
                    }
                    let nodes = document.getElementsByClassName('lodepart-dochierarchy');
                    for(let node of nodes) { 
                        node.onclick_cb = handleClick; 
                    };
               </script>
               <div class="col-md-6 allChartsComments">
                    <div id="chartComments" class="group-donut-chart">
                        <svg class="stats-donut-chart"></svg>
                        <div class="legend-donut-chart">
                            {{trans('lodepart.comments-division')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                </div>
                <div class="col-md-6 allChartsAmendments">
                    <div id="chartAmendments" class="group-donut-chart">
                        <svg class="stats-donut-chart"></svg>
                        <div class="legend-donut-chart">
                            {{trans('lodepart.amendments-division')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartAmendmentsEmpty" class="group-donut-chart">
                        <svg class="stats-donut-chart"></svg>
                        <div class="legend-donut-chart">
                            {{trans('lodepart.amendments-division')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row allChartsComments">
                <div class="col-md-6">
                    <div id="chartPositiveCommentsNationality" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-comments-nationality-positive')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartNegativeCommentsNationality" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-comments-nationality-negative')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartNeutralCommentsNationality" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-comments-nationality-neutral')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div id="chartPositiveCommentsGroup" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-comments-group-positive')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartNegativeCommentsGroup" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-comments-group-negative')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartNeutralCommentsGroup" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-comments-group-neutral')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row allChartsAmendments">
                <div class="col-md-6">
                    <div id="chartInsertionsNationality" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-amendments-nationality-insertion')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartDeletionsNationality" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-amendments-nationality-deletion')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartSubstitutionsNationality" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-amendments-nationality-substitution')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div id="chartInsertionsGroup" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-amendments-group-insertion')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartDeletionsGroup" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-amendments-group-deletion')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                    <div id="chartSubstitutionsGroup" class="group-bar-chart">
                        <svg class="stats-bar-chart"></svg>
                        <div class="legend-bar-chart">
                            {{trans('lodepart.stats-amendments-group-substitution')}}<br/><br/>{{$id_fmx_element}}
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="group-map-chart">
                        <div class="lodepart-euromap"
                            data-src="{{ url('/images/europe.svg') }}"
                            data-eli="{{'/eli/'.$doc_code.'/'.$year.'/'.$num}}"
                            data-sparql_endpoint="{{env('VIRTUOSO.SPARQL.ENDPOINT')}}"
                            data-viz="nationality-score"
                            data-color_min="#d74848"
                            data-color_med="#afafaf"
                            data-color_max="#8fa155"
                            data-options="debug"
                        ></div>
                        <br /><br /><br />
                        <div class="legend-donut-chart">
                            {{trans('lodepart.sentiments-country')}}
                        </div>
                   </div>
                </div>
                <div class="col-md-6">
                    <div class="group-map-chart">
                        <div class="lodepart-euromap"
                            data-src="{{ url('/images/europe.svg') }}"
                            data-eli="{{'/eli/'.$doc_code.'/'.$year.'/'.$num}}"
                            data-sparql_endpoint="{{env('VIRTUOSO.SPARQL.ENDPOINT')}}"
                            data-viz="nationality-number"
                            data-color_min="#0099cc"
                            data-color_max="#003399"
                            data-color_med="#afafaf"
                            data-options="debug"
                        ></div>
                        <br /><br /><br />
                        <div class="legend-donut-chart">
                            {{trans('lodepart.activity-country')}}
                        </div>
                   </div>
                </div>
                <script src="{{ url('/javascripts/d3/d3graph.js') }}"></script>
            </div>
        </div>
    </div>
</div>
@stop