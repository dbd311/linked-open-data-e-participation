<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

/**
 * To configure naviguation web page
 * @author Duy Dinh
 * @date 22 September 2016
 */
class AdminController extends Controller {

    /**
     * Load admin page
     */
    public function admin(Request $request) {
        self::updateLocale($request->get('lang'));
        $user = Session::get('user');
        if ($user != null) {
            $fields = explode('/', $user->role->value);
            $role = end($fields);

            if ($role == 'admin') {
                return view('admin.index');
            } else {
                return view('errors.forbidden');
            }
        } else {
            return view('home');
        }
    }

    public function showGlobalConfig() {
        return view('admin.config.management');
    }

    public function loadEnvironment() {
        $params = '{"params" : ['
                . '  {"name" : "APP_ENV",     "value" : "' . env('APP_ENV') .
                '"}, {"name" : "APP_KEY", "value" : "' . env('APP_KEY') .
                '"}, {"name" : "APP_DEBUG", "value" : "' . env('APP_DEBUG') .
                '"}, {"name" : "MAIL_DRIVER", "value" : "' . env('MAIL_DRIVER') .
                '"}, {"name" : "MAIL_HOST",   "value" : "' . env('MAIL_HOST') .
                '"},  {"name" : "SEND_MAIL",   "value" : "' . env('SEND_MAIL') .
                '"},  {"name" : "VIRTUOSO.SPARQL.ENDPOINT",   "value" : "' . env('VIRTUOSO.SPARQL.ENDPOINT') .
                '"},  {"name" : "VIRTUOSO.SPARQL.ENDPOINT_SRV",   "value" : "' . env('VIRTUOSO.SPARQL.ENDPOINT_SRV') .
                '"},  {"name" : "VIRTUOSO.SPARQL.ENDPOINT_SRV_AUTH",   "value" : "' . env('VIRTUOSO.SPARQL.ENDPOINT_SRV_AUTH') .
                '"},  {"name" : "SITE_NAME",   "value" : "' . env('SITE_NAME') .
                '"},  {"name" : "LOD_GRAPH",   "value" : "' . env('LOD_GRAPH') .
                '"},  {"name" : "ELASTIC_SEARCH_HOSTS",   "value" : "' . env('ELASTIC_SEARCH_HOSTS') .
                '"},  {"name" : "INDEX_TYPE",   "value" : "' . env('INDEX_TYPE') .
                '"},  {"name" : "FORMEX.DOCUMENTS.JSON.PATH",   "value" : "' . env('FORMEX.DOCUMENTS.JSON.PATH') .
                '"},  {"name" : "CALLBACK",   "value" : "' . env('CALLBACK') .
                '"},  {"name" : "ERRORCALLBACK",   "value" : "' . env('ERRORCALLBACK') .
                '"}]}';

//        $content = file_get_contents(public_path() . '/.env');

        return $params;
    }

    /**
     * Load the language interface and update locale
     */
    public static function updateLocale($lang_code) {
        $lang = Config::get('app.locale');
        if (isset($lang_code)) {
            $lang = $lang_code;
        }
        App::setLocale($lang);
    }

}
