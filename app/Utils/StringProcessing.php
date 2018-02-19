<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Utils;

/**
 * Description of StringProcessing
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 01 September 2015
 */
class StringProcessing {

    public static function normalizeTitle($title) {
        
        // add a slash before the 'double quote' 
        $title1 = preg_replace('/"/', '\"', trim($title));
        // remove white space, tab or new line characters
        return preg_replace('/\s+/', ' ', $title1);
        
    }
    
    public static function extractPlainText($text){
        return strip_tags($text);
    }

}
