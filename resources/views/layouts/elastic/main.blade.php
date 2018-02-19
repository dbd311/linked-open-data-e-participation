<!DOCTYPE html>
<html>
    @include('layouts.head')
    <body>
        <div id="wrapper">
            @section('main-content')
            <div id="main-content" ng-app="lodepartApp">

                @include('layouts.header')
                @section('adaptable-area')            

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
