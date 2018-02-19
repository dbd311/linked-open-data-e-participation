<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Http\Display;

use App\Eloquent\Language;

class Page {

    /**
     * Language chooser toolbar
     * @param $container current container
     * @param $eli_lang_code lang code of current container
     * @return type
     */
    public static function buildCommentLanguageChooser($container, $eli_lang_code) {
        $languages = Language::all();

        $langBar = '<ul class="list-inline"><li><label>Comments language:</label></li>';
        
        foreach ($languages as $language) {
            
            if ($language->code3 == $eli_lang_code) {
                $str = sprintf('<li><a id="lang_%s" title="%s" class="lang-selected">%s</a></li>', $language->code, $language->name, strtoupper($language->code3));
            } else {
                $str = sprintf('<li><a id="lang_%s" 
                    onclick="selectLanguage(\'lang_%s\', \'%s\', \'%s\');" title="%s">%s</a></li>', $language->code, $language->code, $language->code, $container, $language->name, strtoupper($language->code3));
            }
            $langBar .= $str;
        }
        return $langBar . '</ul>';
    }

}
