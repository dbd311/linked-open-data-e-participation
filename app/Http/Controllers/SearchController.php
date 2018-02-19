<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Elastic;
use App\Http\Controllers\WebPageController;
use Illuminate\Support\Facades\Config;

/**
 * Controller for Search activities (ElasticSearch)
 * @author Duy Dinh
 * @date 15 May 2016
 */
class SearchController extends Controller {

    /**
     * Run a query via ElasticSearch
     *
     * @param  Request $request
     * @return Response
    */
    public function runQuery(Request $request) {

        $query = trim($request->get('q'));
        $lang = $request->get('lang');

//        error_log('query is : ' . $query);
        // convert lang into eli lang code
        $eli_lang_code = MetadataController::get_eli_lang_code($lang);
        if (isset($eli_lang_code)) {
            $eli_lang_code = strtolower($eli_lang_code);
        } else {
            $eli_lang_code = Config::get('app.eli_lang_code');
        }

        $fields = ['title', 'subject', 'year', 'num'];
        $sections = ['sections.title', 'sections.subject', 'sections.content', 'sections.content.std'];
        $preamble = ['preamble.titleHtml', 'preamble.content', 'preamble.content.std'];


//        error_log('Search using analyzer : ' . $analyzerName);
        // TODO: replace these if-else by a pre-processing step if the query contains multiple keywords with multiple wildcards or more than two quotes
        if ((strpos($query, '"')) !== false) {
            // phrase match
            $jsonQuery = '{ "query": { "bool": { "should": [ ' . $this->phraseMatches($fields, $query) . ','
                    . '{ "nested": { "path": "sections", "query": { "bool": { "should": [ ' . $this->phraseMatches($sections, $query) . ' ] }}}},'
                    . '{ "nested": { "path": "preamble", "query": { "bool": { "should": [ ' . $this->phraseMatches($preamble, $query) . ' ] }}}}'
                    . ']}}';
        } else if (((strpos($query, '*')) !== false) || ((strpos($query, '?')) !== false)) {
            // wildcard match
            $jsonQuery = '{ "query": { "bool": { "should": [' . $this->wildcardsMatches($fields, $query) . ']}}';
        } else {
            // bag of words match

            $jsonQuery = '{ "query": { "bool": { "should": [' . $this->matches($fields, $query) . ','
                    . '{ "nested": { "path": "sections",  "query": { "bool": { "should": [' . $this->matches($sections, $query) . '  ]}}}},'
                    . '{ "nested": { "path": "preamble",  "query": { "bool": { "should": [' . $this->matches($preamble, $query) . ']}}}}'
                    . ']}}';
        }

        $jsonQuery .= ', "highlight" : {"pre_tags" : ["<b>"], "post_tags" : ["</b>"], "encoder" : "html", "order" : "score", "require_field_match" : true, "fields" : {"*" : {}}}';

        $jsonQuery .= '}';

//        error_log('query : ' . $jsonQuery);

        $params = ['index' => $eli_lang_code . '_docindex', 'type' => env('INDEX_TYPE', 'doc'), 'body' => $jsonQuery];

        $results = Elastic::search($params);

        return \Response::json($results);
    }

    public function matches($fields, $query) {
        $matchStr = '';
        foreach ($fields as $field) {
            $matchStr .= '{ "match": {"' . $field . '" : "' . $query . '"}},';
        }

        return substr($matchStr, 0, -1);
    }

    public function wildcardsMatches($fields, $query) {
        $matchStr = '';
        foreach ($fields as $field) {
            $matchStr .= '{ "wildcard": {"' . $field . '" : "' . $query . '"}},';
        }

        return substr($matchStr, 0, -1);
    }

    public function phraseMatches($fields, $query) {
        $matchStr = '';
        foreach ($fields as $field) {
            $matchStr .= '{ "match": {"' . $field . '" : { "query" : ' . $query . ', "type" : "phrase"}}},';
        }

        return substr($matchStr, 0, -1);
    }

    /**
     * Get detailed results for the query
     *
     * @return Response
    */
	 
    public function getResults(Request $request) {

        WebPageController::updateLocale($request->get('lang'));
        return view('documents.search-docs');
    }

}
