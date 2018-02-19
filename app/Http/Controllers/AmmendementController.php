<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SPARQL;
use App\Concepts\Act;
use App\Concepts\Post;
use App\Concepts\Ammendement;
use App\Concepts\Filter;
use \App\Concepts\Container;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\UserController;

/**
 * This class is used for handling the user ammendment

 * * */
class AmmendementController extends Controller {

    // sparql endpoint
    protected $endpoint;

    public function __construct() {
        $this->endpoint = env('VIRTUOSO.SPARQL.ENDPOINT_SRV');
    }
    
    public function AmmendementController(){}

    /**
     * This function allows to store metadata of amendment, in computing the statistic.
     * @param Request $request: set of parameters fiven by the the front end
     * @param type $containerURI: the container, act, article, ...
     * @param type $post: the comment related to this amendment
     * @param type $eli_lang_code: the lang code of the text.
     */
    public function storeAmmendement(Request $request, $containerURI, $post, $eli_lang_code) {
        if (Session::get('user') == null) {
            return redirect()->to('/auth/login');
        }
        $user = UserController::buildUserURI(Session::get('user')->user_id->value);
      //  $eli_lang_code = $request->get('eli_lang_code');
        $modifications = $request->get('modifications');
        
        $newNodifications = [];
        for ($i = 0; $i < count($modifications); $i++){
            $begin = $modifications[$i]["begin"];
            $end= "";
            if (isset($modifications[$i]["end"])) $end = $modifications[$i]["end"];
            $begin2 = "";
            if (isset($modifications[$i + 1]["begin"])) $begin2 = $modifications[$i + 1]["begin"];
            $str2= "";
            if (isset($modifications[$i + 1]["str"])) $str2 = $modifications[$i + 1]["str"];
            if (isset($end) && $end + 2 == $begin2) {
                $newModification["begin"] = $begin;
                $newModification["end"] = $end;
                $newModification["str"] = $str2;
                array_push($newNodifications, $newModification);
                $i++;
            } else {
                array_push($newNodifications, $modifications[$i]);
            }
        }
        
        foreach ($newNodifications as $modification){
            $begin_position = $modification["begin"];
            $end_position = '';
            $amendment_type = 'insertion';
            $new_content = '';
            if(isset($modification["str"])){
                $new_content = $modification["str"];
                if(isset($modification["end"])){
                    $amendment_type = 'substitution';
                    $end_position = $modification["end"];
                }
            } else {
                $amendment_type = 'deletion';
                $end_position = $modification["end"];
            }
       // $containerURI = Container::get_URI_container($request->get('doc_code'), $request->get('year'), $request->get('num'), $request->get('id_fmx_element'), $eli_lang_code);
        $ammendmentURI = Ammendement::buildAmmendURIContainer(Session::get('user')->user_id->value, $containerURI, $amendment_type, $begin_position, $end_position);
        $this->add_ammend_container($ammendmentURI, $user, $containerURI, $amendment_type, "", $new_content, $begin_position, $end_position, $eli_lang_code, $post);
        }
    }
    /**
     * This fonction remove the amendment related to a specific comment.
     * @param type $post: the comment which we need to delet its amendment.
     */
    
    public function removeAmmendement($post) {
        if (Session::get('user') == null) {
            return redirect()->to('/auth/login');
        }
		$jsnResults = $this->get_amendment_list($post);
		if (!empty($jsnResults)) {
			foreach($jsnResults->results->bindings as $rs){
				$containerURI = $rs->container->value;
				$amendmentURI = $rs->amendment->value;
				$amendment_type = $rs->amendmentType->value;
				$l_amend_type = explode('/', $amendment_type);
				$this->delete_ammend_container($containerURI, $amendmentURI, strtolower(end($l_amend_type)));
			}
		}
    }
    /**
     * delete a specific amendment of an container in considering the updation of the statistic.
     * @param type $containerURI: the uri of the container
     * @param type $ammend: the uri of the amendment, which we need delete.
     * @param type $amendment_type: the type of the amendment: deletion, insertion, or substitution.
     */
    function delete_ammend_container($containerURI, $ammend, $amendment_type){ 
        // initliaze or increment the num_items of the current container and its ascendants
        $this->inverse_update_container_and_ascendants_amendment($containerURI, $amendment_type);
	$this->delete_amendment($ammend, $amendment_type);
        //$this->delete_amendment($ammend, $user, $containerURI, $amendment_type, $deleted_content, $new_content, $begin_position, $end_position, $eli_lang_code, $post);
    }

    /**
     * add a specific amendment for a specific container in considering the update of the statistic.
     * @param type $ammend: the uri of the amendment
     * @param type $user: the uri of the user, which add the amendment.
     * @param type $containerURI: the uri of the container.
     * @param type $amendment_type: the type of the amendment: deletion, insertion, substitution.
     * @param type $deleted_content: the deleted text in the casse of deletion or substitution.
     * @param type $new_content: the added text in the case of insertion or substitution.
     * @param type $begin_position: the begin position of the amendment
     * @param type $end_position: the end position of the amendement in the case of deletion or substitution
     * @param type $eli_lang_code: the langue code of the text
     * @param type $post: the comment uri associated to this amendment.
     */
     
    function add_ammend_container($ammend, $user, $containerURI, $amendment_type, $deleted_content, $new_content, $begin_position, $end_position, $eli_lang_code, $post) {
        // initliaze or increment the num_items of the current container and its ascendants
        $this->update_container_and_ascendants_amendment($containerURI, $amendment_type);
        $this->add_amendment($ammend, $user, $containerURI, $amendment_type, $deleted_content, $new_content, $begin_position, $end_position, $eli_lang_code, $post);
    }
	
    /**
     * The function is used when we add  the amendment in order to updates the statistic related to the amendment. 
     * @param type $containerURI: the uti of the amended container: chapter, article,...(the act can't be amended)
     * @param type $amendment_type: the type of the amendment: deletion, insertion, or substitution.
     */
    
    function update_container_and_ascendants_amendment($containerURI, $amendment_type) {
        // extract all ascendant's URIs of the given container at all levels up to the generic act 
        $sitename = env('SITE_NAME');

        $subdomain = substr_count($sitename, '/') > 2; // a subdomain URI contains more than 2 slashes
        // start and end position of a generic act
        $startPos = 0;
        $endPos = 6;
        if ($subdomain) {
            $pattern = "azertyuiopqsdfghjklmwxcvbn123456789";
            $tmpContainer = str_replace($sitename, $pattern, $containerURI);

            // extract the subdivision fields
            $fields = explode("/", $tmpContainer);
            // replace pattern to the original site name
           //if (strpos($fields[0], $pattern) !== false) {
                $fields[0] = str_replace($pattern, env('SITE_NAME'), $fields[0]);
            //}
            $startPos = 1;
            $endPos = 4;
        } else {
            $fields = explode("/", $containerURI);
        }

        $lang = end($fields); // eli lang code
        // build the generic container of containerURI
        $generic_containerURI = '';

        for ($i = $startPos; $i < $endPos; $i++) {
            $generic_containerURI .= $fields[$i] . '/';
        }

        // append the last component
        $generic_containerURI .= $fields[$endPos];

        // add this generic act and act@lang to the list of ascendants which represents the hiearchy of containers
        $ascendant_containers[] = $generic_containerURI;
        $ascendant_containers[] = $generic_containerURI . '/' . $lang;

        // We are now at the subdivision level: add more ascendant containers to the list (top-down)
        $curContainer = $generic_containerURI; // current container
        // build the ascendant containers starting from the generic container
        for ($i = $endPos + 1; $i < sizeOf($fields) - 1; $i++) {
            $curContainer .= '/' . $fields[$i];
            // add this ascendant container
            $ascendant_containers[] = $curContainer;
            $ascendant_containers[] = $curContainer . '/' . $lang;
        }
        // create a filter for all ascendants of the container.
        $filter_container = Filter::create_filter_ascendant_containers($ascendant_containers);

        /* This query allows to select the container and all its ascendants. 
		   For the container we select aggregated and non-aggregated num_items, 
		   but for its ascendants we select only the aggregated num_items. 
        */
        $query = 'SELECT DISTINCT ?container  ?amendment $amendment_type ?amendment_na $amendment_type_na
                WHERE { {
				 OPTIONAL{?container lodep:num_amendment ?amenedment.}
                  OPTIONAL{?container lodep:num_' . $amendment_type . ' ?amendment_type.}
				  OPTIONAL{?container lodep:num_amendment_na ?amendment_na.}
                  OPTIONAL{?container lodep:num_' . $amendment_type . '_na ?amendment_type_na.}
                  FILTER(?container=<' . $containerURI . '>)}
                      UNION 
                  {OPTIONAL{?container lodep:num_amendment ?amendment.}
                  OPTIONAL{?container lodep:num_' . $amendment_type . ' ?amendment_type.}' . $filter_container . ' }
                }';
        $results = SPARQL::runSPARQLQuery($query);
        // update the container and all ascendants of contrainer, their number ......
        $this->update_container_amendment($containerURI, $ascendant_containers, $results, $amendment_type);
    }

    /**
     * The function is used when we delete  the amendment in order to updates the statistic related to the amendment. 
     * @param type $containerURI: the uri of the container
     * @param type $amendment_type: the type of the amendment: deletion, insertion, substitution.
     */	
    function inverse_update_container_and_ascendants_amendment($containerURI, $amendment_type) {
        // extract all ascendant's URIs of the given container at all levels up to the generic act 
        $sitename = env('SITE_NAME');

        $subdomain = substr_count($sitename, '/') > 2; // a subdomain URI contains more than 2 slashes
        // start and end position of a generic act
        $startPos = 0;
        $endPos = 6;
        if ($subdomain) {
            $pattern = "azertyuiopqsdfghjklmwxcvbn123456789";
            $tmpContainer = str_replace($sitename, $pattern, $containerURI);

            // extract the subdivision fields
            $fields = explode("/", $tmpContainer);
            // replace pattern to the original site name
           //if (strpos($fields[0], $pattern) !== false) {
                $fields[0] = str_replace($pattern, env('SITE_NAME'), $fields[0]);
            //}
            $startPos = 1;
            $endPos = 4;
        } else {
            $fields = explode("/", $containerURI);
        }

        $lang = end($fields); // eli lang code
        // build the generic container of containerURI
        $generic_containerURI = '';

        for ($i = $startPos; $i < $endPos; $i++) {
            $generic_containerURI .= $fields[$i] . '/';
        }

        // append the last component
        $generic_containerURI .= $fields[$endPos];

        // add this generic act and act@lang to the list of ascendants which represents the hiearchy of containers
        $ascendant_containers[] = $generic_containerURI;
        $ascendant_containers[] = $generic_containerURI . '/' . $lang;

        // We are now at the subdivision level: add more ascendant containers to the list (top-down)
        $curContainer = $generic_containerURI; // current container
        // build the ascendant containers starting from the generic container
        for ($i = $endPos + 1; $i < sizeOf($fields) - 1; $i++) {
            $curContainer .= '/' . $fields[$i];
            // add this ascendant container
            $ascendant_containers[] = $curContainer;
            $ascendant_containers[] = $curContainer . '/' . $lang;
        }
        // create a filter for all ascendants of the container.
        $filter_container = Filter::create_filter_ascendant_containers($ascendant_containers);

        /* This query allows to select the container and all its ascendants. 
		   For the container we select aggregated and non-aggregated num_items, 
		   but for its ascendants we select only the aggregated num_items. 
        */
        $query = 'SELECT DISTINCT ?container  ?amendment $amendment_type ?amendment_na $amendment_type_na
                WHERE { {
				 OPTIONAL{?container lodep:num_amendment ?amenedment.}
                  OPTIONAL{?container lodep:num_' . $amendment_type . ' ?amendment_type.}
				  OPTIONAL{?container lodep:num_amendment_na ?amendment_na.}
                  OPTIONAL{?container lodep:num_' . $amendment_type . '_na ?amendment_type_na.}
                  FILTER(?container=<' . $containerURI . '>)}
                      UNION 
                  {OPTIONAL{?container lodep:num_amendment ?amendment.}
                  OPTIONAL{?container lodep:num_' . $amendment_type . ' ?amendment_type.}' . $filter_container . ' }
                }';
        $results = SPARQL::runSPARQLQuery($query);
        // update the container and all ascendants of contrainer, their number ......
        $this->inverse_update_container_amendment($containerURI, $ascendant_containers, $results, $amendment_type);
    }
    /**
     * get the number of amendment, in concedering all subdevision: total, insertion deletion, substitution.
     * @param type $container: the uri of the conatiner.
     * @return type $sparql_result: the result of the query, which select: num_amen, num_deletion, num_insertion, and num_substitution
     */
    function getNumberAmmendment($container){
        $query='SELECT DISTINCT ?num_amen ?num_deletion ?num_insertion ?num_substitution
                WHERE {<'.$container.'> lodep:num_amendment ?num_amen; lodep:num_deletion ?num_deletion; lodep:num_substitution ?num_substitution; lodep:num_insertion ?num_insertion. }';
        $results = SPARQL::runSPARQLQuery($query);
	return  $results; 
    }
    /**
     * get number of amendment realted to a specific post
     * @param type $postUri: the uri of the specific post
     * @return type
     */
    function getNumberAmmendmentOfPost($postUri){				
		$query='SELECT ?post COUNT(?insertion) as ?num_insertion COUNT(?deletion) as ?num_deletion COUNT(?substitution) as ?num_substitution   
				WHERE{
				{?insertion  lodep:contained <'.$postUri.'>.
				?insertion a prv:Insertion.}
				UNION
				{?deletion  lodep:contained <'.$postUri.'>.
				?deletion  a prv:Deletion.}
				UNION
				{?substitution lodep:contained <'.$postUri.'>.
				?substitution a prv:Substitution.}
				}';
        $results = SPARQL::runSPARQLQuery($query);
		return  $results; 
    }
    /**
     * When we add an amendment, this function update the statistic of the amendment: the container and its parents 
     * @param type $containerURI: the container of the uri
     * @param array $containers: the list container uri, whose are the parent of the amended container
     * @param type $results: the result of sarql query, which select the properties of stattistics
     * @param type $amendment_type: deletion, insertion, or substitution.
     */

    function update_container_amendment($containerURI, $containers, $results, $amendment_type) {
        // add the leave to the list of containers, which represents the complete hiearchy of containers from the leave to 
        // the root (top-down)
        $containers[] = $containerURI;
        $jsnResults = json_decode($results);

        if (!empty($jsnResults->results)) {
            // increments num_items of each container in the SPARQL results
            foreach ($jsnResults->results->bindings as $result) {//?total ?totalna ?note ?notena 
                $query = 'WITH <' . env('LOD_GRAPH') . '> DELETE  {';

                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment ?amendment. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . ' ?amendment_type. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment_na ?amendment_na. ';
                }

                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . '_na ?amendment_type_na. ';
                }
                $query .= '} ';
                $query .= 'INSERT {';
                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment ?new_amendment. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . ' ?new_amendment_type. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment_na ?new_amendment_na. ';
                }

                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . '_na ?new_amendment_type_na. ';
                }
                $query .= '} ';


                $query .='WHERE {
				OPTIONAL{ <' . $result->container->value . '> lodep:num_amendment ?amendment. BIND ((?amendment + 1) AS ?new_amendment)}
	                       OPTIONAL{ <' . $result->container->value . '> lodep:num_' . $amendment_type . ' ?amendment_type. BIND ((?amendment_type + 1) AS ?new_amendment_type)}
				OPTIONAL{<' . $result->container->value . '> lodep:num_amendment_na ?amendment. BIND ((?amendment_na + 1) AS ?new_amendment_na)}
	                        OPTIONAL{<' . $result->container->value . '> lodep:num_' . $amendment_type . '_na ?amendment_type_na. BIND ((?amendment_type_na + 1) AS ?new_amendment_type_na)}
                            }';
                SPARQL::runSPARQLUpdateQuery($query);
            }

            // processing ascendant containers
            for ($i = 0; $i < sizeof($containers); $i++) {
                $container = $containers[$i];
                $containerExists = false;
                $containerTypeExists = false;

                foreach ($jsnResults->results->bindings as $result) {
                    // if container exists in the SPARQL results
                    if ($container === $result->container->value) {
                        $containerExists = true;
                        // if the note of the container is the same as the note of the container in the SPARQL result
                        if (!isset($result->note)) {// 
                            $containerTypeExists = true;
                            break;
                        }
                    }
                }
                if (!$containerExists) {
                    // the container is not found in the SPARQL results
                    $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> { 
							 <' . $container . '> lodep:num_amendment "1"^^xsd:nonNegativeInteger.
							 <' . $container . '> lodep:num_' . $amendment_type . ' "1"^^xsd:nonNegativeInteger.';

                    if ($containerURI === $container) {
                        $query .='<' . $container . '> lodep:num_amendment_na "1"^^xsd:nonNegativeInteger.
                                 <' . $container . '> lodep:num_' . $amendment_type . '_na "1"^^xsd:nonNegativeInteger.';
                    }
                    if ($i > 1) {
                        $query.='<' . $container . '> a sioc:Thread.';
                    }

                    $query.='}';
                    SPARQL::runSPARQLUpdateQuery($query);
                } else {
                    if ($containerTypeExists) {
                        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> { 
                                <' . $container . '> lodep:num_' . $amendment_type . ' "1"^^xsd:nonNegativeInteger. 
								<' . $container . '> lodep:num_' . $amendment_type . '_na "1"^^xsd:nonNegativeInteger.}';
                        SPARQL::runSPARQLUpdateQuery($query);
                    }
                }
            }
        }
    }
    
    /**
     * When we delete an amendment, this function update the statistic of the amendment: the container and its parents 
     * @param type $containerURI: the container of the uri
     * @param array $containers: the list container uri, whose are the parent of the amended container
     * @param type $results: the result of sarql query, which select the properties of stattistics
     * @param type $amendment_type: deletion, insertion, or substitution.
     */

	
    function inverse_update_container_amendment($containerURI, $containers, $results, $amendment_type) {
        $containers[] = $containerURI;
        $jsnResults = json_decode($results);

        if (!empty($jsnResults->results)) {
            // increments num_items of each container in the SPARQL results
            foreach ($jsnResults->results->bindings as $result) {//?total ?totalna ?note ?notena 
                $query = 'WITH <' . env('LOD_GRAPH') . '> DELETE  {';

                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment ?amendment. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . ' ?amendment_type. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment_na ?amendment_na. ';
                }

                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . '_na ?amendment_type_na. ';
                }
                $query .= '} ';
                $query .= 'INSERT {';
                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment ?new_amendment. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . ' ?new_amendment_type. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_amendment_na ?new_amendment_na. ';
                }

                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_' . $amendment_type . '_na ?new_amendment_type_na. ';
                }
                $query .= '} ';


                $query .='WHERE {
				OPTIONAL{<' . $result->container->value . '> lodep:num_amendment ?amendment. BIND ((?amendment - 1) AS ?new_amendment)}
	                       OPTIONAL{ <' . $result->container->value . '> lodep:num_' . $amendment_type . ' ?amendment_type. BIND ((?amendment_type - 1) AS ?new_amendment_type)}
			      OPTIONAL{<' . $result->container->value . '> lodep:num_amendment_na ?amendment. BIND ((?amendment_na - 1) AS ?new_amendment_na)}
	                        OPTIONAL{<' . $result->container->value . '> lodep:num_' . $amendment_type . '_na ?amendment_type_na. BIND ((?amendment_type_na - 1) AS ?new_amendment_type_na)}
                            }';
                SPARQL::runSPARQLUpdateQuery($query);
            }
            for ($i = 0; $i < sizeof($containers); $i++) {
                $container = $containers[$i];
                $containerExists = false;
                $containerTypeExists = false;

                foreach ($jsnResults->results->bindings as $result) {
                    // if container exists in the SPARQL results
                    if ($container === $result->container->value) {
                        $containerExists = true;
                        // if the note of the container is the same as the note of the container in the SPARQL result
                        if (!isset($result->note)) {// 
                            $containerTypeExists = true;
                            break;
                        }
                    }
                }
                if (!$containerExists) {
                    // the container is not found in the SPARQL results
                    $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> { 
							 <' . $container . '> lodep:num_amendment "0"^^xsd:nonNegativeInteger.
							 <' . $container . '> lodep:num_' . $amendment_type . ' "0"^^xsd:nonNegativeInteger.';

                    if ($containerURI === $container) {
                        $query .='<' . $container . '> lodep:num_amendment_na "0"^^xsd:nonNegativeInteger.
                                 <' . $container . '> lodep:num_' . $amendment_type . '_na "0"^^xsd:nonNegativeInteger.';
                    }
                    if ($i > 1) {
                        $query.='<' . $container . '> a sioc:Thread.';
                    }

                    $query.='}';
                    SPARQL::runSPARQLUpdateQuery($query);
                } else {
                    if ($containerTypeExists) {
                        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> { 
                                <' . $container . '> lodep:num_' . $amendment_type . ' "0"^^xsd:nonNegativeInteger. 
								<' . $container . '> lodep:num_' . $amendment_type . '_na "0"^^xsd:nonNegativeInteger.}';
                        SPARQL::runSPARQLUpdateQuery($query);
                    }
                }
            }
        }
    }
    /**
     * get the list of the amendment related to a specific post.
     * @param type $post: the comment uri
     * @return result sparql in json format
     */
    
    function get_amendment_list($post){
		$query ='SELECT DISTINCT ?container ?amendment ?amendmentType WHERE{?amendment lodep:contained <' . $post . '>;  rdf:type ?amendmentType; lodep:modifies ?container.}';
                $results = SPARQL::runSPARQLQuery($query);
		return json_decode($results); 
	}
    /**
     * add an amendment 
     * @param type $amendment_URI: the uri of the amendment
     * @param type $user: the uri of the user
     * @param type $container_URI: the uri of the container
     * @param type $amendment_type: "deletion", "insertion" or "substitution"
     * @param type $deleted_content: the deleted text in the case of deletion and substitution
     * @param type $new_content: the added text in the case of insertion or substitution
     * @param type $begin_position: the begin position of the amendment
     * @param type $end_position: the end position in the case of deletion and substitution
     * @param type $eli_lang_code: the lang code of the text
     * @param type $post: the uri of the comment related to this amendement
     */
    function add_amendment($amendment_URI, $user, $container_URI, $amendment_type, $deleted_content, $new_content, $begin_position, $end_position, $eli_lang_code, $post) {
        $timestamp = date('YmdHis');
        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> { ';
        if ($amendment_type === 'deletion') {
            $query.='<' . $amendment_URI . '> a prv:Deletion.';
        }
        if ($amendment_type === 'insertion') {
            $query.='<' . $amendment_URI . '> a prv:Insertion.';
        }
        if ($amendment_type === 'substitution') {
            $query.='<' . $amendment_URI . '> a prv:Substitution.';
        }
        if (!empty($new_content)) {
            $query.='<' . $amendment_URI . '> prv:new_content "' . trim($new_content) . '"@' . $eli_lang_code . '.';
        }
        if (!empty($deleted_content)) {
            $query.='<' . $amendment_URI . '> prv:deleted_content "' . trim($deleted_content) . '"@' . $eli_lang_code . '.';
        }
        if (!empty($end_position)) {
            $query.='<' . $amendment_URI . '> prv:end_position "' . $end_position . '"^^xsd:nonNegativeInteger.';
        }
        $query.='<' . $amendment_URI . '> prv:begin_position "' . $begin_position . '"^^xsd:nonNegativeInteger.
				<' . $amendment_URI . '> prv:has_creator <' . $user . '>.
                                <' . $amendment_URI . '> lodep:contained <' . $post . '>.
				<' . $amendment_URI . '> prv:created_at "' . $timestamp . '". 
				<' . $amendment_URI . '> lodep:modifies <' . $container_URI . '>. }';
        SPARQL::runSPARQLUpdateQuery($query);
    }
    /**
     * delete the amendment,
     * @param type $amendment_URI: the uri of the amendment
     * @param type $amendment_type: the amendment type
     */
    
    function delete_amendment($amendment_URI, $amendment_type){//}, $user, $container_URI, $amendment_type, $deleted_content, $new_content, $begin_position, $end_position, $eli_lang_code, $post) {
        $query = 'DELETE FROM GRAPH <' . env('LOD_GRAPH') . '> { ';
        if ($amendment_type === 'deletion') {
            $query.='?amend a prv:Deletion;
					 prv:end_position ?end_position.';
        }
        if ($amendment_type === 'insertion') {
            $query.='?amend a prv:Insertion;
					 prv:new_content ?new_content.';
        }
        if ($amendment_type === 'substitution') {
            $query.='?amend a prv:Substitution;
					 prv:end_position ?end_position;
					 prv:new_content ?new_content.';
        }
      
        $query.='?amend prv:begin_position ?begin_position;
				prv:has_creator ?user;
				lodep:contained ?post;
				prv:created_at ?created_at; lodep:modifies ?container.  
				}
				WHERE{ ';
        if ($amendment_type === 'insertion') {$query.='?amend a prv:Insertion; prv:new_content ?new_content.';} 
        if ($amendment_type === 'deletion'){$query.='?amend a prv:Deletion; prv:end_position ?end_position.';}
        if ($amendment_type === 'substitution'){$query.='?amend a prv:Substitution; prv:end_position ?end_position; prv:new_content ?new_content.';}
				 $query.=' FILTER (?amend = <' . $amendment_URI . '>) 
				?amend prv:begin_position ?begin_position; 
                                lodep:modifies ?container; 
                                prv:created_at ?created_at;
                                lodep:contained ?post;
                                prv:has_creator ?user.
				}';

        SPARQL::runSPARQLUpdateQuery($query);
    }
    /**
     * select the amendment of a specific container: articl, ... 
     * @param type $container
     */

    public function show_amendment($container) {
        $query = 'SELECT ?ammend ?user ?timestamp ?container ?begin_position ?end_position ?deleted_content ?new_content ?post WHERE { 
				  OPTIONAL {
				            #<' . $container . '> lodep:has_amendment ?ammend. 
							OPTIONAL {?ammend lodep:contained <' . $post . '>.}
				            ?ammend prv:has_creator ?user . 
				            ?ammend prv:created_at ?timestamp .  
							?ammend prv:begin_position ?begin_position . 
				            ammend lodep:modifies ?container.}
				  OPTIONAL {?ammend prv:new_content new_content .} 
				  OPTIONAL {?ammend prv:deleted_content ?deleted_content.} 
				  OPTIONAL {?ammend prv:end_position ?end_position.}
				  OPTIONAL {?ammend lodep:contained ?post.}
                  }';
        SPARQL::runSPARQLQuery($query);
    }

    /*
    public function storeAmmendementComment(Request $request) {
        if (Session::get('user') == null) {
            return redirect()->to('/auth/login');
        }

        $exist_ammend = 'true';
        $exist_comment = 'false';
        $user = UserController::buildUserURI(Session::get('user')->user_id->value);
        $eli_lang_code = $request->get('eli_lang_code');
        $space = Act::buildSpaceURI($request->get('filename'));
        $containerURI = Container::get_URI_container($request->get('doc_code'), $request->get('year'), $request->get('num'), $request->get('id_fmx_element'), $eli_lang_code);

        if ($exist_comment) {
            $comment = str_replace(PHP_EOL, "<br />", $request->get('commentTextBox'));
            $note = $request->get('commentType');
            $containerID = Container::extractContainerIDfromContainerURI($containerURI);
            $post = Post::buildPostURIContainer(Session::get('user')->user_id->value, $containerID);
            $replied_post = '';
            $this->add_post_container($post, $user, $comment, env('SITE_NAME'), $space, $containerURI, $replied_post, $note, $eli_lang_code);
        }
        if ($exist_ammend) {
            foreach ($amends as $amend) {
                $amendment_type = ""; // delet, inser, supst
                $deleted_content = "";
                $new_content = "";
                $begin_position = "";
                $end_position = "";
                $ammendmentURI = Ammend::buildAmmendURIContainer(Session::get('user')->user_id->value, $amendment_type, $begin_position, $end_position);
                $this->add_ammend_container($ammendmentURI, $user, $container_URI, $amendment_type, $deleted_content, $new_content, $begin_position, $end_position, $eli_lang_code);
            }
        }
        if ($exist_ammend && $exist_comment) {
            $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> {<' . $post . '> lodep:contains <' . $ammendmentURI . '>. <' . $ammendmentURI . '> lodep:contained  <' . $post . '>.}';
            SPARQL::runSPARQLUpdateQuery($query);
        }
    }*/
    
    
   /* public function check_link_post_amendment($post) {
        $query = 'SELECT ?ammend ?post WHERE { 
		          OPTIONAL {?ammend lodep:contained <' . $post . '>.}
				  #UNION {OPTIONAL {<' . $ammend . '>.  lodep:contains ?post.}}
				  }';
        SPARQL::runSPARQLQuery($query);

        if (!empty(ammend)) {
            return true;
        } else {
            return false;
        }
    }*/

}

?>
