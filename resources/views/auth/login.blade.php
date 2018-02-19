@extends('layouts.lodepart-fancy')

@section('page-name')
<li><a title="{{trans('lodepart.login')}}" href="/">{{trans('lodepart.login')}}</a></li>
@stop

@section('adaptable-area')
<div class="container">
    <br />
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-body">
                    @if (count($errors) > 0)
                    <div class="alert alert-danger">
                        {{trans('lodepart.verify')}}<br><br>
                        <ul>
                            @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <?php
                    $loginAttempts = Session::get('loginAttempts');
                    Session::set('loginAttempts', $loginAttempts + 1);
                    ?>
                    @endif
                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/auth/login') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('email') ? ' has-error' : '' }}">
                            <label for="email" class="col-md-4 control-label">{{trans('lodepart.email')}}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control" name="email" value="{{ old('email') }}">

                                @if ($errors->has('email'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('email') }}</strong>
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                            <label for="password" class="col-md-4 control-label">{{trans('lodepart.password')}}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control" name="password">

                                @if ($errors->has('password'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('password') }}</strong>
                                </span>
                                @endif
                            </div>
                        </div>
                        <input type="hidden" name="lang" value="{{Config::get('app.locale')}}" /> 
                        <div class="form-group">
                            <div class="col-md-6 col-md-offset-4" ng-init="currentLogin = false">
                                <button ng-hide="currentLogin" type="submit" class="btn btn-primary" ng-click="currentLogin = true">
                                    <i class="fa fa-btn fa-sign-in"></i> {{trans('lodepart.login')}}
                                </button>
                                <a ng-hide="currentLogin" class="btn btn-link" href="{{ url('/password/email?lang='.Config::get('app.locale')) }}">{{trans('lodepart.forgot-password')}}</a>
                                <div ng-show="currentLogin" class="act center">
                                    <i class="fa fa-refresh fa-spin fa-lg fa-fw"></i> {{trans('lodepart.loading-login')}}
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
