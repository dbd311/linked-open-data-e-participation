<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MetadataController;
use SPARQL;
use App\Utils\DateProcessing;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\WebPageController;
use Illuminate\Support\Facades\Session;

/* * *
 * This controller is used for processing documents
 * @author: Duy Dinh <dinhbaduy@gmail.com>
 * @date 03/06/2016
 */

class DocumentController extends Controller {

    public function populate() {
        $endpoint = "http://publications.europa.eu/webapi/rdf/sparql";
        $query = "prefix cdm: <http://publications.europa.eu/ontology/cdm#> "
                . "SELECT * {?work a cdm:act_preparatory} LIMIT 100";
        $results = SPARQL::retrievedLinkedData($endpoint, $query);
        print ($results);
    }

    public function displayDoc(Request $request) {

        $path = $request->get('path');
        $hl = $request->get('hl');
        $lang = Config::get('app.locale');
        $param = $request->get('lang');
        if (isset($param)) {
            $lang = $param;
        }
        App::setLocale($lang);

        return view('documents.json-document')->with(['path' => $path, 'hl' => $hl]);
    }

    public function _post_displayContainerStatistics(Request $request) {
        $params = array('containerID' => $request->get('containerID'));
        $queryString = http_build_query($params);
        WebPageController::updateLocale($request->get('lang'));
        Redirect::to(action('DocumentController@displayDocStatistics') . '?' . $queryString)->send();
    }

    public function displayContainerStatistics(Request $request, $containerID = null) {
        WebPageController::updateLocale($request->get('lang'));
        return view('documents.document-statistics', ['containerID' => $containerID]);
    }

    public function populateFormexJson() {
        if (substr(Session::get('user')->role->value, sizeof(Session::get('user')->role->value) - 6) == 'admin') {
            return view('admin.populate-json-docs');
        } else {
            return view('errors.forbidden');
        }
    }

    /*     * *** V2.0 *** */

    public function loadDocument(Request $request) {
        $path = $request->get('path');

        $content = file_get_contents(public_path(env('FORMEX.DOCUMENTS.JSON.PATH') . '/' . $path));
//        error_log($content);
        return $content;
    }

    public function getDocuments(Request $request, $criteria = 'date') {
        $lang_code = Config::get('app.locale');
        $param = $request->get('lang');
        
        if (isset($param)) {
            $lang_code = $param;
        }
        
        $eli_lang_code = strtolower(MetadataController::get_eli_lang_code($lang_code));

        $query = 'SELECT DISTINCT ?act ?id ?title ?subject ?date ?theme ?procedure ?yes ?mixed ?no ?total ?path ?docCode WHERE {
                    ?act sioc:has_parent ?parent; a sioc:Forum; sioc:id ?id; lodep:title ?title.
                    FILTER (LANG(?title) = "' . $eli_lang_code . '" )
                     ?act lodep:subject ?subject.
                    OPTIONAL{?act sioc:has_space ?path.}    
                    OPTIONAL {?act lodep:created_at ?date.}
                    OPTIONAL{
                        ?act    lodep:topic ?topic.
                        ?topic skos:prefLabel ?theme.
                        FILTER(LANG(?theme) = "' . $eli_lang_code . '")
                    }
                    OPTIONAL{
                        ?act lodep:procedure_type ?p.
                        ?p rdfs:label ?procedure.
                        FILTER(LANG(?procedure)="' . $eli_lang_code . '")
                    }
                    OPTIONAL {?parent lodep:num_items_total ?total.}
                    OPTIONAL {?parent lodep:num_items_yes ?yes.}
                    OPTIONAL {?parent lodep:num_items_mixed ?mixed.}
                    OPTIONAL {?parent lodep:num_items_no ?no.}
                    OPTIONAL {?act lodep:doc_code ?docCode.}
                 } ORDER BY DESC(?' . $criteria . ')';

        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);

        // extract doc ids from results
        $ids = array();
        if (!empty($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                if (!in_array($res->id->value, $ids)) {
                    array_push($ids, $res->id->value);
                }
            }
        }

        // extract metadata for each document
        if (!empty($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                $id = $res->id->value;
                if (!empty($data->themes[$id])) {
                    $data->themes[$id] .= ' / ' . $res->theme->value;
                } else {
                    $genericAct = substr($res->act->value, 0, strlen($res->act->value) - 4);
                    $data->genericActURI[$id] = $genericAct;
                    if (!empty($res->theme)) {
                        $data->themes[$id] = $res->theme->value;
                    } else {
                        $data->themes[$id] = 'NA'; // unknown
                    }
                    if (!empty($res->procedure)) {
                        $data->procedure[$id] = $res->procedure->value;
                    } else {
                        $data->procedure[$id] = 'NA'; // unknown
                    }
                    $explodeUri = explode('/', $genericAct);
                    $year = $explodeUri[sizeof($explodeUri) - 2];
                    $num = $explodeUri[sizeof($explodeUri) - 1];
                    $data->title[$id] = $res->title->value;
                    $data->subject[$id] = $res->subject->value;
                    $data->nbOfComments[$id] = $res->total->value;
                    $data->nbOfCommentsY[$id] = $res->yes->value;
                    $data->nbOfCommentsM[$id] = $res->mixed->value;
                    $data->nbOfCommentsN[$id] = $res->no->value;

                    $data->year[$id] = $year;
                    $data->num[$id] = $num;
                    $data->lang2[$id] = strtoupper(MetadataController::get_lang_code_no_json($eli_lang_code));
                    if (!empty($res->date)) {
                        $data->date[$id] = $res->date->value;
                    } else {
                        $data->date[$id] = '';
                    }
                    $data->path[$id] = $res->path->value;
                    if (!empty($res->docCode)) {
                        $data->docCode[$id] = $res->docCode->value;
                    } else {
                        $data->docCode[$id] = 'NA'; // unknown
                    }
                }
            }
        }

        // build final json list of documents with metadata
        foreach ($ids as $id) {
            $document = array('id' => $id,
                'path' => $data->path[$id],
                'title' => $data->title[$id],
                'subject' => $data->subject[$id],
                'nbOfComments' => $data->nbOfComments[$id],
                'lang' => $eli_lang_code,
                'date' => $data->date[$id],
                'procedure' => $data->procedure[$id],
                'themes' => $this->retrieveThemes($id, $eli_lang_code),
                'genericActURI' => $data->genericActURI[$id],
                'nbOfCommentsY' => $data->nbOfCommentsY[$id],
                'nbOfCommentsM' => $data->nbOfCommentsM[$id],
                'nbOfCommentsN' => $data->nbOfCommentsN[$id],
                'docCode' => $data->docCode[$id]);

            $listDocuments[] = $document;
        }

        return \Response::json($listDocuments);
    }

    public function filterProcedureYear($years) {
        $filter = '';
        if (!empty($years)) {
            $filter .= "FILTER(";
            for ($i = 0; $i < sizeof($years) - 1; $i++) {
                $filter .= " ?year = '" . $years[$i] . "'^^<http://www.w3.org/2001/XMLSchema#gYear> ||";
            }
            if (sizeof($years) > 0) {
                $filter .= " ?year = '" . $years[$i] . "')";
            }
        }
        return $filter;
    }
	
	public function get_concept_from_altLabel($altLabel, $lang_code){
		$query = 'SELECT  DISTINCT ?concept_name  WHERE {		
				?concept skos:altLabel ?altLabel.
				FILTER (REGEX(lcase(?altLabel),  lcase("'. trim($altLabel) .'")))
				?concept skos:prefLabel ?concept_name.		
				FILTER (LANG(?concept_name)="'.$lang_code.'")
				}'; 
		$results = SPARQL::runSPARQLQuery($query);
		$jsnResults = json_decode($results);	
        if (!empty($jsnResults->results->bindings)) {return $jsnResults->results->bindings[0]->concept_name->value;}
		return "";
	}

	public function create_filter_themes_altLabel($theme_list, $eli_lang_code, $lang_code) {
        if (empty($theme_list) || $theme_list == '') {
            return 'FILTER(LANG(?theme)="' . $eli_lang_code . '")';
        }
       $themes = preg_split('/[;]+/', $theme_list, -1, PREG_SPLIT_NO_EMPTY);
        $filter = 'FILTER(LANG(?theme)="' . $eli_lang_code . '") FILTER (';
        for ($i = 0; $i < (sizeof($themes) - 1); $i++) {
            $theme = trim($themes[$i]);
            if ($theme !== '') {			
                $filter .= 'contains(lcase(?theme), lcase("' . trim($themes[$i]) . '")) || ';
				$concept_of_altLabel = $this->get_concept_from_altLabel($theme, $lang_code);
				$filter .= $this->create_filter_altLabel($concept_of_altLabel, $lang_code);
				if ($concept_of_altLabel !== ""){$filter .= ' || ';}
            }
        }
        if (sizeof($themes) > 0) {
            $filter .= 'contains(lcase(?theme), lcase("' . trim($themes[$i]) . '")) ';
			$concept_of_altLabel = $this->get_concept_from_altLabel(trim($themes[$i]), $lang_code);
			if ($concept_of_altLabel !== ""){$filter .= ' || ';}
			$filter .= $this->create_filter_altLabel($concept_of_altLabel, $lang_code);
			$filter .= ')';
        }
        return $filter;
    }

	
	public function create_filter_altLabel($list_altLabel, $lang_code) {
         if (empty($list_altLabel) || $list_altLabel == '') {
            return '';
        }
        $themes = preg_split('/[;]+/', $list_altLabel, -1, PREG_SPLIT_NO_EMPTY);
        $filter = '';
        if (sizeof($list_altLabel) > 0) {
            $filter .= 'contains(lcase(?theme), lcase("' . trim($list_altLabel) . '")) ';
        }
        return $filter;
    }
	
		
	public function create_filter_themes($theme_list, $eli_lang_code) {
        if (empty($theme_list) || $theme_list == '') {
            return 'FILTER(LANG(?theme)="' . $eli_lang_code . '")';
        }
        $themes = preg_split('/[;]+/', $theme_list, -1, PREG_SPLIT_NO_EMPTY);
        $filter = 'FILTER(LANG(?theme)="' . $eli_lang_code . '") FILTER (';
        for ($i = 0; $i < sizeof($themes) - 1; $i++) {
            $theme = trim($themes[$i]);
            if ($theme !== '') {
                $filter .= 'contains(lcase(?theme), lcase("' . trim($themes[$i]) . '")) || ';
            }
        }
        if (sizeof($themes) > 0) {
            $filter .= 'contains(lcase(?theme), lcase("' . trim($themes[$i]) . '")))';
        }
        return $filter;
    }

    /**
     * Get documents by different filtering criteria
     */
    public function getFilteredDocuments(Request $r) {
        $lang_code = Config::get('app.locale');
        $param = $r->get('lang');
        if (isset($param)) {
            $lang_code = $param;
        }
        $lang = strtolower(MetadataController::get_eli_lang_code($lang_code));

        $criteria = $r->get('ct');
        if (empty($criteria)) {
            $criteria = 'date';
        }

        $themes = $r->get('themes');
        $topics = $r->get('topics');
        $dateFrom = DateProcessing::checkDateStart($r->get('date-from'));
        $dateTo = DateProcessing::checkDateEnd($r->get('date-to'));

        $query = 'SELECT DISTINCT ?act ?id ?title ?theme ?date ?procedure ?yes ?mixed ?no ?total ?path ?docCode 
		WHERE {?act sioc:has_parent ?parent; sioc:id ?id; sioc:has_space ?path; lodep:topic ?topic. ?topic skos:prefLabel ?theme.
		OPTIONAL{?topic skos:altLabel ?altLabel. FILTER (LANG(?altLabel) = "'.$lang_code.'")}';

        /*
          $themes can be the select eurovoc hierarchy ...
         */
        if ($r->has('themes') || $r->has('topics')) {
            $keywords = '';
            if ($r->has('themes')) {
                $keywords .= urldecode($themes);
            }
            if ($r->has('topics')) {
                $keywords .= urldecode($topics);
            }
           // $query .= $this->create_filter_themes($keywords, $lang);
		   $query .= $this->create_filter_themes_altLabel($keywords, $lang, $lang_code);

        } else {
            $query .= 'FILTER(LANG(?theme)="' . $lang . '") ';
        }
        $query .= '?act lodep:title ?title. FILTER (LANG(?title)="' . $lang . '") ?act lodep:created_at ?date. ';

        if ($r->has('date-from')) {
            $query .= ' FILTER((?date >= "' . $dateFrom . '"^^xsd:dateTime)';
            if ($r->has('date-to')) {
                $query .= ' AND (?date <= "' . $dateTo . '"^^xsd:dateTime)';
            }
            $query .= ')';
        }

        $query .= ' OPTIONAL{?act lodep:procedure_type ?pr. ?pr rdfs:label ?procedure. FILTER(LANG(?procedure)="' . $lang . '")} OPTIONAL{?act lodep:doc_code ?docCode.} ?parent lodep:num_items_yes ?yes; lodep:num_items_mixed ?mixed; lodep:num_items_no ?no; lodep:num_items_total ?total.}';

        if ($r->has('ct')) {
            switch ($criteria) {
                case 1:
                    $criteria = 'date';
                    break;
                case 2:
                    $criteria = 'total';
                    break;
                case 3:
                    $criteria = 'yes';
                    break;
                case 4:
                    $criteria = 'mixed';
                    break;
                case 5:
                    $criteria = 'no';
                    break;
                default :
                    $criteria = 'date';
                    break;
            }
            $query .= ' ORDER BY DESC(?' . $criteria . ')';
        }
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $ids = array();
        // extract the doc ids in results
        if (!empty($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                if (!in_array($res->id->value, $ids)) {
                    array_push($ids, $res->id->value);
                }
            }
        }
        // extract metadata from each result
        if (!empty($jsnResults->results)) {

            foreach ($jsnResults->results->bindings as $res) {
                $id = $res->id->value;

                if (!empty($data->themes[$id])) {
                    $data->themes[$id] .= ' / ' . $res->theme->value;
                } else {
                    // remove the last four characters from the URI
                    $genericAct = substr($res->act->value, 0, strlen($res->act->value) - 4);

                    $data->genericActURI[$id] = $genericAct;
                    if (!empty($res->theme)) {
                        $data->themes[$id] = $res->theme->value;
                    }
                    if (!empty($res->procedure)) {
                        $data->procedure[$id] = $res->procedure->value;
                    }
                    if (!empty($res->docCode)) {
                        $data->docCode[$id] = $res->docCode->value;
                    }
                    $explodeUri = explode('/', $genericAct);
                    $year = $explodeUri[sizeof($explodeUri) - 2];
                    $num = $explodeUri[sizeof($explodeUri) - 1];
                    $data->title[$id] = $res->title->value;
                    $data->nbOfComments[$id] = $res->total->value;
                    $data->nbOfCommentsY[$id] = $res->yes->value;
                    $data->nbOfCommentsM[$id] = $res->mixed->value;
                    $data->nbOfCommentsN[$id] = $res->no->value;
                    $data->year[$id] = $year;
                    $data->num[$id] = $num;


                    $data->lang2[$id] = strtoupper(MetadataController::get_lang_code_no_json($lang));
                    if (!empty($res->date)) {
                        $data->date[$id] = $res->date->value;
                    }
                    $data->path[$id] = $res->path->value;
                }
            }
        }

        // build final list of json documents
        foreach ($ids as $id) {

            $document = $this->parseDocument($id, $data, $lang);
            $listDocuments[] = $document;
        }

        if (!empty($listDocuments)) {
            return \Response::json($listDocuments);
        } else {
            return null;
        }
    }

    /**
     * Parse document from matrix of metadata
     * @param type $id
     * @param type $data matrix of data
     * @param $hl language
     */
    public function parseDocument($id, $data, $hl) {
        $path = $data->path[$id]; // relative path
        // optional metadata
        $date = empty($data->date[$id]) ? '' : $data->date[$id];
        $procedure = empty($data->procedure[$id]) ? '' : $data->procedure[$id];
        $themes = $this->retrieveThemes($id, $hl);

        $document = array('id' => $id,
            'path' => $path,
            'title' => $data->title[$id],
            'lang' => $hl,
            'date' => $date,
            'procedure' => $procedure,
            'themes' => $themes,
            'genericActURI' => $data->genericActURI[$id],
            'nbOfComments' => $data->nbOfComments[$id],
            'nbOfCommentsY' => $data->nbOfCommentsY[$id],
            'nbOfCommentsM' => $data->nbOfCommentsM[$id],
            'nbOfCommentsN' => $data->nbOfCommentsN[$id],
            'docCode' => $data->docCode[$id]);

        return $document;
    }

    /*
     * Retrieve themes of a document for a particular language
     * @param string $docID document ID
     * @param string $lang_code a language code (of 2 or 3 chars)
     */

    public function retrieveThemes($docID, $lang_code) {
        // convert to eli lang code if it is not the case
        if (strlen($lang_code) == 2) {
            $eli_lang_code = strtolower(MetadataController::get_eli_lang_code($lang_code));
        } else {
            $eli_lang_code = $lang_code;
        }
        $query = 'SELECT DISTINCT ?theme WHERE { ?act sioc:id "' . $docID . '"^^rdfs:Literal. ?act lodep:topic ?topic. ?topic skos:prefLabel ?theme.' . 'FILTER(LANG(?theme)="' . $eli_lang_code . '")}';

        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);

        $themes = '';
        // extract metadata from each result
        if (!empty($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                if (empty($themes)) {
                    $themes = $res->theme->value;
                } else {
                    $themes .= ' / ' . $res->theme->value;
                }
            }
        }
        return $themes;
    }

    /**
     * Load metadata document
     * @param type $docID
     * @param type $eli_lang_code
     * @return type
     */
    public static function loadMetadata($docID, $eli_lang_code) {

        $query = "SELECT DISTINCT ?date_adopted ?id_celex ?procedure_type_label ?procedure_code ?directory_code ?doc_code
                  WHERE{
                    ?act a sioc:Forum; sioc:id '" . $docID . "'^^rdfs:Literal.
                    OPTIONAL{?act lodep:created_at ?date_adopted.}
                    OPTIONAL{?act lodep:id_celex ?id_celex.}                        
                    OPTIONAL{?act lodep:procedure_code ?procedure_code.}
                    OPTIONAL{?act lodep:directory_code ?directory_code.} 
                    OPTIONAL{?act lodep:doc_code ?doc_code.} 
                    OPTIONAL{?act lodep:procedure_type ?type_proc.
                    ?type_proc rdfs:label ?procedure_type_label.
                    FILTER (lang(?procedure_type_label) = '" . $eli_lang_code . "')}
                  }";
        return SPARQL::runSPARQLQuery($query);
    }
    
    

    /**
     * Load annexes
     * @param type $folder
     * @return type
     */
    public function loadAnnexes($folder) {
        $subCollectionPath = env('FORMEX.DOCUMENTS.JSON.PATH');
        $collectionPath = public_path() . '/' . $subCollectionPath;
        $annexes = array();
        foreach (glob($collectionPath . '/' . $folder . '/annexes/*') as $file) {
            $filename = basename($file);
            array_push($annexes, $filename);
        }
        return $annexes;
    }

    function deleteDocuments() {
        $query = 'CLEAR GRAPH <' . env('LOD_GRAPH') . '>';
        SPARQL::runSPARQLUpdateQuery($query);
        return redirect()->back();
    }
    
    function existFile(Request $request) {
        $subCollectionPath = env('FORMEX.DOCUMENTS.JSON.PATH');
        $collectionPath = public_path() . '/' . $subCollectionPath;
        $finded = 'false';
        foreach (glob($collectionPath . '/*/*.json') as $file) {
            $pos = strpos($file, $subCollectionPath);
            if ($pos > 0) {
                $filename = substr($file, $pos + strlen($subCollectionPath) + 1);
            } else {
                $filename = basename($file);
            }
            if($request->get('searchedFile') === $filename){
                $finded = 'true';
            }
        } return $finded;
    }
}
