<form method="GET" action="/auth/logout">
    {!! csrf_field() !!}
    <button type="submit">{{trans('lodepart.logout')}}</button>
</form>

