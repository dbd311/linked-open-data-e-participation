<?php

namespace App\Concepts;

/**
 * Description of Paragraph
 *
 * @author lod
 */
class Paragraph extends Container {
    /*     * *
     * build an ELI URI for an paragraph
     */

    public static function buil_ELI_URI_paragraph($type_doc, $year, $num, $article, $paragraph, $lang) {
        return env('SITE_NAME') . '/eli/' . $type_doc . '/' . $year . '/' . $num . '/art_' . $article . '/par_' . $paragraph . '/' . $lang;
    }

    /**
     * build paragraph URI
     */
    public static function buildParagraphURI($actID, $artID, $paragraphID, $langCode, $version) {
        return env('SITE_NAME') . '/legal_resource/id_act_' . $actID . '/' . $langCode . '/v_' . $version . '/id_article_' . $artID . '/id_paragraph_' . $paragraphID;
    }

}
