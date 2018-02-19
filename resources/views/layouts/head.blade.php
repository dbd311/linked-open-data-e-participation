<head>
    @section('title')
    <title>{{ trans('lodepart.title')}} </title>
    @show
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <?php header("Cache-Control:no-cache"); ?>

    @section('css')
    <link rel="stylesheet" href="/css/lodepart.css" />
    <link rel="stylesheet" href="/css/bundle.css" />
    <link rel="stylesheet" href="/css/d3graph.css" />
    <link rel="stylesheet" href="/css/jquery/jquery-ui.min.css">
    <link rel="stylesheet" href="{{ url('/javascripts/bower_components/angular-material/angular-material.min.css') }}"/>
    @show

    @section('css-local')

    @show

    @section('javascript')

    <script src="{{ url('/javascripts/jquery/jquery.min.js') }}"></script>
    <script src="{{ url('/javascripts/jquery/jquery-ui.min.js') }}"></script> 
    <script src="{{ url('/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ url('/javascripts/ckeditor/ckeditor.js') }}"></script>
    <script src="{{ url('/javascripts/jsdiff/jsdiff.js') }}"></script>
    <script src="{{ url('/javascripts/typeahead/bootstrap3-typeahead.min.js') }}"></script>
    
    <script src="{{ url('/javascripts/utils/go-top/move-top.js') }}"></script>
    <script src="{{ url('/javascripts/utils/go-top/easing.js') }}"></script>
    
    <script src="{{ url('/javascripts/bower_components/angular/angular.js') }}"></script> 
    <script src="{{ url('/javascripts/bower_components/angular-material/angular-material.min.js') }}"></script>
    <script src="{{ url('/javascripts/bower_components/angular-animate/angular-animate.min.js') }}"></script>
    <script src="{{ url('/javascripts/bower_components/angular-aria/angular-aria.min.js') }}"></script>
    <script src="{{ url('/javascripts/bower_components/angular-sanitize/angular-sanitize.min.js') }}"></script>
    
    <script src="{{ url('/javascripts/bower_components/react/react.js') }}"></script>
    <script src="{{ url('/javascripts/bower_components/react/react-dom.js') }}"></script>
    <script src="{{ url('/javascripts/bower_components/ngReact/ngReact.min.js') }}"></script>
    
    <script src="{{ url('/javascripts/d3/d3.js') }}"></script>
    <script src="{{ url('/javascripts/d3/angularjs-nvd3-directives.min.js') }}"></script>
    <script src="{{ url('/javascripts/d3/nv.d3.js') }}"></script>
    
    <script src="{{ url('/javascripts/angularjs/lodepartApp.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/configController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/searchController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/documentController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/statisticsController.js') }}"></script>   
    <script src="{{ url('/javascripts/angularjs/controllers/moreStatisticsController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/registerController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/adminController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/userController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/adminService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/configService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/searchService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/documentService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/commentService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/statisticsService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/statisticsAmendmentsService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/userService.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/filters/htmlFilter.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/filters/removeHtmlFilter.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/filters/reverseFilter.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/filters/startFrom.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/directives/ckeditor.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/directives/trustedHtml.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/directives/compile.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/controllers/elasticSearchController.js') }}"></script>
    <script src="{{ url('/javascripts/angularjs/services/elasticSearchService.js') }}"></script>

    <script>
        $(function () {
            $("#datepickerFrom").datepicker({dateFormat: 'yy-mm-dd'});
            $("#datepickerFrom").css("background-color", "white");
            $("#datepickerTo").datepicker({dateFormat: 'yy-mm-dd'});
            $("#datepickerTo").css("background-color", "white");
        });
    </script>
    @show
    
    @section('javascript-local')

    @show
</head>
