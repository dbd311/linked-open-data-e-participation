<?php

namespace App\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use SPARQL;
use App\Http\Controllers\MetadataController;
use App\Concepts\Act;

/**
 * Description of Document, each of which has a unique URI
 *
 * @author Duy Dinh <dinhbaduy@gmail.com>
 * @date 03 May 2016
 */
class Document extends Model {

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'documents';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['docno', 'title', 'path', 'filename'];

    /*     * *
     * Get procedure type from CELLAR
     */

    public static function get_procedure_type($id_comnat) {

        $query = 'PREFIX cdm: <http://publications.europa.eu/ontology/cdm#>  PREFIX at: <http://publications.europa.eu/ontology/authority/> SELECT DISTINCT ?procedure_type  WHERE {?act cdm:work_id_document "' . $id_comnat . '"^^xsd:string. ?dossier cdm:dossier_contains_work ?act. ?dossier cdm:procedure_code_interinstitutional_has_type_concept_type_procedure_code_interinstitutional ?procedure_type.}';

        $endpoint = Config::get('app.cellar_sparql_endpoint'); // cellar
        return SPARQL::retrieveLinkedData($endpoint, $query);
    }

    public static function add_label_procedureType($actID) {
        $results = Document::retriveLabel_procedureType($actID);
        $jsnResults = json_decode($results);

        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> {';
        if (!empty($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                $eli_lang_code = MetadataController::get_eli_lang_code($res->lang_PT->value);
                if (!empty($eli_lang_code)) {
                    $query.= '<' . $res->procedure_type->value . '> rdfs:label "' . $res->label_PT->value . '"@' . $eli_lang_code . '.';
                }
            }
        }
        $query.='}';
        SPARQL::runSPARQLUpdateQuery($query);
    }

    /**
     * Retrieve metadata from the CELLAR
     * @param type $actID
     * @return type
     */
    public static function retriveMetaData($actID) {

        $query = 'PREFIX cdm: <http://publications.europa.eu/ontology/cdm#> PREFIX at: <http://publications.europa.eu/ontology/authority/>
                              
        SELECT DISTINCT ?id_celex  ?num_celex ?date_adopted  ?topic ?doc_type ?doc_code ?directory_code  ?procedure_type ?procedure_num  ?procedure_code ?procedure_year WHERE {
        ?act cdm:work_id_document "' . $actID . '"^^xsd:string.
        ?act owl:sameAs ?id_celex. FILTER(regex (?id_celex, "celex")) 
        ?act cdm:work_date_document ?date_adopted.
        ?act cdm:work_is_about_concept_eurovoc ?topic.
        ?act cdm:resource_legal_number_natural_celex ?num_celex.# data property at the level of legal resource 
        ?act cdm:resource_legal_is_about_concept_directory-code ?directory_code.
        ?act cdm:work_has_resource-type ?doc_type.
        ?doc_type at:authority-code ?doc_code.
        ?dossier cdm:dossier_contains_work ?act.
        ?dossier cdm:procedure_code_interinstitutional_reference_procedure ?procedure_code.
        ?dossier cdm:procedure_code_interinstitutional_has_type_concept_type_procedure_code_interinstitutional ?procedure_type.
        ?dossier cdm:procedure_code_interinstitutional_number_procedure ?procedure_num.
        ?dossier cdm:procedure_code_interinstitutional_year_procedure ?procedure_year.
        }';

//        error_log($query);
        $endpoint = Config::get('app.cellar_sparql_endpoint'); // cellar
        return SPARQL::retrieveLinkedData($endpoint, $query);
    }

    /**
     * Retrieve topics from the CELLAR
     * @param type $actID
     * @return type
     */
    public static function retriveLabelTopic($actID) {

        $query = 'PREFIX cdm: <http://publications.europa.eu/ontology/cdm#> PREFIX skos: <http://www.w3.org/2004/02/skos/core#> PREFIX lang:<http://publications.europa.eu/resource/authority/language/>
	SELECT distinct ?topic ?label LANG(?label) as ?lang  WHERE {?act cdm:work_id_document "' . $actID . '"^^xsd:string; cdm:work_is_about_concept_eurovoc ?topic. ?topic skos:prefLabel ?label.	} ORDER BY ?topic';

        return SPARQL::retrieveLinkedData(Config::get('app.cellar_sparql_endpoint'), $query);
    }

    /**
     * Add topics to triple store
     * @param type $actID
     * @param type $genericAct
     */
    public static function add_label_topic($actID, $genericAct) {
        $results = Document::retriveLabelTopic($actID);
        $jsnResults = json_decode($results);

        $query = '';
        $topic = '';
        if (!empty($jsnResults->results)) {
            foreach ($jsnResults->results->bindings as $res) {
                $eli_lang_code = MetadataController::get_eli_lang_code($res->lang->value);
                if ($topic != $res->topic->value) {
                    if ($query != '') {
                        $query.='.}';
                        \SPARQL::runSPARQLUpdateQuery($query);
                    }

                    $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> {';
                    $topic = $res->topic->value;
                    $query.= '<' . $genericAct . '> lodep:topic <' . $topic . '> ';

                    if (!empty($eli_lang_code)) {
                        $query.= '. <' . $topic . '> skos:prefLabel "' . $res->label->value . '"@' . $eli_lang_code;
                    }
                } else {

                    if (!empty($eli_lang_code)) {
                        $query.= '; skos:prefLabel "' . $res->label->value . '"@' . $eli_lang_code;
                    }
                }
            }
        }
    }

    public static function documentExists($actURI) {
        $query = 'SELECT ?id WHERE {<' . $actURI . '> sioc:id ?id}';
        $jsnResults = json_decode(SPARQL::runSPARQLQuery($query));
        if (!empty($jsnResults->results)) {
            if (!empty($jsnResults->results->bindings)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add a document to triple store
     * @param string $title
     * @param string  $actID
     * @param string  $eli_lang_code
     * @param string $site
     * @param string $space
     * @param string year
     * @param string num
     * @return the URI of the added document
     */
    public static function addDocument($title, $subject, $actID, $eli_lang_code, $site, $space, $year, $num) {
        $results = Document::retriveMetaData($actID);
        $jsnResults = json_decode($results);
        $doc_code = null;
        // extract doc code
        if (!empty($jsnResults->results)) {
            // since there are probably several topics per act, we need to loop over the results
            foreach ($jsnResults->results->bindings as $res) {
                if (empty($doc_code)) {
                    $doc_code = $res->doc_code->value;
                }
            }
        }

        if (empty($doc_code)) {
            $doc_code = 'NA';
        }
        $actURI = Act::buil_ELI_URI_act($doc_code, $year, $num, $eli_lang_code);
        if (self::documentExists($actURI)) {
            return null;
        }

        $normTitle = preg_replace('/(\r\n|\r|\n)/', ' ', preg_replace('/"/', '\"', trim($title)));

        $normSubject = preg_replace('/(\r\n|\r|\n)/', ' ', preg_replace('/"/', '\"', trim($subject)));

        // execute several insert queries for the act and update its generic container
        $genericAct = substr($actURI, 0, strlen($actURI) - 4); // act without language
        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '>{<' . $actURI . '>   a sioc:Forum;sioc:id "' . $actID . '"^^rdfs:Literal;lodep:title "' . $normTitle . '"@' . $eli_lang_code . ';lodep:subject "' . $normSubject . '"@' . $eli_lang_code . ';lodep:num_items_total 0;lodep:num_items_yes 0;lodep:num_items_no 0;lodep:num_items_mixed 0;lodep:num_items_total_na 0;lodep:num_items_yes_na 0;lodep:num_items_no_na 0;lodep:num_items_mixed_na 0;sioc:has_host <' . $site . '> ;sioc:has_space <' . $space . '>.}';
        SPARQL::runSPARQLUpdateQuery($query);

        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '>{<' . $genericAct . '> a sioc:Forum;sioc:id "' . $actID . '"^^rdfs:Literal;lodep:title "' . $normTitle . '"@' . $eli_lang_code . ';lodep:subject "' . $normSubject . '"@' . $eli_lang_code . ';lodep:num_items_total 0;lodep:num_items_yes 0;lodep:num_items_no 0;lodep:num_items_mixed 0;lodep:num_items_total_na 0;lodep:num_items_yes_na 0;lodep:num_items_no_na 0;lodep:num_items_mixed_na 0;sioc:has_host <' . $site . '>.<' . $actURI . '> sioc:has_parent <' . $genericAct . '>.}';
        SPARQL::runSPARQLUpdateQuery($query);

        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '>{';

        $id_celex = null;
        $date_adopted = null;
        $procedure_type = null;
        $procedure_num = null;
        $procedure_code = null;
        $directory_code = null;

        $procedure_year = null;

        if (!empty($jsnResults->results)) {
            // since there are probably several topics per act, we need to loop over the results
            foreach ($jsnResults->results->bindings as $res) {
                $query .= '<' . $actURI . '> lodep:topic <' . $res->topic->value . '>. ';

                // however we only need the remainder variables once since they are the same for each act
                if (empty($date_adopted)) {
                    if (!empty($res->date_adopted)) {
                        $date_adopted = $res->date_adopted->value;
                    }
                }
                if (empty($procedure_num)) {
                    $procedure_num = $res->procedure_num->value;
                }
                if (empty($procedure_type)) {
                    $procedure_type = $res->procedure_type->value;
                }
                if (empty($procedure_code)) {
                    $procedure_code = $res->procedure_code->value;
                }
                if (empty($directory_code)) {
                    $directory_code = $res->directory_code->value;
                }
                if (empty($doc_code)) {
                    $doc_code = $res->doc_code->value;
                }
                if (empty($id_celex)) {
                    $id_celex = str_replace('http://publications.europa.eu/resource/celex/', '', $res->id_celex->value);
                }

                if (empty($procedure_year)) {
                    $procedure_year = $res->procedure_year->value;
//                    error_log('Procedure year is ' . $procedure_year . ' (' . $actURI . ')');
                    if (empty($procedure_year)) {
                        error_log('Oups ... The procedure year is not specified for document ' . $actURI);
                    }
                }
            }

            if (!empty($date_adopted)) {
                $query .= '<' . $actURI . '> lodep:created_at "' . $date_adopted . '"^^xsd:dateTime.
                <' . $genericAct . '> lodep:created_at "' . $date_adopted . '"^^xsd:dateTime.';
            }
            if (!empty($procedure_type)) {
                $query .= '<' . $actURI . '> lodep:procedure_type <' . $procedure_type . '>.';
            }
            if (!empty($procedure_type)) {
                $query .= '<' . $genericAct . '> lodep:procedure_type <' . $procedure_type . '>.';
            }
            if (!empty($id_celex)) {
                $query .= '<' . $actURI . '> lodep:id_celex "' . $id_celex . '"^^xsd:string.                
                <' . $genericAct . '> lodep:id_celex "' . $id_celex . '"^^xsd:string.';
            }if (!empty($procedure_num)) {
                $query .= '<' . $actURI . '> lodep:procedure_number "' . $procedure_num . '"^^xsd:string.
                <' . $genericAct . '> lodep:procedure_number "' . $procedure_num . '"^^xsd:string.';
            }
            if (!empty($procedure_code)) {
                $query .= '<' . $actURI . '> lodep:procedure_code "' . $procedure_code . '"^^xsd:string.
                <' . $genericAct . '> lodep:procedure_code "' . $procedure_code . '"^^xsd:string.';
            }
            if (!empty($directory_code)) {
                $query .= '<' . $actURI . '> lodep:directory_code "' . $directory_code . '"^^xsd:string.
                <' . $genericAct . '> lodep:directory_code "' . $directory_code . '"^^xsd:string.';
            }
            if (!empty($doc_code)) {
                $query .= '<' . $actURI . '> lodep:doc_code "' . $doc_code . '"^^xsd:string.
                <' . $genericAct . '> lodep:doc_code "' . $doc_code . '"^^xsd:string.';
            }

            if (!empty($procedure_year)) {
                $query .= '<' . $actURI . '> lodep:procedure_year "' . $procedure_year . '"^^xsd:gYear.
                <' . $genericAct . '> lodep:procedure_year "' . $procedure_year . '"^^xsd:gYear.';
            } else {
                error_log('Procedure year is empty for act: "' . $actURI . '"');
                $query .= '<' . $actURI . '> lodep:procedure_year "' . $procedure_year . '"^^xsd:gYear.
                <' . $genericAct . '> lodep:procedure_year "' . $procedure_year . '"^^xsd:gYear.';
            }
        }

        $query.='
            }';

        SPARQL::runSPARQLUpdateQuery($query);

        return $actURI;
    }

    /**
     * Retrieve procedure type from the CELLAR
     * @param type $actID
     * @return type
     */
    public static function retriveLabel_procedureType($actID) {

        $query = 'PREFIX cdm: <http://publications.europa.eu/ontology/cdm#> 
            PREFIX skos: <http://www.w3.org/2004/02/skos/core#>            
            SELECT distinct ?procedure_type ?label_PT LANG(?label_PT) as ?lang_PT
            WHERE { ?act cdm:work_id_document "' . $actID . '"^^xsd:string. ?dossier cdm:dossier_contains_work ?act. ?dossier cdm:procedure_code_interinstitutional_has_type_concept_type_procedure_code_interinstitutional ?procedure_type. ?procedure_type skos:prefLabel ?label_PT.}';

        $endpoint = Config::get('app.cellar_sparql_endpoint'); // cellar
        return SPARQL::retrieveLinkedData($endpoint, $query);
    }

}
