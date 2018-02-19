<head>
    @section('title')
    <title>{{ trans('lodepart.title')}} </title>
    @show
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

    @section('css')
    <link rel="stylesheet" href="/css/lodepart.css" />
    <link rel="stylesheet" href="/css/jquery/jquery-ui.min.css">
    <link rel="stylesheet" href="{{ url('/javascripts/bower_components/angular-material/angular-material.min.css') }}"/>
    @show

    @section('css-local')

    @show

    @section('javascript')

    <script src="{{ url('/js/search.js') }}"></script>

    @show

    @section('javascript-local')

    @show
</head>
