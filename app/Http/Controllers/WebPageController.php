<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use App\Eloquent\Language;
use Illuminate\Support\Facades\Session;

/**
 * To configure naviguation web page
 * @author Vivien Touly
 * @date 29 June 2016
 */
class WebPageController extends Controller {
    
     /**
     * Load the languages
     */
    public function loadLanguages(){
        $languages = MetadataController::get_list_all_langues();
        
        $newListLanguages = array();
        if (!empty($languages)) {
            foreach ($languages->results->bindings as $lang) {
                $language = new Language();
                $language->code3 = strtolower($lang->eli_lang_code->value);
                $language->code = $lang->lang_code->value;
                $language->name = $lang->lang_name->value . ' (' . $lang->lang_code->value . ')';
                array_push($newListLanguages,$language);
            }
        }
        return json_encode($newListLanguages);
    }

    /**
     * Load the language interface and update locale
     */
    public static function updateLocale($lang_code){
        $lang = Config::get('app.locale');
        if(isset($lang_code)){
            $lang = $lang_code;
        }
        App::setLocale($lang);
    }
    
    /**
     * Change lang and load the home page
     * @param Request $request
     * @return type view home
     */
    public function home(Request $request){
        self::updateLocale($request->get('lang'));
        return view('home');
    }
    
    /**
     * About page
     */
    public function about(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.about');
    }
    
    /**
     * FAQs page
     */
    public function faqs(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.faqs');
    }
    
    /**
     * Contact page
     */
    public function contact(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.contact');
    }
    
    /**
     * Legal notice page
     */
    public function legalNotice(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.legal-notice');
    }
    
    /**
     * Sitemap page
     */
    public function sitemap(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.sitemap');
    }
    
    /**
     * What is epart page
     */
    public function whatIsEpart(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.what-is-epart');
    }
    
    /**
     * What is lodepart page
     */
    public function whatIsLod(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.what-is-lod');
    }
    
    /**
     * Load admin page
     */
    public function admin(Request $request){
        self::updateLocale($request->get('lang'));
        $user = Session::get('user');
        if ($user != null) {
            if (substr(Session::get('user')->role->value, sizeof(Session::get('user')->role->value)-6) == 'admin') {
                return view('admin.index');
            } else {
                return view('errors.forbidden');
            }
        } else {
            return view('home');
        }
    }
    
    /**
     * Load user page
     */
    public function user(Request $request){
        self::updateLocale($request->get('lang'));
        return view('main-menu.user');
    }
}
