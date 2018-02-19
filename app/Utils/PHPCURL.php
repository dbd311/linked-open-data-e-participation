<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\SPARQL;

/**
 * PHPSPARQL
 *
 * @author Duy Dinh <duy.dinh@gmail.com>
 * @date 27 April 2016
 * 
 */
class PHPCURL {

    /**
     * Run a SPARQL query from a SPARQL endpoint
     * $endpoint SPARQL endpoint
     * $query SPARQL query
     * $format returned format
     */
    public static function runAuthSPARQLQuery($username, $password, $endpoint, $query, $format = '') {


        $url = $endpoint . '?query=' . urlencode($query);

        if ($format != '') {
            $url = $url . '&format=' . $format;
        }

//        echo $url . "<br>";
        if (!function_exists('curl_init')) {
            die('CURL is not installed!');
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_HEADER, "application/rdf+xml");


        $response = curl_exec($ch);

        curl_close($ch);

        return $response;
    }

    /*     * *
     * Send a SPARQL query to a SPARQL endpoint and retrieve the XML results 
     * Returns the results in XML format
     */

    public static function runSPARQLQuery_XML($url, $query) {
        $format = 'xml';
        $DEBUG = 0;
        $searchUrl = $url . "?query=" . urlencode($query);
        if (!function_exists('curl_init')) {
            die('CURL is not installed');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $searchUrl . "&format=" . $format);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $header_array[] = "Mime-Version: 1.0";
        $header_array[] = "Content-type: text/html; charset=utf-8";
        $header_array[] = "Accept-Encoding: compress, gzip";
        curl_setopt($ch, CURLOPT_USERAGENT, "open-data-portal");
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        if (!$responce = curl_exec($ch)) {
            curl_close($ch);
            return null;
        }
        list($header, $body) = explode("\r\n\r\n", $responce, 2);
        if (substr($body, 0, 33) != '<table class="sparql" border="1">') {
            $body = htmlentities($body);
        }
        $pattern = '/http([^<>]*)/';
        $replace = '<a href="$0">$0</a href>';
        $body = preg_replace($pattern, $replace, $body);

        if ($DEBUG == 1) {
            $info = "searchUrl :" . $searchUrl;
            if (getenv('HTTP_X_FORWARDED_FOR')) {
                $pipaddress = getenv('HTTP_X_FORWARDED_FOR');
                $ipaddress = getenv('REMOTE_ADDR');
                $info = "Your Proxy IP address is : " . $pipaddress . "(via $ipaddress)";
            } else {
                $ipaddress = getenv('REMOTE_ADDR');
                $info = "Your IP address is : $ipaddress";
            }
            curl_close($ch);
            return $info . " : " . $_SERVER['REMOTE_ADDR'] . "|" . $header . "|" . $body;
        } else {
            curl_close($ch);
            return $body;
        }
    }

    /**
     * Run a SPARQL query from a SPARQL endpoint
     * $endpoint SPARQL endpoint
     * $query SPARQL query
     * $format returned format
     */
    public static function runSPARQLQuery($endpoint, $query, $format = 'json') {

        $url = $endpoint . '?query=' . urlencode($query);

        if ($format != '') {
            $url = $url . '&format=' . $format;
        }



        if (!function_exists('curl_init')) {
            die('CURL is not installed!');
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_USERAGENT, "Google Chrome Browser");
        curl_setopt($ch, CURLOPT_HEADER, "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8");


        $response = curl_exec($ch);

        curl_close($ch);

//        echo $url . "<br>";
        return $response;
    }

    public static function retrieveRecentlyPublishesArticles() {
        $query = "DEFINE input:inference \"cdm_rule_set\" " .
                "PREFIX cdm: <http://publications.europa.eu/ontology/cdm#> " .
                "SELECT ?title ?item ?date " .
                "WHERE {" .
                "    ?article    a cdm:proposal_act. " .
                "            cdm:resource_legal_id_celex ?id; " .
                "            cdm:work_date_document ?date; " .
                "            cdm:work_has_expression ?exp. " .
                "    ?exp    cdm:expression_uses_language ?lang; " .
                "            cdm:expression_title ?title; " .
                "            cdm:expression_manifested_by_manifestation ?manifestation.  " .
                "    ?manifestation cdm:manifestation_type \"html\"^^<http://www.w3.org/2001/XMLSchema#string>.  " .
                "    ?item_article  owl:sameAs ?manifestation;  " .
                "                   cdm:manifestation_has_item ?item.  " .
                "FILTER (?lang like 'http://publications.europa.eu/resource/authority/language/ENG')  " .
                "} " .
                "LIMIT 50";

        $outputFormat = 'json';
        $endpoint = Config::get('app.cellar_sparql_endpoint');

        $results = PHPSPARQL::runSPARQLQuery($endpoint, $query, $outputFormat);
        print ("Recently published : <br><br> ");
        // convert json string into an assoc table
        $jsonResults = json_decode($results, true);
        PHPSPARQL::doPagination($jsonResults['results']['bindings']);
    }

    public static function doPagination($elements) {
        if (empty($elements) || sizeof($elements) == 0) {
            return;
        }
        // how many records should be displayed on a page?
        $records_per_page = 10;
        // instantiate the pagination object
        $pagination = new Zebra_Pagination();
        // the number of total records is the number of records in the array
        $pagination->records(count($elements));
        // records per page
        $pagination->records_per_page($records_per_page);
        // here's the magick: we need to display *only* the records for the current page
        $slices = array_slice(
                $elements, (($pagination->get_page() - 1) * $records_per_page), $records_per_page
        );

        $i = 1;
        foreach ($slices as $res) {
            print '<strong>[' . (($pagination->get_page() - 1) * $records_per_page + $i) . '] </strong>';
            $i++;

            if (!empty($res['title'])) {
                $docID = CELLARDOC::extractDocIDfromURI($res['item']['value']);
                print_r('<strong><a href="lod/documents/show/' . $docID . '">' . $res['title']['value'] . '</a></strong>');
            }
            print "<br><br>";
        }
        echo "<br>";
        $pagination->render();
    }

    /**
     * Run a SPARQL query from a SPARQL endpoint
     * $endpoint SPARQL endpoint
     * $query SPARQL query
     * $format returned format
     */
    public static function runQuery($url, $format = '') {
        if ($format != '') {
            $url = $url . '&format=' . $format;
        }
        if (!function_exists('curl_init')) {
            die('CURL is not installed!');
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}
