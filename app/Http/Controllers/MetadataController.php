<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SPARQL;
use Illuminate\Support\Facades\Config;
use App\Eloquent\Nationality;
use App\Eloquent\Group;
use Illuminate\Support\Facades\Session;

class MetadataController extends Controller {

    protected $CELLAR_endpoint;

    public function __construct() {
        $this->CELLAR_endpoint = Config::get('app.cellar_sparql_endpoint'); // CELLAR endpoint
    }

    /**
     * This function return tree if the input graph name existe, false otherwise.
     * @param Request $request: an object which contains the graph name and the user role. only the admine role can run this function
     * @return string: boolean "tree" or "false"
     */
    public function existGraph(Request $request) {

        $uri = env('LOD_GRAPH');
        $gName = $request->get('name');
        if (strlen($gName) > 0) {
            $uri .= '/' . $gName;
        }

        $query = 'SELECT DISTINCT ?s ?p ?o WHERE { graph <' . $uri . '> { ?s ?p ?o}  } limit 1';

        $jsnResults = json_decode(SPARQL::runSPARQLQuery($query));

        return \Response::json(!empty($jsnResults->results->bindings));
    }

    /**
     * Get the liste of existed graphs, in the triple store 
     * @return liste of graph names.
     */
    static public function getListGraph() {
        $query = 'SELECT DISTINCT ?graph WHERE {GRAPH ?graph {?s ?p ?o}}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $list_graphs = Array();
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                array_push($list_graphs, $res->graph->value);
            }
        }
        return list_graphs;
    }

    /**
     * This function check if the input graph name exist in the lists of existed graph and 
     *  return "tree" if the input graph name existe, "false" otherwise.
     * @param type $graph_name to check if existe
     * @return string: boolean "tree" or "false"
     */
    static public function in_list_graph($graph_name) {
        $query = 'SELECT DISTINCT ?graph WHERE {GRAPH ?graph {?s ?p ?o}}';

        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $list_graphs = Array();

        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                array_push($list_graphs, $res->graph->value);
            }
        }
        if (in_array($graph_name, $list_graphs)) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * This function retrieve the meta-data of language from CELLAR and put them in our triple store.
     * We can replace this way of adding metadat, by puting directelly the language graphe from the MDR of EU
     * @param Request $request: the user role have to be admin to run this function
     * @return previos page
     */
    function addEuroVoclanguages(Request $request) {
        if (substr(Session::get('user')->role->value, sizeof(Session::get('user')->role->value) - 6) != 'admin') {
            return view('errors.forbidden');
        }
        $query = "PREFIX cdm: <http://publications.europa.eu/ontology/cdm#>
                PREFIX skos: <http://www.w3.org/2004/02/skos/core#>
                PREFIX at: <http://publications.europa.eu/ontology/authority/>
                PREFIX dc: <http://purl.org/dc/elements/1.1/>                

                SELECT DISTINCT ?concept ?langue_name ?lang_code_2 ?lang_code_3 ?mapped_concept
                WHERE {
                        ?concept at:op-code ?lang_code_3. FILTER REGEX (?concept, <http://publications.europa.eu/resource/authority/language/>)
                        ?concept skos:prefLabel ?langue_name. 
                        FILTER(?langue_name='Bulgarian'@en || ?langue_name='Czech'@en || ?langue_name='Danish'@en || ?langue_name='German'@en || ?langue_name='Greek'@en || ?langue_name='English'@en || ?langue_name='Spanish'@en || ?langue_name='Estonian'@en || ?langue_name='Finnish'@en || ?langue_name='French'@en || ?langue_name='Irish'@en || ?langue_name='Croatian'@en || ?langue_name='Hungarian'@en || ?langue_name='Italian'@en || ?langue_name='Latvian'@en || ?langue_name='Lithuanian'@en || ?langue_name='Maltese'@en || ?langue_name='Dutch'@en || ?langue_name='Polish'@en || ?langue_name='Portuguese'@en || ?langue_name='Romanian'@en || ?langue_name='Slovak'@en || ?langue_name='Slovene'@en || ?langue_name='Serbian'@en || ?langue_name='Swedish'@en)
                        ?concept at:op-mapped-code ?mapped_concept. 
                        ?mapped_concept dc:source \"iso-639-1\".
                        ?mapped_concept at:legacy-code ?lang_code_2.
                }";


        $results = SPARQL::retrieveLinkedData($this->CELLAR_endpoint, $query);
        $jsnResults = json_decode($results);

        // Add European langues to LOD.
        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '/lang>{';
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $c = str_replace('http://publications.europa.eu/resource/authority/language/', "lang:", $res->concept->value);
                $query .= $c . ' a skos:Concept;at:op-code "' . $res->lang_code_3->value . '"^^rdfs:Literal;skos:prefLabel "' . $res->langue_name->value . '"@eng;at:op-mapped-code <' . $res->mapped_concept->value . '>. <' . $res->mapped_concept->value . '> a at:MappedCode;dc:source "iso-639-1"^^rdfs:Literal;at:legacy-code "' . $res->lang_code_2->value . '"^^rdfs:Literal. ';
            }
        }
        $query.='}';

        SPARQL::runSPARQLUpdateQuery($query);
        $this->add_Nationalities();
        return redirect()->intended('dashboard/espace-admin/?lang=' . $request->get('lang'));
    }

    function addEurovoc(Request $request) {
        if (substr(Session::get('user')->role->value, sizeof(Session::get('user')->role->value) - 6) != 'admin') {
            return view('errors.forbidden');
        }
    }

    function cleanVirtuosoGraph(Request $request) {
        if (substr(Session::get('user')->role->value, sizeof(Session::get('user')->role->value) - 6) != 'admin') {
            return view('errors.forbidden');
        }
    }

    /**
     * add metadata of countery. 
     * We can replace this way of adding metadat, by puting directelly the nationality/countery graphe from the MDR of EU
     */
    function add_Nationalities() {
        //$nationalities = $this->get_eurovoc_european_countries();
        $nationalities = array(
            'Austria',
            'Belgium',
            'Bulgaria',
            'Croatia',
            'Cyprus',
            'Denmark',
            'Finland',
            'France',
            'Germany',
            'Greece',
            'Hungary',
            'Ireland',
            'Italy',
            'Latvia',
            'Lithuania',
            'Luxembourg',
            'Malta',
            'Netherlands',
            'Norway',
            'Poland',
            'Portugal',
            'Romania',
            'Slovakia',
            'Slovenia',
            'Spain',
            'Sweden',
            'United_Kingdom'
        );
        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '/lang>{';
        foreach ($nationalities as $nationality) {
            $nationalityURI = env('SITE_NAME') . '/user/nationality/' . $nationality;
            $query .= '<' . $nationalityURI . '> a schema:Country.';
        }
        $query .='}';
        SPARQL::runSPARQLUpdateQuery($query);
    }

    /**
     * get the list of nationalities/counteries
     * @return the list in json format
     */
    function getNationalities() {
        $query = 'SELECT ?nationality WHERE {?nationality a schema:Country.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $newListNationalities = array();
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $nationality = new Nationality();
                $nationality->name = str_replace(env('SITE_NAME') . '/user/nationality/', '', $res->nationality->value);
                array_push($newListNationalities, $nationality);
            }
        }
        return \Response::json($newListNationalities);
    }

    /**
     * get the list of groups
     * @return the list in json format
     */
    function getGroups() {
        $query = 'SELECT DISTINCT ?user_group WHERE{ ?user a sioc:UserAccount; sioc:member_of ?user_group.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $newListGroups = array();
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $group = new Group();
                $group->name = str_replace(env('SITE_NAME') . '/user_group/', '', $res->user_group->value);
                array_push($newListGroups, $group);
            }
        }
        return \Response::json($newListGroups);
    }

    /**
     * get the nationality of a specific user
     * @param type $user: the user uri
     * @return string: the nationality
     */
    static public function getNationalitiesUser($user) {
        $query = 'SELECT ?nationality WHERE {<' . $user . '> lodep:nationality ?nationality.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);

        if (!empty($jsnResults)) {
            $nationality = str_replace(env('SITE_NAME') . '/user/nationality/', '', $jsnResults->results->bindings[0]->nationality->value);
        }
        return $nationality;
    }

    /**
     * get lang_code (two letters) from lang_code (tree letters)
     * @param type $eli_lang_code: the lang_code of tree letters
     * @return string $lang_code of two letters
     */
    static public function get_lang_code_no_json($eli_lang_code) {
        $query = 'SELECT DISTINCT ?lang_code_2  WHERE {
                ?concept at:op-code "' . strtoupper($eli_lang_code) . '"^^rdfs:Literal.
                ?concept at:op-mapped-code ?mapped_concept. 
                ?mapped_concept dc:source "iso-639-1"^^rdfs:Literal.
                ?mapped_concept at:legacy-code ?lang_code_2. 
                        }';
        // recuper the value
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            return $jsnResults->results->bindings[0]->lang_code_2->value;
        }
    }

    /**
     * get the lang name form the lang_cod of two letters
     * @param type $lang_codel lang_code of two latters
     * @return string lang_code of tree letters
     */
    static public function get_lang_name($lang_code) {
        $query = 'SELECT DISTINCT ?lang_name  WHERE {
                ?concept a skos:Concept.
                ?concept skos:prefLabel ?lang_name.
                ?concept at:op-mapped-code ?mapped_concept. 
                ?mapped_concept dc:source "iso-639-1"^^rdfs:Literal.
                ?mapped_concept at:legacy-code "' . $lang_code . '"^^rdfs:Literal. 
                }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            if (isset($jsnResults->results->bindings[0])) {
                return $jsnResults->results->bindings[0]->lang_name->value;
            } else {
                return $lang_code;
            }
        }
    }

    /**
     * get the lang_code of two letters form the lang_cod of tree letters
     * @param type $eli_lang_code: tree letters lang_code
     * @return string lang_code two letters
     */
    public function get_lang_code($eli_lang_code) {
        $query = 'SELECT DISTINCT ?lang_code_2  WHERE {
                ?concept at:op-code "' . strtoupper($eli_lang_code) . '"^^rdfs:Literal.
                ?concept at:op-mapped-code ?mapped_concept. 
                ?mapped_concept dc:source "iso-639-1"^^rdfs:Literal.
                ?mapped_concept at:legacy-code ?lang_code_2. 
                        }';
        // recuper the value
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            return \Response::json($jsnResults->results->bindings[0]->lang_code_2->value);
        }
    }

    /**
     * get the lang_code of two letters form the lang_cod of tree letters
     * @param type $lang_code: two letters lang_code
     * @return string eli_lang_code tree letters
     */
    static public function get_eli_lang_code($lang_code) {
        $query = 'SELECT DISTINCT ?lang_code_3 WHERE { ?concept at:op-code ?lang_code_3; at:op-mapped-code ?mapped_concept. ?mapped_concept dc:source "iso-639-1"^^rdfs:Literal; at:legacy-code "' . $lang_code . '"^^rdfs:Literal.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);

        if (!empty($jsnResults->results->bindings)) {
            return $jsnResults->results->bindings[0]->lang_code_3->value;
        }
    }

    /**
     * get narrower terms of microThesaruse
     * @param type $micro_thesaruse_name
     * @param type $lang_code: two letter
     * @return type
     */
    public function get_children_micro_thesaruse_name($micro_thesaruse_name, $lang_code) {
        $query = 'SELECT DISTINCT ?narrower_term_name
                                WHERE{
                                ?micro_thesaruse skos:prefLabel  ?micro_thesaruse_name. 
                                FILTER (REGEX (?micro_thesaruse_name, "' . $micro_thesaruse_name . '"))               
                                OPTIONAL{?narrower_term skos:broader ?micro_thesaruse. 
                                ?narrower_term skos:prefLabel ?narrower_term_name.
                                FILTER (LANG(?narrower_term_name)="' . $lang_code . '")}
                                }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $list_prefLabel = [];
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $list_prefLabel[] = $res->narrower_term_name->value;
            }
        }
        return $list_prefLabel;
    }

    /**
     * get list of European countery from Eurovoc
     * @return list of counteries
     */
    public function get_eurovoc_european_countries() {
        $query = 'SELECT DISTINCT ?narrower_term_name
                        WHERE{
                        ?micro_thesaruse skos:prefLabel  ?micro_thesaruse_name. 
                        FILTER (REGEX (?micro_thesaruse_name, "Northern Europe") 
                        || REGEX (?micro_thesaruse_name,"Southern Europe") 
                        || REGEX (?micro_thesaruse_name, "Western Europe") 
                        || REGEX (?micro_thesaruse_name, "Eastern Europe"))       
                        OPTIONAL{?narrower_term skos:broader ?micro_thesaruse. 
                        ?narrower_term skos:prefLabel ?narrower_term_name.
                        FILTER (LANG(?narrower_term_name)="en")}
                        } ORDER BY ?narrower_term_name';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $list_countries = [];
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $list_countries[] = $res->narrower_term_name->value;
            }
        }
        return $list_countries;
    }

    /**
     * get relatd terms, narower terms, and related terms of narrower terms of a related terms
     * @param type $related_term_name
     * @param type $lang_code: two letters
     * @return list of relatd terms, narower terms, and related terms. 
     */
    public function get_children_related_term_name($related_term_name, $lang_code) {
        $query = 'SELECT DISTINCT ?related_term_related_term_name ?narrower_term_name ?related_term_narrower_term_name
                WHERE{              
                ?micro_thesaruse skos:related ?related_term. 
                ?related_term skos:prefLabel ?related_term_name. 
                FILTER (REGEX (?related_term_name, "' . $related_term_name . '"))     
                OPTIONAL{?related_term skos:related ?related_term_related_term.  
                ?related_term_related_term skos:prefLabel ?related_term_related_term_name.
                FILTER (LANG(?related_term_related_term_name)="' . $lang_code . '")}  
                OPTIONAL{?narrower_term skos:broader ?micro_thesaruse. 
                ?narrower_term skos:prefLabel ?narrower_term_name.
                FILTER (LANG(?related_term_related_term_name)="' . $lang_code . '")}
                OPTIONAL{?narrower_term skos:related ?related_term_narrower_term.  
                ?related_term_narrower_term skos:prefLabel ?related_term_narrower_term_name.
                FILTER (LANG(?related_term_narrower_term_name)="' . $lang_code . '")} 				
                }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $list_prefLabel = [];
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $list_prefLabel[] = $res->related_term_related_term_name->value;
                $list_prefLabel[] = $res->narrower_term_name->value;
                $list_prefLabel[] = $res->related_term_narrower_term_name->value;
            }
        }
        return $list_prefLabel;
    }

    /**
     * get related terms list of narrower terms
     * @param type $narrower_term_name
     * @param type $lang_code: two letters
     * @return list of related terms
     */
    public function get_children_narrower_term_name($narrower_term_name, $lang_code) {
        $query = 'SELECT DISTINCT ?related_term_narrower_term_name
                WHERE{              				
                ?narrower_term skos:broader ?micro_thesaruse. 
                ?narrower_term skos:prefLabel ?narrower_term_name.
                FILTER (REGEX (?narrower_term_name, "' . $narrower_term_name . '"))    
                OPTIONAL{?narrower_term skos:related ?related_term_narrower_term.  
                ?related_term_narrower_term skos:prefLabel ?related_term_narrower_term_name.
                FILTER (LANG(?related_term_narrower_term_name)="' . $lang_code . '")}  
                }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $list_prefLabel = [];
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $list_prefLabel[] = $res->related_term_narrower_term_name->value;
            }
        }
        return $list_prefLabel;
    }

    /**
     * get the list of procedure year and their occurrence.
     * @return list of procedure year
     */
    public function getProcedureYear() {
        $query = "SELECT ?procedure_year  COUNT(?procedure_year) as ?number
                WHERE {
                ?act sioc:has_parent ?parent.
                ?act lodep:title ?title. FILTER(LANG(?title)='eng')
                ?act lodep:procedure_year ?procedure_year.
                } ORDER BY DESC(?procedure_year)";
        $results = SPARQL::runSPARQLQuery($query);

        $jsnResults = json_decode($results);
        $list_procedure_year = Array();
        if (isset($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                $procedure_year = Array($res->procedure_year->value, $res->number->value);
                array_push($list_procedure_year, $procedure_year);
            }
        }
        return $list_procedure_year;
    }

    /**
     * get list of thematics of existed acts
     * @param type $eli_lang_code: tree letters lang_code
     * @return list of thematics.
     */
    function get_list_topics($eli_lang_code) {
        $query = 'SELECT  DISTINCT ?topic_name WHERE {
                ?topic skos:prefLabel  ?topic_name. FILTER(LANG(?topic_name)=' + $eli_lang_code + ')}';
        $list_topics = [];
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $list_topics[] = $res->topic_name->value;
            }
        }
        return $list_topics;
    }

    /**
     * Gets a list all langues, in selecting langue name, two and tree letters lang code
     * @return the list of langues in json formats
     */
    static function get_list_all_langues() {
        $query = 'SELECT  DISTINCT ?lang_name ?lang_code ?eli_lang_code WHERE {
                    ?lang a skos:Concept.
                    ?lang skos:prefLabel ?lang_name.
                    ?lang at:op-code ?eli_lang_code.
                    ?lang at:op-mapped-code ?mapped_concept. 
                    ?mapped_concept dc:source "iso-639-1"^^rdfs:Literal.
                    ?mapped_concept at:legacy-code ?lang_code.}';

        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        return $jsnResults;
    }

    /**
     * Get a list of eurovoc domains
     * @return list of domain in json format
     */
    function get_domains() {
        $query = 'SELECT DISTINCT ?domain WHERE {?domain a eu:Domain.}';
        return json_decode(SPARQL::runSPARQLQuery($query));
    }

    /**
     * Get a list of eurovoc thesaurus
     * @return list of thesaruses in json format
     */
    function get_thesaruses() {
        $query = 'SELECT DISTINCT ?thesaurus WHERE {?thesaurus a skos:conceptScheme.}';
        return json_decode(SPARQL::runSPARQLQuery($query));
    }

    /**
     * Get a list of eurovoc thesaurus
     * @return list thesaruses in json format
     */
    function get_concepts() {
        $query = 'SELECT DISTINCT ?concept WHERE {?concept a cdm:concept_eurovoc.}';
        return json_decode(SPARQL::runSPARQLQuery($query));
    }

    /**
     * get relatd terms, narower terms, and related terms of narrower terms of a related terms
     * @param type $lang_code
     * @param type $related_term
     * @return list of answers in json format 
     */
    function getRelatedTermAndNarrowerNamesOfRelatedTerm($lang_code, $related_term) {
        $query = 'SELECT  DISTINCT ?related_term_1_name ?narrower_name WHERE {
                        ?related_term skos:prefLabel "' . $related_term . '"@' . $lang_code . '. 
                        {?related_term skos:related ?related_term_1. 
                        ?related_term_1 skos:prefLabel ?related_term_1_name. 
                        FILTER(LANG(?related_term_1_name)="' . $lang_code . '")
                        } UNION 
                        {?related_term ^skos:broader ?narrower. 
                        ?narrower skos:prefLabel ?narrower_name. 
                        FILTER(LANG(?narrower_name)="' . $lang_code . '")
                        }
                        }';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /*     * ************* Functions to communicate with the browser *************************************** 

     * *********************************************************************************************** */

    /**
     * This function return "true" if the input domain name has thesaurus, and "false" outherwise. 
     * @param type $lang_code: two letters code
     * @param type $domain_name
     * @return string boolean value
     */
    function hasChildThesaurusNames($lang_code, $domain_name) {
        $query = 'SELECT  DISTINCT ?thesaurus  WHERE { ?thesaurus eu:domain ?domain. ?domain skos:prefLabel  "' . $domain_name . '"@' . $lang_code . '. } limit 1';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults->results->bindings)) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * This function return "true" if the input thesaurus name has micor thesaurus, and "false" outherwise. 
     * @param type $lang_code: two letters 
     * @param type $thesaurus_name
     * @return string boolean value
     */
    function hasChildConceptNames($lang_code, $thesaurus_name) {
        $query = 'SELECT  DISTINCT ?concept  WHERE {?thesaurus skos:prefLabel "' . $thesaurus_name . '"@' . $lang_code . '. ?thesaurus skos:hasTopConcept ?concept.} limit 1';

        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults->results->bindings)) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * This function return "true" if the input micro thesaurus name has narrower terms or related terms, and "false" outherwise. 
     * @param type $lang_code
     * @param type $concept_name
     * @return string boolean value
     */
    function hasChildRelatedTermAndNarrowerNames($lang_code, $concept_name) {
        $query = 'SELECT  DISTINCT ?concept WHERE { ?concept skos:prefLabel "' . $concept_name . '"@' . $lang_code . '. {?concept skos:related ?related_term.} UNION {?concept ^skos:broader ?narrower.}}limit 1';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults->results->bindings)) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * This function return "true" if the input narrower name has related terms, and "false" outherwise. 
     * @param type $lang_code
     * @param type $narrower_name
     * @return string
     */
    function hasChildRelatedTermOfNarrowerNames($lang_code, $narrower_name) {
        $query = 'SELECT  DISTINCT ?related_term_narrower WHERE {?narrower skos:prefLabel "' . $narrower_name . '"@' . $lang_code . '. ?narrower skos:related ?related_term_narrower.} limit 1';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults->results->bindings)) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * get list of domain in  a specific language
     * @param type $lang_code
     * @return type
     */
    function getDomainNames($lang_code) {
        $query = 'SELECT  DISTINCT ?domain ?domain_name  WHERE { ?thesaurus eu:domain ?domain. ?domain skos:prefLabel  ?domain_name. FILTER (LANG(?domain_name)="' . $lang_code . '")}';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * get list of thesaurus of a specific domain names
     * @param type $lang_code
     * @param type $domain_name
     * @return json format answer
     */
    function getThesaurusNames($lang_code, $domain_name) {
        $query = 'SELECT  DISTINCT ?domain ?thesaurus ?thesaurus_name  WHERE { ?thesaurus eu:domain ?domain. ?domain skos:prefLabel  "' . $domain_name . '"@' . $lang_code . '. ?thesaurus skos:prefLabel ?thesaurus_name. FILTER(LANG(?thesaurus_name)="' . $lang_code . '")}';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * get list of micro thesarus of a specific thesaurus name
     * @param type $lang_code
     * @param type $thesaurus_name
     * @return json format answers
     */
    function getConceptNames($lang_code, $thesaurus_name) {
        $query = 'SELECT  DISTINCT ?thesaurus ?concept ?concept_name  WHERE {?thesaurus skos:prefLabel "' . $thesaurus_name . '"@' . $lang_code . '. ?thesaurus skos:hasTopConcept ?concept. ?concept skos:prefLabel ?concept_name. FILTER (LANG(?concept_name)="' . $lang_code . '")}';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * get related terms and narrower terms of a micro thesaususe
     * @param type $lang_code
     * @param type $concept_name
     * @return json format answers
     */
    function getRelatedTermAndNarrowerNames($lang_code, $concept_name) {
        $query = 'SELECT  DISTINCT ?related_term_name ?narrower_name WHERE { ?concept skos:prefLabel "' . $concept_name . '"@' . $lang_code . '. {?concept skos:related ?related_term. ?related_term skos:prefLabel ?related_term_name. FILTER(LANG(?related_term_name)="' . $lang_code . '")} UNION {?concept ^skos:broader ?narrower. ?narrower skos:prefLabel ?narrower_name. FILTER(LANG(?narrower_name)="' . $lang_code . '")}}';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * get narrower terms of a specific micro thesausrus
     * @param type $lang_code
     * @param type $concept_name
     * @return json format answers
     */
    function getNarrowerNames($lang_code, $concept_name) {
        $query = 'SELECT  DISTINCT ?narrower_name WHERE { ?concept skos:prefLabel "' . $concept_name . '"@' . $lang_code . '. 
            ?concept ^skos:broader ?narrower. ?narrower skos:prefLabel ?narrower_name. FILTER(LANG(?narrower_name)="' . $lang_code . '")}';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * get related term of narrower name
     * @param type $lang_code
     * @param type $narrower_name
     * @return json format answers
     */
    function getRelatedTermOfNarrowerNames($lang_code, $narrower_name) {
        $query = 'SELECT  DISTINCT ?related_term_narrower_name WHERE {?narrower skos:prefLabel "' . $narrower_name . '"@' . $lang_code . '. ?narrower skos:related ?related_term_narrower. ?related_term_narrower skos:prefLabel ?related_term_narrower_name. FILTER(LANG(?related_term_narrower_name)="' . $lang_code . '")}';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * get related terms of micro thesaurus
     * @param type $lang_code
     * @param type $concept_name
     * @return json format answers
     */
    function getRelatedTerm($lang_code, $concept_name) {
        $query = 'SELECT  DISTINCT ?related_term_name WHERE {?concept skos:prefLabel "' . $concept_name . '"@' . $lang_code . '. 
                ?concept skos:related ?related_term. ?related_term skos:prefLabel ?related_term_name. 
                FILTER(LANG(?related_term_name)="' . $lang_code . '")}';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * get narrower terms of a specific narrower terms
     * @param type $lang_code
     * @param type $narrower_name
     * @return json format answers
     */
    function getNarrowerNamesOfNarrowerNames($lang_code, $narrower_name) {
        $query = 'SELECT  DISTINCT ?narrower_of_narrower_name WHERE {?narrower skos:prefLabel "' . $narrower_name . '"@' . $lang_code . '. 
            ?narrower ^skos:broader ?narrower_of_narrower. ?narrower_of_narrower skos:prefLabel ?narrower_of_narrower_name. FILTER(LANG(?narrower_of_narrower_name)="' . $lang_code . '")
            }';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * This function return "true" if the input narrower name has narrower terms, and "false" outherwise. 
     * @param type $lang_code
     * @param type $narrower_name
     * @return string
     */
    function hasChildNarrowerTerm($lang_code, $narrower_name) {
        $query = 'SELECT  DISTINCT ?narrower_of_narrower_name WHERE {?narrower skos:prefLabel "' . $narrower_name . '"@' . $lang_code . '. 
              ?narrower ^skos:broader ?narrower_of_narrower. ?narrower_of_narrower skos:prefLabel ?narrower_of_narrower_name. } limit 1';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults->results->bindings)) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * select meta data of all european langues
     * @return json format
     */
    function select_meta_data_4_all_lang() {
        $query = 'SELECT ?uri_lang ?name_langue ?eli_lang_code ?code_lang 
                                WHERE {
                                ?uri_lang  skos:prefLabel ?name_langue.
                                ?uri_lang  at:op-code ?eli_lang_code.
                                ?uri_lang at:op-mapped-code ?o.
                                ?o at:legacy-code ?code_lang. }';
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * select meta data of  a specific langue
     * @param type $lang_code
     * @return type json format
     */
    function select_meta_data_4_lang($lang_code) {
        $query = 'SELECT ?uri_lang ?name_langue ?eli_lang_code
                                WHERE {
                                ?uri_lang  skos:prefLabel ?name_langue.
                                ?uri_lang  at:op-code ?eli_lang_code.
                                ?uri_lang at:op-mapped-code ?o.
                                ?o at:legacy-code "' . $lang_code . '"^^<http://www.w3.org/2000/01/rdf-schema#Literal>. }';

        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /*     * ******* 
     * Create rdf file of instance to upload at virtuoso.
      This function can be adapted to another metadata of triple store, like metadata of COM docment.
     */

    function retrieve_metadata_eurovoc() {

        $content = '<?xml version="1.0" encoding="UTF-8"?>
    <rdf:RDF						
    xmlns:eu="http://eurovoc.europa.eu/schema#"
    xmlns:euvoc="http://publications.europa.eu/ontology/euvoc#"
    xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
    xmlns:foaf="http://xmlns.com/foaf/0.1/"
    xmlns:owl="http://www.w3.org/2002/07/owl#"
    xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
    xmlns:lodep="https://webgate.acceptance.ec.testa.eu/eparticipation/ontologies/LOD_Eparticipation/"
    xmlns:cdm="http://publications.europa.eu/ontology/cdm#"
    xmlns:skos="http://www.w3.org/2004/02/skos/core#">
    ';
        file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);

        $jsnResults = get_list_all_langues();
        $langs = [];
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $key = array_search($res->lang_code->value, $langs);
                if ($key === false) {
                    retrieve_eurovoc_by_lang($res->lang_code->value);
                    echo $res->lang_code->value . ' eurovoc concept are retrieved....  
                                    ';
                }
            }
        }
        //retrieve_eurovoc_by_lang('en');

        $content = '</rdf:RDF>';
        file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);
        echo 'End process ....';
    }

    function retrieve_eurovoc_by_lang($lang) {
        $prefix = 'PREFIX cdm: <http://publications.europa.eu/ontology/cdm#>
                            PREFIX skos: <http://www.w3.org/2004/02/skos/core#> 
                            PREFIX eurovoc: <http://eurovoc.europa.eu/schema#>';
        $select_1 = 'SELECT  distinct ?domain ?domain_name ?thesaurus ?thesaurus_name ?concept';
        $select_2 = 'SELECT  distinct ?concept ?concept_name ?related_concept ?related_concept_name ?narrower_concept ';
        $select_3 = 'SELECT  distinct ?narrower_concept ?narrower_concept_name ?related_narrower';
        $select_4 = 'SELECT  distinct ?related_narrower  ?related_narrower_name';
        $where = 'WHERE {
                            {?thesaurus eurovoc:domain ?domain. ?domain skos:prefLabel  ?domain_name.  filter(LANG(?domain_name)="' . $lang . '")                      #?domain_name, ex. 04 politics
                            ?thesaurus skos:prefLabel  ?thesaurus_name.  filter(LANG(?thesaurus_name)="' . $lang . '")                                                     # thesaurus_name, ex. 0406 political framwork
                            ?thesaurus skos:hasTopConcept ?concept.?concept skos:prefLabel  ?concept_name.  filter(LANG(?concept_name)="' . $lang . '")                # concept_name, ex. political ideology
                            ?concept skos:related ?related_concept. ?related_concept skos:prefLabel ?related_concept_name. filter(LANG(?related_concept_name)="' . $lang . '")    }              #related_concept_name, ex. political affiliation
                            UNION
                            {
                            ?thesaurus eurovoc:domain ?domain. ?domain skos:prefLabel  ?domain_name.  filter(LANG(?domain_name)="' . $lang . '")                       
                            ?thesaurus skos:prefLabel  ?thesaurus_name.  filter(LANG(?thesaurus_name)="' . $lang . '")                                                     
                            ?thesaurus skos:hasTopConcept ?concept.?concept skos:prefLabel  ?concept_name.  filter(LANG(?concept_name)="' . $lang . '")                
                            OPTIONAL{?narrower_concept skos:broader ?concept. ?narrower_concept skos:prefLabel ?narrower_concept_name. filter(LANG(?narrower_concept_name)="' . $lang . '")}   #narrower_concept_name, ex. communalism 
                            OPTIONAL{?narrower_concept skos:related ?related_narrower. ?related_narrower skos:prefLabel ?related_narrower_name. filter(LANG(?related_narrower_name)="' . $lang . '")} }   #related_narrower_name, ex. national identity
                            } order by ASC(?domain_name) (?thesaurus_name) (?concept_name)';

        $query_1 = $prefix . $select_1 . $where;
        $query_2 = $prefix . $select_2 . $where;
        $query_3 = $prefix . $select_3 . $where;
        $query_4 = $prefix . $select_4 . $where;

        $endpoint = 'http://publications.europa.eu/webapi/rdf/sparql';

        $results = runSPARQLQuery($endpoint, $query_1);
        $jsnResults = json_decode($results);
        $eli_lang_code = get_eli_lang_code($lang);

        if (!empty($jsnResults->results->bindings)) {
            foreach ($jsnResults->results->bindings as $res) {
                $domain = $res->domain->value;
                $thesaurus = $res->thesaurus->value;
                $concept = $res->concept->value;

                if (!empty($domain)) {
                    $content = '<rdf:Description rdf:about="' . $domain . '">
    <rdf:type rdf:resource="http://publications.europa.eu/ontology/euvoc#DomainEurovoc"/>        
    <skos:prefLabel>' . $res->domain_name->value . '@' . $eli_lang_code . '</skos:prefLabel>					
    </rdf:Description>';

                    file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);
                }

                if (!empty($thesaurus)) {
                    $content = '<rdf:Description rdf:about="' . $thesaurus . '">
    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#conceptSheme"/>												
    <euvoc:domain rdf:resource="' . $domain . '"/>
    <skos:prefLabel>' . $res->thesaurus_name->value . '@' . $eli_lang_code . '</skos:prefLabel>
    <skos:hasTopConcept rdf:resource="' . $concept . '"/>   
    </rdf:Description>';

                    file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);
                }
                $domain = "";
                $thesaurus = "";
                $concept = "";
            }
        }

        $results = runSPARQLQuery($endpoint, $query_2);
        $jsnResults = json_decode($results);
        $eli_lang_code = get_eli_lang_code($lang);

        if (!empty($jsnResults->results->bindings)) {
            foreach ($jsnResults->results->bindings as $res) {
                $concept = $res->concept->value;
                $related_concept = "";
                $narrower_concept = "";
                if (!empty($res->related_concept)) {
                    $related_concept = $res->related_concept->value;
                }
                if (!empty($res->narrower_concept)) {
                    $narrower_concept = $res->narrower_concept->value;
                }

                if (!empty($concept)) {
                    $content = '<rdf:Description rdf:about="' . $concept . '">
    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>  
    <skos:prefLabel>' . $res->concept_name->value . '@' . $eli_lang_code . '</skos:prefLabel>';
                    if (!empty($related_concept)) {
                        $content.='
    <skos:related rdf:resource="' . $related_concept . '"/>';
                    } if (!empty($narrower_concept)) {
                        $content.='
    <^skos:broader rdf:resource="' . $narrower_concept . '"/>';
                    } $content.='
    </rdf:Description>';

                    file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);
                }

                if (!empty($related_concept)) {
                    $related_concept_name = "";
                    if (!empty($res->related_concept_name)) {
                        $related_concept_name = $res->related_concept_name->value;
                    }
                    $content = '<rdf:Description rdf:about="' . $related_concept . '">
    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>
    <skos:prefLabel>' . $related_concept_name . '@' . $eli_lang_code . '</skos:prefLabel>
    </rdf:Description>';

                    file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);
                }
                $concept = "";
                //$related_concept ="";
                //$narrower_concept ="";	
            }
        }

        $results = runSPARQLQuery($endpoint, $query_3);
        $jsnResults = json_decode($results);
        $eli_lang_code = get_eli_lang_code($lang);

        if (!empty($jsnResults->results->bindings)) {
            foreach ($jsnResults->results->bindings as $res) {

                //$concept =$res->concept->value;
                $narrower_concept = "";
                $related_narrower = "";
                if (!empty($res->narrower_concept)) {
                    $narrower_concept = $res->narrower_concept->value;
                }
                if (!empty($res->related_narrower)) {
                    $related_narrower = $res->related_narrower->value;
                }

                if (!empty($narrower_concept)) {
                    $narrower_concept_name = "";
                    if (!empty($res->narrower_concept_name)) {
                        $narrower_concept_name = $res->narrower_concept_name->value;
                    }
                    $content = '<rdf:Description rdf:about="' . $narrower_concept . '">
    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>';
                    if (!empty($narrower_concept_name)) {
                        $content.='
    <skos:prefLabel>' . $narrower_concept_name . '@' . $eli_lang_code . '</skos:prefLabel>';
                    } if (!empty($related_narrower)) {
                        $content.='
    <skos:related rdf:resource="' . $related_narrower . '"/>';
                    } $content.='
    </rdf:Description>';

                    file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);
                }
            }
        }
        $results = runSPARQLQuery($endpoint, $query_4);
        $jsnResults = json_decode($results);
        $eli_lang_code = get_eli_lang_code($lang);

        if (!empty($jsnResults->results->bindings)) {
            foreach ($jsnResults->results->bindings as $res) {
                $related_narrower = "";
                if (!empty($res->related_narrower)) {
                    $related_narrower = $res->related_narrower->value;
                }

                if (!empty($related_narrower)) {
                    $related_narrower_name = "";
                    if (!empty($res->related_narrower_name)) {
                        $related_narrower_name = $res->related_narrower_name->value;
                    }
                    $content = '<rdf:Description rdf:about="' . $related_narrower . '">
    <rdf:type rdf:resource="http://www.w3.org/2004/02/skos/core#Concept"/>';
                    if (!empty($narrower_concept_name)) {
                        $content.='
    <skos:prefLabel>' . $related_narrower_name . '@' . $eli_lang_code . '</skos:prefLabel>';
                    }$content.='
    </rdf:Description>';

                    file_put_contents('eurovoc_lodep.rdf', $content . "  \r\n", 8);
                }
            }
        }
    }

    function runSPARQLQuery($endpoint, $query) {
        $format = 'json';
        $url = $endpoint . '?query=' . urlencode($query);

        if ($format != '') {
            $url = $url . '&format=' . $format;
        }

        // echo $url . PHP_EOL . PHP_EOL;
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

        return $response;
    }

    /**
     * get hashtags
     * @return the list in json format
     */
    public function getHashtags(Request $request) {
        $start = $request->get('start');
        if (substr($start, 0, 1) === '#') {
            $start = substr($start, 1);
        }
        $query = 'SELECT ?comment WHERE {?post sioc:has_container ?container. ?post sioc:content ?comment.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $hashtags = array();
        if (!empty($jsnResults)) {
            foreach ($jsnResults->results->bindings as $res) {
                $matches = array();
                preg_match_all('/#(\w+)/u', $res->comment->value, $matches);
                foreach ($matches[1] as $match) {
                    if (!in_array('#' . strtolower($match), $hashtags)) {
                        if (empty($start)) {
                            array_push($hashtags, strtolower($this->removeAccents('#' . $match)));
                        } else if (strpos(strtolower($match), strtolower($start)) === 0) {
                            array_push($hashtags, strtolower($this->removeAccents('#' . $match)));
                        }
                    }
                    if (sizeof($hashtags) > 4)
                        break 2;
                }
            }
        }
        return \Response::json($hashtags);
    }

    /**
     * get authors
     * @return the list in json format
     */
    public function getAuthors(Request $request) {
        $name = $request->get('name');
        $query = "SELECT DISTINCT concat(?firstName,' ',?lastName) as ?name WHERE { ?user a sioc:UserAccount; foaf:name ?firstName; foaf:familyName ?lastName. FILTER(REGEX(lcase(concat(?firstName,' ',?lastName)),lcase('" . $name . "')) OR (REGEX(lcase(concat(?lastName,' ',?firstName)),lcase('" . $name . "'))))}";
        return \Response::json(json_decode(SPARQL::runSPARQLQuery($query)));
    }

    /**
     * Remove accents in a string
     * @param type $str
     * @param type $encoding
     * @return type
     */
    function removeAccents($str, $encoding = 'utf-8') {
        $str = htmlentities($str, ENT_NOQUOTES, $encoding);
        $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);
        return $str;
    }

}
