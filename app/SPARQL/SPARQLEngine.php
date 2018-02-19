<?php

namespace App\SPARQL;

/**
 * SPARQLEngine is used for communicating with the SPARQL endpoint via Facade SPARQL
 * Using GuzzleHttp\guzzle 6.2.*
 * @author Duy Dinh
 * @date 19/07/2016
 */
class SPARQLEngine {

    protected $client;

    public function __construct() {
        $this->client = new \GuzzleHttp\Client();
    }

    /**
     * Run a SPARQL query
     * @param type $query
     * @return JSON results
     */
    public function runSPARQLQuery($query) {
        
        $response = $this->client->request('GET', env('VIRTUOSO.SPARQL.ENDPOINT_SRV') . '?format=json&query=' . urlencode($query));
        return $response->getBody();
    }

    /*     * *
     * Run a secure SPARQL query via Digest Authentication and return JSON results.
     * By default, the secure SPARQL endpoint is configured in .env with _AUTH
     */

    public function runSPARQLUpdateQuery($query) {

         if (strlen((urlencode($query))) <= 8192) {
            $response = $this->client->get(env('VIRTUOSO.SPARQL.ENDPOINT_SRV_AUTH') . '?format=json&query=' . urlencode($query), ['auth' => [env('VIRTUOSO.USERNAME'), env('VIRTUOSO.PASSWORD'), 'digest']]);
            return $response->getBody();
        } else {
            error_log($query);
            error_log('!!! The query is longer than 8KB .....');
        }

        return null;
    }

    /*     * *
     * Run a SPARQL query and return JSON results from a SPARQL endpoint
     */

    public function retrieveLinkedData($endpoint, $query) {
        $response = $this->client->request('GET', $endpoint . '?format=json&query=' . urlencode($query));
        return $response->getBody();
    }
}
