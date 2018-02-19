<?php

namespace App\Http;

/**
 * General Settings for the LODEPART
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 13/08/2015
 */
class Settings {

    // basic auth, proxy settings ...
   //protected $context;
    // EDF main host
    // Elastic search settings:  ---------------------------------------
    // indexing and search services URIs
    protected $INDEXING_SERVICE_HOST = "http://localhost:9200";
    protected $SEARCH_SERVICE_HOST = "http://localhost:9200";
    
    protected $documentHandler;

    protected $SITE_NAME = "lodepart";
    
    function __construct() {
        // index and search handlers
        $this->INDEXING_SERVICE_HOST = env('INDEXING_SERVICE_HOST');
        $this->SEARCH_SERVICE_HOST = env('SEARCH_SERVICE_HOST');
        $this->documentHandler = $this->INDEXING_SERVICE_HOST . "/lod/documents";
        
        $this->SITE_NAME = env('SITE_NAME');
    }

    public function getContext() {
        return $this->context;
    }

}
