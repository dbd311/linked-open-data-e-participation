<?php

namespace App\Concepts;

/**
 * Description of Act
 *
 * @author lod
 */
class Act extends Container {

    /**     * *
     * build an ELI URI for an act
     * @param $doc_code doc type
     * @param $year year of doc
     * @param $num number of doc
     * @param $lang language of doc
     */
    public static function buil_ELI_URI_act($doc_code, $year, $num, $lang) {
        return env('SITE_NAME') . '/eli/' . $doc_code . '/' . $year . '/' . $num . '/' . $lang;
    }
    
    public static function buil_ELI_URI_generic_act($doc_code, $year, $num) {
        return env('SITE_NAME') . '/eli/' . $doc_code . '/' . $year . '/' . $num;
    }

    /**
     * build act URI
     */
    public static function buildActURI($actID, $langCode, $version) {
        return env('SITE_NAME') . '/legal_resource/id_act_' . $actID . '/' . $langCode . '/v_' . $version;
    }

    /**
     * build space URI for an act
     * @param $filename short filename
     */
    public static function buildSpaceURI($filename) {
        return env('SITE_NAME') . '/' . env('FORMEX.DOCUMENTS.PATH') . '/' . $filename;
    }

}
