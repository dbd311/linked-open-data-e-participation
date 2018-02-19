<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Concepts;

class Themes {

    /**
     * Create a filter for themes
     * @param type $theme_list list of themes that the user selects from the web page
     * @param type $eli_lang_code language code of the text (eli)
     * @return string
     */
    public static function create_filter_themes($theme_list, $eli_lang_code) {
        if (empty($theme_list) || $theme_list == '') {
            return 'FILTER(LANG(?theme)="' . $eli_lang_code . '")';
        }
        $themes = preg_split('/[;]+/', $theme_list, -1, PREG_SPLIT_NO_EMPTY);
        $filter = 'FILTER(LANG(?theme)="' . $eli_lang_code . '") FILTER (';
        for ($i = 0; $i < sizeof($themes) - 1; $i++) {
            $theme = trim($themes[$i]);
            if ($theme !== '') {
                $filter .= 'contains(lcase(?theme), lcase("' . trim($themes[$i]) . '")) || ';
            }
        }
        if (sizeof($themes) > 0) {
            $theme = trim($themes[$i]);
//            error_log(sizeof($theme));

            $filter .= 'contains(lcase(?theme), lcase("' . trim($themes[$i]) . '")))';
        }
        return $filter;
    }

}
