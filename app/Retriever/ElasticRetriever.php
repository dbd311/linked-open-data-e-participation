<?php

namespace App\Retriever;

use App\Http\Display\Zebra_Pagination;
use App\Documents\CELLARDoc;

/**
 * ElasticRetriever allows to query the Elasic index
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 */
class ElasticRetriever extends Retriever {

    public function retrieveDocs($query) {
        $queryStr = str_replace(' ', '+', $query);

        $url = $this->SEARCH_SERVICE_HOST . '/_search?size=1000&q=' . $queryStr;

        //echo $url . "<br>";
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

        return json_decode($response, true);
    }

    /*     * *
     * pagination
     */

    public function paginate($jsonDocs, $records_per_page) {
//        print_r ($jsonDocs);
//        return;
//        
        $docs = $jsonDocs['hits']['hits'];
        if (empty($docs) || sizeof($docs) == 0) {
            return;
        }

// how many records should be displayed on a page?
//        $records_per_page = 10;
// instantiate the pagination object
        $pagination = new Zebra_Pagination();

// the number of total records is the number of records in the array        
        $pagination->records(count($docs));

// records per page
        $pagination->records_per_page($records_per_page);

// here's the magick: we need to display *only* the records for the current page
        $slices = array_slice(
                $docs, (($pagination->get_page() - 1) * $records_per_page), $records_per_page
        );

        $i = 1;

        foreach ($slices as $res) {
            //print '<strong>[' . (($pagination->get_page() - 1) * $records_per_page + $i) . '] </strong>';
            print '<img src="/images/logos/eu-logo.png" />&nbsp;';
            $i++;
            //print_r($res);
            if (!empty($res['_source']['title'])) {
                $docID = CELLARDoc::extractDocIDfromURI($res['_source']['item']['value']);
                print_r('<strong><a href="/lod/documents/show/' . $docID . '">' . $res['_source']['title']['value'] . '</a></strong>');
            }
            print "<br><br>";
        }
        
        echo "<br>";
        $pagination->render();
        echo '<div>&nbsp;</div>';
    }

}
