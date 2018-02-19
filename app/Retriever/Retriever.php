<?php

/*
 * Retrieving stands, talks and user profiles from the Raw Data server.
 * Processing data (e.g. extracting social network data) and put them into Elastic index
 */

namespace App\Retriever;

use Illuminate\Support\Facades\Config;
use App\Http\Display\Zebra_Pagination;
use SPARQL;

/**
 * Description of Retriever
 *
 * @author Duy Dinh <duy.dinh@tudor.lu>
 */
class Retriever extends \App\Http\Settings {

    protected $counter;
    protected $indexer;
    // Hash tables to combine themes into a single result
    protected $themeHash;
    protected $procedureHash;
    protected $genericActHash;
    protected $titleHash;
    protected $dateHash;
    protected $pagination;
    protected $records_per_page = 10;
    /*public static $RECENT_PUBLISHED_DOCS_SPARQL_QUERY = 'DEFINE input:inference "cdm_rule_set" \
            PREFIX cdm: <http://publications.europa.eu/ontology/cdm#> 
            SELECT ?title ?item ?date \
            WHERE { \
                ?article a cdm:proposal_act; \
                        cdm:resource_legal_id_celex ?id; \
                        cdm:work_date_document ?date; \
                        cdm:work_has_expression ?exp. \
                ?exp    cdm:expression_uses_language ?lang; \
                        cdm:expression_title ?title; \
                        cdm:expression_manifested_by_manifestation ?manifestation.  \
                 ?manifestation cdm:manifestation_type \"html\"^^xsd:string.  \
                ?item_article  owl:sameAs ?manifestation;  \
                               cdm:manifestation_has_item ?item.  \
             FILTER (?lang like "http://publications.europa.eu/resource/authority/language/ENG" AND year(?date) like year(now()))  \
            } \
            ORDER BY DESC(?date) ';*/

    public function __construct() {
        parent::__construct();
        $this->indexer = new \App\Indexer\Indexer();

        // instantiate the pagination object
        $this->pagination = new Zebra_Pagination();
    }

    /*
     * Retrieve an array of stands
     * @return a JSON string representing an array of documents    
     */

    /*public function retrieveDocuments() {

        // Run SPARQL query
        $query = self::$RECENT_PUBLISHED_DOCS_SPARQL_QUERY . " LIMIT 10000";

        $outputFormat = 'json';
        $endpoint = Config::get('app.cellar_sparql_endpoint');

        //echo $endpoint;
        $results = SPARQL::retrieveLinkedData($endpoint, $query);
        print ("Recently published : <br><br> ");

        //print_r($results);
        // convert json string into an assoc table
        $jsonDocs = json_decode($results, true);

        return $jsonDocs['results']['bindings'];
    }*/
/*
    public function retrieveDocumentsDuringPeriod($yearStart, $yearEnd) {

        $query = "DEFINE input:inference \"cdm_rule_set\" " .
                "PREFIX cdm: <http://publications.europa.eu/ontology/cdm#> " .
                "SELECT ?title ?item ?date " .
                "WHERE {" .
                "    ?article    a cdm:proposal_act; " .
                "            cdm:resource_legal_id_celex ?id; " .
                "            cdm:work_date_document ?date; " .
                "            cdm:work_has_expression ?exp. " .
                "    ?exp    cdm:expression_uses_language ?lang; " .
                "            cdm:expression_title ?title; " .
                "            cdm:expression_manifested_by_manifestation ?manifestation.  " .
                "    ?manifestation cdm:manifestation_type \"html\"^^<http://www.w3.org/2001/XMLSchema#string>.  " .
                "    ?item_article  owl:sameAs ?manifestation;  " .
                "                   cdm:manifestation_has_item ?item.  " .
                "FILTER (?lang like 'http://publications.europa.eu/resource/authority/language/ENG' AND year(?date) >= $yearStart AND year(?date) <= $yearEnd)  " .
                "} " .
                " ORDER BY DESC(?date) " .
                " LIMIT 10000";

         $endpoint = Config::get('app.cellar_sparql_endpoint');

        //echo $endpoint;
        $results = SPARQL::retrieveLinkedData($endpoint, $query);
        print ("Recently published : <br><br> ");

        //print_r($results);
        // convert json string into an assoc table
        $jsonDocs = json_decode($results, true);

        return $jsonDocs['results']['bindings'];
    }*/

    /*     * ********************** STATIC FUNCTIONS *********************** */

    /**
     * Retrieve recently published documents from the CELLAR
     */
    /*public static function retrieveRecentlyPublishedDocuments() {
        // Run SPARQL query
        $query = self::$RECENT_PUBLISHED_DOCS_SPARQL_QUERY .
                //" LIMIT 50";
                " LIMIT " . Config::get('app.cellar_max_retrieved_docs');

        
        $endpoint = Config::get('app.cellar_sparql_endpoint');

        //echo $endpoint;
        $results = SPARQL::retrieveLinkedData($endpoint, $query);
        print ("Recently published : <br><br> ");


        //print_r($results);
        // convert json string into an assoc table
        $jsonResults = json_decode($results, true);

        return $jsonResults;
    }*/

    /*     * *
     * Retrieve the list of documents
     */

   /* public function retrieveDocumentList($lang_code, $eli_lang_code, $records_per_page, $criteria = 'ORDER BY DESC(?date)') {
        $query = 'SELECT DISTINCT ?act ?id ?title ?date ?theme ?procedure ?yes ?mixed ?no ?total WHERE {
                    ?act sioc:has_parent ?parent.
                    ?act a sioc:Forum;
                        sioc:id ?id;                        
                        lodep:title ?title.
                        FILTER (LANG(?title) = "' . $eli_lang_code . '" ) 
                    OPTIONAL {?act lodepcreated_at ?date.}        
                    OPTIONAL{                        
                        ?act    lodep:topic ?topic.
                        ?topic rdfs:label ?theme.
                        FILTER(LANG(?theme) = "' . $eli_lang_code . '")
                    }
                    OPTIONAL{
                        ?act lodep:procedure_type ?p.
                        ?p rdfs:label ?procedure.
                        FILTER(LANG(?procedure)="' . $eli_lang_code . '")
                    }
                    OPTIONAL {?act lodep:num_items_yes ?yes.}
                    OPTIONAL {?act lodep:num_items_mixed ?mixed.}
                    OPTIONAL {?act lodep:num_items_no ?no.}                         
                    OPTIONAL {?act lodep:num_items_total ?total.}
                 }';

        $query .= ' ' . $criteria;

        $this->processQuery($query);

        $this->records_per_page = $records_per_page;
    }*/

    /**
     * Process a SPARQL query and parse results
     * @param type $query
     */
    /*public function processQuery($query) {
        $results = SPARQL::runSPARQLQuery(env('VIRTUOSO.SPARQL.ENDPOINT_SRV'), $query);
        error_log(urlencode($query));
        // parse results into different hash tables
        $jsnResults = json_decode($results);
//        print_r($jsnResults);
        if (!empty($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                $id = $res->id->value;
                if (!empty($this->themeHash[$id])) {
                    $this->themeHash[$id] .= ' / ' . $res->theme->value;
                } else {
                    $genericAct = substr($res->act->value, 0, strlen($res->act->value) - 4);
                    $this->genericActHash[$id] = $genericAct;
                    if (!empty($res->theme)) {
                        $this->themeHash[$id] = $res->theme->value;
                    }
                    if (!empty($res->procedure)) {
                        $this->procedureHash[$id] = $res->procedure->value;
                    }
                    $this->titleHash[$id] = $res->title->value;
                    if (!empty($res->date)) {
                        $this->dateHash[$id] = $res->date->value;
                    }
                }
            }
        }
    }*/

    /*     * *
     * pagination
     */

    /*public function doPagination($eli_lang_code) {

        if (empty($this->genericActHash) || sizeof($this->genericActHash) == 0) {
            return;
        }

// the number of total records is the number of records in the array
        $this->pagination->records(count($this->genericActHash));

// records per page
        $this->pagination->records_per_page($this->records_per_page);

// here's the magick: we need to display *only* the records for the current page
        $slices = array_slice($this->genericActHash, (($this->pagination->get_page() - 1) * $this->records_per_page), $this->records_per_page, true);

        foreach ($slices as $id => $genericActID) {
            $this->processResult($id, $eli_lang_code);
        }
        $this->pagination->render();
    }*/

   /* public function processResult($id, $eli_lang_code) {

        if (!empty($this->genericActHash[$id])) {
            // CELLAR
            printf('<div class="row search-list-item" ng-controller="searchListItemCtrl"><div class="col-md-10"><a href="/lod/documents/displayDoc/id/%s/lang/%s">%s</a><p>%s %s <strong>%s</strong></p><p>%s</p></div>', $id, $eli_lang_code, $this->titleHash[$id], $this->dateHash[$id], $this->getSeperator($this->dateHash[$id]), $this->procedureHash[$id], $this->themeHash[$id]);

            printf('<div class="col-md-2 statist"><div class="panel-default"><div class="panel-heading"><i class="fa fa-comments-o"></i> <span id="number-of-comments-%s">[[numberOfComments]]</span></div><div class="panel-body no-padding"><div ID="search-list-item-chart-%s" class="chart-area-search-list-item" ng-init="drawChartForItem(\'search-list-item-chart-%s\', \'%s\')"> </div></div> </div></div>', $id, $id, $id, $this->genericActHash[$id]);

            printf('</div>'); // end row
        }
    }*/

   /* function getSeperator($date) {
        if (!empty($date)) {
            return '|';
        } else {
            return '';
        }
    }*/

    /*public function getTotalResults() {
        if (empty($this->themeHash)) {
            return 0;
        } else {
            return count($this->themeHash);
        }
    }*/

   /* public function getStart() {
        if (!empty($this->pagination)) {
            return (($this->pagination->get_page() - 1) * $this->records_per_page);
        } else {
            return 0;
        }
    }*/

    /*public function getEnd() {
        if (!empty($this->pagination)) {

            $end = ($this->pagination->get_page() * $this->records_per_page) - 1;

            if ($end > count($this->themeHash)) {
                $end = count($this->themeHash);
            }

            return $end;
        } else {
            return 0;
        }
    }*/

  /*  public static function displayTopics($actURI, $eli_lang_code) {
        $query = sprintf('PREFIX sioc: <http://rdfs.org/sioc/ns#> PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
                SELECT DISTINCT ?theme
                WHERE {
                    <%s>  lodep:topic ?topic.
                    ?topic rdfs:label ?theme.
                    FILTER(LANG(?theme) = "%s")                    
                 }', $actURI, $eli_lang_code
        );
//        echo urlencode($query);
        
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $result) {
                printf('<span><a href="#">%s</a></span>', $result->theme->value);
            }
        }
    }*/

}
