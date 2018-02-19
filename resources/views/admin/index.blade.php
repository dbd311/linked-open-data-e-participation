@extends('layouts.lodepart-fancy')

@section('title')
<title>{{trans('lodepart.admin-interface')}}</title>
@stop

@section('adaptable-area')
<div id="page-content" ng-controller="adminCtrl" >
    <div class="container" ng-init="loadAdmin()">
        <h4>{{trans('lodepart.admin-elastic-search')}}</h4>
        <ul>
            <li>
                <a href='/elastic/document-indexing'>
                    {{trans('lodepart.admin-build-doc')}}
                </a>
            </li>
            <li>
                <a href='/elastic/clean-document-index'>
                    {{trans('lodepart.admin-clean-doc')}}
                </a>
            </li>
        </ul>
        <br />

        <h4>{{trans('lodepart.admin-semantic')}}</h4>
        <h5>{{trans('lodepart.admin-doc-tasks')}}</h5>

        <ul>
            <li ng-if="!showDeleteDoc">
                <a href='/virtuoso/populate-json-documents' title='{{trans('lodepart.admin-load-docs-info')}}'>
                    {{trans('lodepart.admin-load-docs')}}
                </a>
            </li>
            <li ng-if="showDeleteDoc">
                <a href='/virtuoso/delete-documents' title='{{trans('lodepart.admin-delete-docs-info')}}'>
                    {{trans('lodepart.admin-delete-docs')}}
                </a>
            </li>            
        </ul>

        <h5>{{trans('lodepart.admin-ontology')}}</h5>

        <ul>
            <li ng-if="showLoadEurovoc">
                <a href="{{ url('/lod/dashboard/load-eurovoc?lang='.Config::get('app.locale'))}}">
                    {{trans('lodepart.admin-load-eurovoc')}}
                </a>
            </li>
            <li ng-if="showLoadLang">
                <a href="{{ url('/lod/dashboard/load-languages?lang='.Config::get('app.locale'))}}">
                    {{trans('lodepart.admin-load-lang')}}
                </a>
            </li>
        </ul>

        <h4>{{trans('lodepart.translation')}}</h4>

        <ul>
            <li ng-if="showUpdateTranslation === 'true'">
                <a href="{{ url('/update-translation?lang='.Config::get('app.locale'))}}">
                    {{trans('lodepart.update-translation')}}
                </a>
            </li>
            <li ng-if="showUpdateTranslation === 'false'">
                {{trans('lodepart.ok-translation')}}
            </li>
        </ul>

        <h4><a href='/cpanel/management' title='Configration'>{{trans('keywords.configuration')}}</a></h4>        

    </div>
</div>

@stop