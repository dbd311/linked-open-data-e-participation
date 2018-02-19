@extends('layouts.lodepart-fancy')

@section('title')
<title>{{trans('lodepart.registration')}}</title>
@stop

@section('page-name')
<li><a title="{{trans('lodepart.register')}}" href="/">{{trans('lodepart.register')}}</a></li>
@stop


@section('adaptable-area')

<div id="page-content">
    <div class="container-fluid">
        <div class="col-md-10 col-md-offset-1">
            <div class="panel panel-default">
                <div class="panel-body">
                    @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        {{trans('lodepart.verify')}}<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error}}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                    <form method="POST" action="/auth/register?lang={{Config::get('app.locale')}}" class="form-horizontal" role="form" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="{{ csrf_token()}}">

                        <div class="form-group">
                            <label class="col-md-2 control-label">{{trans('lodepart.first-name')}}</label>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="first_name" value="{{ old('first_name')}}" maxlength="35">
                            </div>
                            <label class="col-md-2 control-label">{{trans('lodepart.last-name')}}</label>
                            <div class="col-md-3">
                                <input type="text" class="form-control" name="last_name" value="{{ old('last_name')}}" maxlength="35">
                            </div>

                        </div>

                        <div class="form-group">

                            <label class="col-md-2 control-label">{{trans('lodepart.mail')}}</label>
                            <div class="col-md-3">
                                <input type="email" class="form-control" name="email" value="{{ old('email')}}" maxlength="60">
                            </div>
                            <label class="col-md-2 control-label">{{trans('lodepart.avatar')}}</label>
                            <div class="col-md-3">
                                <input type="file" name="avatar" accept="image/*">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="col-md-2 control-label">{{trans('lodepart.password')}}</label>
                            <div class="col-md-3">
                                <input type="password" class="form-control" name="password" maxlength="20">
                            </div>
                            <label class="col-md-2 control-label">{{trans('lodepart.confirm-password')}}</label>
                            <div class="col-md-3">
                                <input type="password" class="form-control" name="password_confirmation" maxlength="25">
                            </div>
                        </div>

                        <div class="form-group" ng-controller="registerCtrl" ng-init="loadGroups()">
                            <label class="col-md-2 control-label">{{trans('lodepart.organization')}}</label>
                            <div class="col-md-3 keywords-search">
                                <input if="userGroup" type="text" ng-model="userGroup" ng-keyup="getSuggestions($event)" ng-focus="getSuggestions($event)" autocomplete="off" class="form-control group-input" name="group" value="{{ old('group')}}" maxlength="30">
                                <ul id="suggestions" class="typeahead group-menu" role="menu" ng-show="listGroups.length > 0">
                                    <li ng-repeat="group in listGroups" auto-suggest-menu-item class="">
                                        <a href="" ng-bind="group" class="group-option"
                                           ng-click="addGroup(group)"></a>
                                    </li>
                                </ul> 
                            </div>
                            <label class="col-md-2 control-label">{{trans('lodepart.nationality')}}</label>
                            <div class="col-md-3" ng-init="loadNationalities()">
                                <select ng-model="selectNationality" name="nationality">
                                    <option ng-repeat="nationality in listNationalities" value="[[nationality.name]]" ng-bind="nationality.name"></option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-5" ng-init="currentRegistration = false">
                                <button ng-hide="currentRegistration" type="submit" class="btn btn-primary" ng-click="currentRegistration = true">
                                    {{trans('lodepart.registration')}}
                                </button>
                                <button ng-hide="currentRegistration" class="btn btn-primary" onclick="location.href = '/?lang={{Config::get('app.locale')}}'">
                                    {{trans('lodepart.cancel')}}
                                </button>
                                <div ng-show="currentRegistration" class="act">
                                    <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.loading-registration')}}
                                </div>
                            </div>
                        </div>
						
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
