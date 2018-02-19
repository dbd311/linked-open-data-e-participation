<!-- Header -->
<div id="header">
    <div id="header-container">
        <div class="row">
            <div class="col-md-5 logo-wrapper">
                <div id="logo">
                    <span class="lod-logo hand" onclick="location.href='{{ url('/?lang='.Config::get('app.locale'))}}';"></span>
                </div>
            </div>
            <div class="col-md-7" ng-controller="configCtrl" ng-init="initialize('{{Config::get('app.eli_lang_code')}}', '{{Config::get('app.locale')}}', 
                 '{{env('VIRTUOSO.SPARQL.ENDPOINT')}}', '{{env('SITE_NAME')}}', '{{Config::get('app.cellar_sparql_endpoint')}}')" ng-cloak>
                <div id="header-menu">
                    <ul class="lod-nav-links">
                        <li><a title="{{trans('lodepart.about')}}" href="{{ url('/about?lang='.Config::get('app.locale'))}}">{{trans('lodepart.about')}}</a></li>
                        <li><a title="{{trans('lodepart.what-is-e-participation-menu')}}" href="{{ url('/what-is-epart?lang='.Config::get('app.locale')) }}">{{trans('lodepart.what-is-e-participation-menu')}}</a></li>
                        <li><a title="{{trans('lodepart.what-is-linked-open-data-menu')}}" href="{{ url('/what-is-lod?lang='.Config::get('app.locale')) }}">{{trans('lodepart.what-is-linked-open-data-menu')}}</a></li>
                        <li><a title="{{trans('lodepart.faqs')}}" href="{{ url('/faqs?lang='.Config::get('app.locale'))}}">{{trans('lodepart.faqs')}}</a></li>
                        <li><a title="{{trans('lodepart.legal-notice')}} notice" href="{{ url('/legal-notice?lang='.Config::get('app.locale'))}}">{{trans('lodepart.legal-notice')}}</a></li>
                        <li><a title="{{trans('lodepart.contact')}}" href="{{ url('/contact?lang='.Config::get('app.locale'))}}">{{trans('lodepart.contact')}}</a></li>
                        <li>
                            <select class="form-control" ng-model="config.lang" ng-change="changeLang()">
                                <option ng-repeat="language in config.listLanguages" value="[[language.code]]" ng-bind="language.name"></option>
                            </select>
                        </li>                    
                    </ul>
                </div>
                @section('search-header')
                <div class="lod-search pull-right">
                    <form autocomplete="off" method="GET" action="/search">
                        <div class="input-group">
                            <span class="input-group-addon">
                                <button class="search-es" type="submit">
                                    <i class="fa fa-search" aria-hidden="true" title="{{trans('lodepart.search')}}"></i>
                                </button>
                            </span>
                            <input type="text" class="form-control" placeholder="{{trans('lodepart.search')}}" name="q" maxlength="200"
                                    style="border-top-right-radius:4px;border-bottom-right-radius:4px;">
                            <input type="hidden" value="{{Config::get('app.locale')}}" name="lang">                                                
                        </div>
                    </form>
                </div>
                @show
            </div>
        </div>
    </div>

    <div class="lod-breadcrumb"> 

        <div id="breadcrumb-left">                            
            <ul>
                <li><a href="{{ url('/?lang='.Config::get('app.locale'))}}">{{trans('lodepart.e-part-lod')}}</a></li>
                @section('page-name')
                @show
            </ul>
        </div>
        <div id="breadcrumb-right">            
            <?php use Illuminate\Support\Facades\Session;
                if (Session::get('user') == null) {
                    echo "<a href=\"" . url('/auth/login?lang=' . Config::get('app.locale')) . "\">" . trans('lodepart.login') . " | <a href=\"" . url('/auth/register?lang=' . Config::get('app.locale')) . "\">" . trans('lodepart.register') . "</a><i class=\"hidden\" id=\"user_not_login\"></i>";
                } else {
                    echo "<a id=\"myProfile\" role=\"button\" tabindex=\"0\" data-toggle=\"popover\" data-trigger=\"focus\">" . Session::get('user')->user_name->value . " " . Session::get('user')->family_name->value . "</a>";
                }
            ?>
        </div>
    </div>

    <div id="account-info" class="hidden">
        <ul id="menu-account-info" class="nav">
            <?php
                if (Session::get('user') != null){
                    echo "<li><a title=\"" . trans('lodepart.my-account') . "\" href=\"" . url('/dashboard/espace-user?id=' . Session::get('user')->user_id->value . '&lang=' . Config::get('app.locale')) . "\">" . trans('lodepart.my-account') . "</a></li>";
                }            
                if (Session::get('user') != null && substr(Session::get('user')->role->value, sizeof(Session::get('user')->role->value)-6) == 'admin') {
                    echo "<li><a title=\"" . trans('lodepart.admin') . "\" href=\"" . url('/dashboard/espace-admin?lang=' . Config::get('app.locale')) . "\">" . trans('lodepart.admin') . "</a></li>";
                }
            ?>
            <li class="nav-divider"></li>
            <li><a title="{{trans('lodepart.logout')}}" href="{{ url('/auth/logout?lang='.Config::get('app.locale'))}}">{{trans('lodepart.logout')}}</a></li>
        </ul>
    </div>
</div>
