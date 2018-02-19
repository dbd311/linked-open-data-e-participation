<!DOCTYPE html>
<html>
    @include('layouts.head')
    <body>
        <div id="wrapper">
            @section('main-content')
            <div id="main-content" ng-app="lodepartApp">

                @include('layouts.header')
                @section('adaptable-area')
                <!-- caroussel -->
<!--                <div class="container-fluid">
                    <div class="row lod-carousel">
                        <div class="col-lg-4">
                            <div class="panel" ng-controller="searchCtrl" ng-init="findMostPopularThisWeek()">
                                <div class="panel-heading">{{ trans('lodepart.popular-this-week')}}</div>
                                <div class="panel-body popular-title">
                                    <a href="/lod/documents/displayDoc?path=[[popularDoc.path]]&hl={{Config::get('app.eli_lang_code')}}&lang={{Config::get('app.locale')}}"
                                       title="[[popularDoc.title_popuplar_this_week]]">
                                        <span ng-bind-html="popularDoc.short_title_popuplar_this_week | htmlFilter"></span>
                                    </a>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <span class="right number-of-comments"><i class="fa fa-comments-o"> </i></span> 
                                        <span class="number-of-comments" ng-bind="popularDoc.numberOfComments"></span> 
                                    </div>
                                    <div class="col-md-2">
                                        <nvd3-pie-chart
                                            data="popularDoc.dataPieChart"
                                            x="xFunction(popularDoc.dataPieChart)"
                                            y="yFunction(popularDoc.dataPieChart)"
                                            color="colorFunction()"
                                            showLabels="true"
                                            pieLabelsOutside="false"
                                            tooltips="true"
                                            tooltipcontent="toolTipContentFunction()"
                                            labelType="percent"
                                            width="250"
                                            height="240">           
                                            <svg class="pie-chart-popular"></svg>
                                        </nvd3-pie-chart>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="panel">
                                <div class="panel-heading">{{ trans('lodepart.what-is-e-participation')}}</div>
                                <div class="panel-body text-center">
                                    <a href="{{ url('/what-is-epart?lang='.Config::get('app.locale')) }}">
                                        <img src="{{ url('/images/carousel/e-participation-graphic.png')}}"
                                             class="img-rounded center-block" alt="{{ trans('lodepart.what-is-e-participation')}}">
                                    </a>
                                </div>                               
                            </div>
                        </div>  
                        <div class="col-lg-4">
                            <div class="panel">
                                <div class="panel-heading">{{ trans('lodepart.what-is-linked-open-data')}}</div>
                                <div class="panel-body">
                                    <a href="{{ url('/what-is-lod?lang='.Config::get('app.locale')) }}">
                                        <img src="{{ url('/images/carousel/lod-graphic.png')}}"
                                             class="img-rounded center-block" alt="{{ trans('lodepart.what-is-linked-open-data')}}">
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>-->
                <!-- end caroussel -->

                <!-- result list -->
                <div class="container-fluid" id="page-content">
                    <div class="row" ng-controller="searchCtrl">
                        @include('includes.search-panel')
                    </div>
                </div>
                <!-- end result list -->
                @show

                @include('layouts.footer')
            </div>
            @show
        </div>         
        @include('includes.go-top')       
    </body>
</html>
