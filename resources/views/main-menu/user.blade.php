@extends('layouts.lodepart-fancy')
@section('title')
<title>{{trans('lodepart.user-account')}}</title>
@stop

<?php 
    use Illuminate\Support\Facades\Session;
    if (Session::get('user') == null) {
        $log = '0';
    } else {
        $log = Session::get('user')->user_id->value;
    }
    $user = Request::get('id');
?>

@section('page-name')
<li>{{trans('lodepart.user-account')}}</li>
@stop

@section('adaptable-area')
<div id="page-content" ng-controller="userCtrl" ng-init="selectUser('{{$user}}')">
    <div ng-hide="loading" class="act center">
        <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.loading-user')}}
    </div>
    
    <div ng-show="loading" class="row">
        <div class="col-md-2 col-sm-3 col-xs-4">
            <img class="max-size" ng-src="[[user.avatar.value]]" alt="Avatar" title="[[user.user_name.value]] [[user.family_name.value]]">
            
            <div ng-show="message.updated || message.updatePassword || message.wrongPassword || message.badPassword || message.shortPassword" 
                 class="alert [[cssBox]] center transition-time">
                <span ng-show="message.updated" class="transition-time">
                    {{trans('lodepart.sucess-save')}}
                </span>
                <span ng-show="message.updatePassword" class="transition-time">
                    {{trans('lodepart.password-change')}}
                </span>
                <span ng-show="message.wrongPassword" class="transition-time">
                    {{trans('lodepart.password-wrong')}}
                </span>
                <span ng-show="message.badPassword" class="transition-time">
                    {{trans('lodepart.password-bad')}}
                </span>
                <span ng-show="message.shortPassword" class="transition-time">
                    {{trans('lodepart.password-short')}}
                </span>
            </div>
            
        </div>
        
        <div class="col-md-5 col-sm-9 col-xs-8">
            <h3>{{trans('lodepart.user-profile')}}</h3>
            <br />
            <b>{{trans('lodepart.first-name')}} :</b> 
            <span ng-if="!same('{{$log}}','{{$user}}')" ng-bind="user.user_name.value"></span>
            <span ng-if="same('{{$log}}','{{$user}}')" class="firstName" 
                  ng-bind="user.user_name.value" ng-click="editFirstName()"></span>
            <input class="editionFirstName" type="text" maxlength="35" 
                   ng-model="user.user_name.value" ng-keyup="saveFirstName($event)">
            <i class="fa fa-floppy-o buttonFirstName" aria-hidden="true" ng-click="saveFirstName($event)"></i>
            <br /><br />
            
            <b>{{trans('lodepart.last-name')}} :</b>
            <span ng-if="!same('{{$log}}','{{$user}}')" ng-bind="user.family_name.value"></span>
            <span ng-if="same('{{$log}}','{{$user}}')" class="lastName" 
                  ng-bind="user.family_name.value" ng-click="editLastName()"></span>
            <input class="editionLastName" type="text" maxlength="35" 
                   ng-model="user.family_name.value" ng-keyup="saveLastName($event)">
            <i class="fa fa-floppy-o buttonLastName" aria-hidden="true" ng-click="saveLastName($event)"></i>
            <br /><br />
            
            <b>{{trans('lodepart.organization')}} :</b> 
            <span ng-if="!same('{{$log}}','{{$user}}')" ng-bind="user.user_group.value"></span>
            <span ng-if="same('{{$log}}','{{$user}}')" class="group" 
                  ng-bind="user.user_group.value" ng-click="editGroup()"></span>
            <span ng-if="user.user_group.value == ''" ng-click="editGroup()">{{trans('lodepart.no-group')}}</span>
            <input class="editionGroup" type="text" maxlength="35" 
                   ng-model="user.user_group.value" ng-keyup="saveGroup($event)">
            <ul id="suggestions" class="typeahead group-menu" role="menu" ng-show="listGroups.length > 0">
                <li ng-repeat="group in listGroups" auto-suggest-menu-item class="">
                    <a href="" ng-bind="group" class="group-option"
                       ng-click="addGroup(group)"></a>
                </li>
            </ul>
            <i class="fa fa-floppy-o buttonGroup" aria-hidden="true" ng-click="saveGroup($event)"></i>
            <br /><br />
            
            <b>{{trans('lodepart.nationality')}} :</b> 
            <span ng-if="!same('{{$log}}','{{$user}}')" ng-bind="user.nationality.value"></span>
            <span ng-if="same('{{$log}}','{{$user}}')" class="nationality" 
                  ng-bind="user.nationality.value" ng-click="editNationality()"></span>            
            <select class="editionNationality" ng-model="user.nationality.value" ng-change="saveNationality()" ng-blur="saveNationality()">
                <option ng-repeat="nationality in listNationalities" value="[[nationality.name]]" ng-bind="nationality.name"></option>
            </select>
            <br /><br /><br />
            
            <div ng-if="same('{{$log}}','{{$user}}')">
                <i class="fa fa-pencil" aria-hidden="true"></i>
                <b>{{trans('lodepart.change-avatar')}} :</b>
                <br /><br />
                <form method="POST" action="/update-avatar?lang={{Config::get('app.locale')}}" role="form" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="{{$user}}">
                    <input type="file" name="avatar" accept="image/*">
                    <br />
                    <button type="submit" class="btn btn-primary">
                        {{trans('lodepart.save')}}
                    </button>
                </form>
            </div>
            <br /><br />
            
            <div ng-if="same('{{$log}}','{{$user}}')">
                <i class="fa fa-pencil" aria-hidden="true"></i>
                <b>{{trans('lodepart.change-password')}} :</b>
                <br /><br />
                <div class="form-horizontal">
                    <div class="form-group">
                        <div class="col-sm-6">
                            <input type="password" class="form-control" placeholder="{{trans('lodepart.password-current')}}" 
                                   ng-model="user.passwordCurrent" ng-focus="cleanBox()" ng-keyup="changePassword($event)">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <input type="password" class="form-control" placeholder="{{trans('lodepart.password-new')}}"
                                   ng-model="user.passwordNew" ng-focus="cleanBox()" ng-keyup="changePassword($event)">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <input type="password" class="form-control" ng-model="user.passwordConfirm" placeholder="{{trans('lodepart.password-confirm')}}"
                                   ng-model="user.passwordConfirm" ng-focus="cleanBox()" ng-keyup="changePassword($event)">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-sm-6">
                            <button type="submit" class="btn btn-primary" ng-click="changePassword($event)">{{trans('lodepart.save')}}</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-5 col-sm-12 col-xs-12">
            <h3>{{trans('lodepart.user-infos')}}</h3>
            <br />
            <b>{{trans('lodepart.user-role')}} : </b>
            <span ng-if="role == 'admin'">{{trans('lodepart.user-admin')}}</span>
            <span ng-if="role == 'citizen'">{{trans('lodepart.user-citizen')}}</span>
            <br /><br />
            <b>{{trans('lodepart.number-comments')}} :</b>
            <span ng-bind="nbComments"></span>
        </div>
        
    </div>
</div>
@stop