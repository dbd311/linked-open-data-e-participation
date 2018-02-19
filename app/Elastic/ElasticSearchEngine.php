<?php

namespace App\Elastic;

use Illuminate\Support\Facades\Log;

/**
 * ElasticSearchEngine is used for communicating with the ElasticSearch
 *
 * @author Duy Dinh
 * @date 27/04/2016
 */
class ElasticSearchEngine {

    protected $client;

    public function __construct() {

        $hosts = explode(',', env('ELASTIC_SEARCH_HOSTS'));
        $this->client = \Elasticsearch\ClientBuilder::create()->setHosts($hosts)->build();
    }

    
    /*     * *
     * Build an index using input settings
     */

    public function createIndex($settings) {
        return $this->client->indices()->create($settings);
    }

    /**
     * Index a document
     * @param json $params
     * @param json $settings
     * @return HTTP response
     */
    public function indexDocument($params) {

        $response = $this->client->index($params);
        return $response;
    }

    /**
     * Clean an index
     * @param type $params
     * @return type
     */
    public function cleanIndex($params) {
        $response = $this->client->indices()->delete($params);
        return $response;
    }

    /**
     * Clean an index by name
     * @param type $index name of the index
     * @return type
     */
    public function cleanIndexByName($index) {
        $params = ['index' => $index];
        $response = $this->client->indices()->delete($params);
        Log::info($response);
        return $response;
    }

    /**
     * Delete all indices     
     * @return type
     */
    public function deleteAllIndices() {

        // list indices
        $params = ['h' => 'i'];
        $results = $this->client->cat()->indices($params);

        // filter indices
        $indices = [];
        $fields = preg_split("/\s+/", $results, -1, PREG_SPLIT_NO_EMPTY);
        $length = sizeof($fields);
        for ($i = 0; $i < $length; $i++) {
            if (ends_with($fields[$i], '_docindex')) {
                $indices [] = $fields[$i];
            }
        }

        for ($i = 0; $i < sizeof($indices); $i++) {
            $this->cleanIndexByName($indices[$i]);
        }
    }

    /**
     * Search for documents
     * @param type $params
     */
    public function search($params) {
//        error_log($params);
        $response = $this->client->search($params);
//        error_log($response);
        return $response;
    }

    /*     * *
     * Check if an index exists
     */

    public function exists($indexName) {
        $params['index'] = $indexName;
        $val = $this->client->indices()->exists($params);
        if ($val === true || $val === "1") {
            return true;
        }
        return false;
    }

    /**
     * Get all document indices from ES
     * @return string
     */
    public function getIndices() {

        // list indices
        $params = ['h' => 'i'];
        $results = $this->client->cat()->indices($params);

        // filter indices
        $indices = [];
        $fields = preg_split("/\s+/", $results, -1, PREG_SPLIT_NO_EMPTY);
        $length = sizeof($fields);
        for ($i = 0; $i < $length; $i++) {
            if (ends_with($fields[$i], '_docindex')) {
                $indices [] = $fields[$i];
            }
        }

        sort($indices); // sort indices in asc order

        $size = sizeof($indices);
        $jsnIndicesStr = '{"indices": [';
        for ($i = 0; $i < $size - 1; $i++) {

            $eli_lang_code = explode("_", $indices[$i])[0];
            $jsnIndicesStr .= '{"eli_lang_code" : "' . $eli_lang_code . '", "name" : "' . $indices[$i] . '"}, ';
        }

        $lastAdded = false;
        if ($size > 0) {
            $eli_lang_code = explode("_", $indices[$i])[0];
            $jsnIndicesStr .= '{"eli_lang_code" : "' . $eli_lang_code . '", "name" : "' . $indices[$i] . '"}';
            $lastAdded = true;
        }

        if (!$lastAdded) {
            $jsnIndicesStr = chop($jsnIndicesStr, ', ');
        }

        $jsnIndicesStr .= ']}';

        return $jsnIndicesStr;
    }
}
