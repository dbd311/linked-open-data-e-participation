@extends('layouts.lodepart-fancy')

@section('adaptable-area')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <?php
                if ($token == null){
                    echo "<br/><p>" . trans('lodepart.impossible-reset-password') . "</p>";
                } else {
            ?>
            <br />
            <div class="panel panel-default">
                <div class="panel-heading">{{trans('lodepart.reset-your-password')}}</div>
                <div class="panel-body">
                    <form METHOD="POST" action="/reset/password">
                        {!! csrf_field() !!}
                        <input type="hidden" name="token" value="{{ $token }}">
                        @if (count($errors) > 0)
                            <ul>
                                @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{trans('lodepart.mail')}}</label>
                            <div class="col-md-8">
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                        <br /><br /><br />
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{trans('lodepart.new-password')}}</label>
                            <div class="col-md-8">
                                <input type="password" name="password" class="form-control">
                            </div>
                        </div>
                        <br /><br />
                        <div class="form-group">
                            <label class="col-md-3 control-label">{{trans('lodepart.confirm-password')}}</label>
                            <div class="col-md-8">
                                <input type="password" name="password_confirmation" class="form-control">
                            </div>
                        </div>
                        <br /><br />
                        <div class="form-group">
                            <div class="col-md-2 col-md-offset-3">
                                <button type="submit" class="btn btn-primary">{{trans('lodepart.reset-password')}}</button>                          
                            </div>
                        </div>
                        <input type="hidden" name="lang" value="{{Config::get('app.locale')}}">
                    </form>
                </div>
            </div>
            <?php
                }
            ?>
        </div>
    </div>
</div>
@stop