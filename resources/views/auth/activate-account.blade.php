@extends('layouts.lodepart-fancy')

@section('adaptable-area')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
			An email has been sent to '{{$email}}'.<br />
			Please check your email and click on the link for activating your account. <br />
			Thank you for your registration.
        </div>    
    </div>
</div>
@stop
