<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Indexer;

use App\Documents\CELLARDoc;

/**
 * Description of Indexer
 *
 * @author Duy Dinh <duy.dinh@tudor.lu>
 */
class Indexer extends \App\Http\Settings {

    public function __construct() {
        parent::__construct();

        // generate index handlers

        $this->makeIndexStructures();
    }

    public function DeleteAndMakeIndex() {
        $this->deleteIndexStructures();

        $this->makeIndexStructures();
    }

    public function deleteIndexStructures() {
        `curl -XDELETE '$this->INDEXING_SERVICE_HOST/lod/documents'`;
    }

    public function makeIndexStructures() {
        `curl -XPUT '$this->INDEXING_SERVICE_HOST/lod/documents'`;
    }

    /**
     * Process ALL stands
     * @param type $jsonStands
     */
    public function indexDocuments($docs) {

        $counter = 1;
        foreach ($docs as $doc) {
            if (!empty($doc['title'])) {
                $docID = CELLARDoc::extractDocIDfromURI($doc['item']['value']);
            }
            $message = $this->indexDocument($doc, $docID);
            $counter = $counter + 1;
        }
    }

    /**
     * Index a stand
     * @param type $stand
     * @return a JSON message
     */
    public function indexDocument($doc, $docID) {
        $jsonDoc = str_replace("'", "", json_encode($doc));
        $message = `curl -XPUT '$this->documentHandler/$docID' -d '$jsonDoc'`;
//        echo $message;
        return json_decode($message);
    }

}
