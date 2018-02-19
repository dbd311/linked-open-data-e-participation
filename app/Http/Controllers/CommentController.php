<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Concepts\Act;
use App\Concepts\Post;
use App\Concepts\Filter;
use \App\Concepts\Container;
use SPARQL;
use App\Http\Controllers\AmmendementController;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\UserController;

class CommentController extends Controller {

    /**
     * Store a newly created comment in the triple store
     *
     * @param  Request  $request
     *
     */
    public function storeComment(Request $request) {
        $user = UserController::buildUserURI(Session::get('user')->user_id->value);
        $comment = $request->get('commentTextBox');
        $space = Act::buildSpaceURI($request->get('filename'));
        $note = $request->get('commentType');
        $eli_lang_code = $request->get('eli_language_code');
        $uri = $request->get('uri');
        $containerURI = Container::get_URI_container($request->get('doc_code'), $uri);
        $post = Post::buildPostURIContainer(Session::get('user')->user_id->value, $uri);
        $replied_post = '';

        $this->add_post_container($post, $user, $comment, env('SITE_NAME'), $space, $containerURI, $replied_post, $note, $eli_lang_code);

        if ($request->modifications != null || !empty($request->modifications)) {
            $ammendementController = new AmmendementController();
            $ammendementController->storeAmmendement($request, $containerURI, $post, $eli_lang_code);
        }
    }

    /**
     * Add a post for an act
     * @param   $post an URI of the comment (post)
     * @param   $user user URI
     * @param   $comment text of the comment
     * @param   $site site name URI
     * @param   $act act URI
     * @param   $title title of act
     * @param   $replied_post post URI
     * @param   $topic N/A
     * @param   $title_com N/A
     * @param   $lang_code language code of the act
     */
    function add_post_container($post, $user, $comment, $site, $space, $containerURI, $replied_post, $note, $lang_code) {

        // initliaze or increment the num_items of the current container and its ascendants
        $this->update_container_and_ascendants($containerURI, $note);

        $this->add_post($post, $user, $comment, $containerURI, $replied_post, $note, $lang_code);
    }

    /**
     * Update attributes of the container and all of its ascendants
     * @param type $containerURI
     * @param type $note
     */
    function update_container_and_ascendants($containerURI, $note) {
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
        for ($i = $endPos + 1; $i < sizeOf($fields) - 1; $i++) {
            $curContainer .= '/' . $fields[$i];
            $ascendant_containers[] = $curContainer;
            $ascendant_containers[] = $curContainer . '/' . $lang;
        }
        $filter_container = Filter::create_filter_ascendant_containers($ascendant_containers);
        $query = 'SELECT DISTINCT ?container ?total ?totalna ?note ?notena WHERE {{OPTIONAL{?container lodep:num_items_total ?total.} OPTIONAL{?container lodep:num_items_' . $note . ' ?note.} OPTIONAL{?container lodep:num_items_' . $note . '_na ?notena.} OPTIONAL{?container lodep:num_items_total_na ?totalna.} FILTER(?container=<' . $containerURI . '>)} UNION {OPTIONAL{?container lodep:num_items_total ?total.} OPTIONAL{?container lodep:num_items_' . $note . ' ?note.}' . $filter_container . ' }}';
        $results = SPARQL::runSPARQLQuery($query);
        // update the container and all of its ascendants as well as their statistics ...
        $this->update_container($containerURI, $ascendant_containers, $results, $note);
    }

    /**
     * Update the container and its ascendants as well as their statistics given the results from SPARQL
     * @param URI $containerURI
     * @param array $containers
     * @param JSON $results
     * @param string $note
     */
    function update_container($containerURI, $containers, $results, $note) {
        // add the leave to the list of containers, which represents the complete hiearchy of containers from the leave to 
        // the root (top-down)
        $containers[] = $containerURI;

        $jsnResults = json_decode($results);

        if (!empty($jsnResults->results)) {
            // increments num_items of each container in the SPARQL results
            foreach ($jsnResults->results->bindings as $result) {//?total ?totalna ?note ?notena 
                // first of all, we delete old statistics (?total, ?totalna, ?note, ?notena) and replace by new statistics (?newTotal, ?newTotalna, ?newNote, ?newNotena)
                $query = 'WITH <' . env('LOD_GRAPH') . '> DELETE  {';

                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total ?total. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . ' ?note. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total_na ?totalna. ';
                }

                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . '_na ?notena. ';
                }
                $query .= '} ';
                // and update statistics about comments by increasing by 1
                $query .= 'INSERT {';
                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total ?newtotal. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . ' ?newnote. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total_na ?newtotalna. ';
                }

                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . '_na ?newnotena. ';
                }
                $query .= '} ';

                $query .='WHERE {
                            OPTIONAL{<' . $result->container->value . '> lodep:num_items_total ?total. BIND ((?total + 1) AS ?newtotal)}
                             OPTIONAL{<' . $result->container->value . '> lodep:num_items_total_na ?totalna. BIND ((?totalna + 1) AS ?newtotalna)}
	                     OPTIONAL{<' . $result->container->value . '> lodep:num_items_' . $note . '_na ?notena. BIND ((?notena + 1) AS ?newnotena)}
                            OPTIONAL{<' . $result->container->value . '> lodep:num_items_' . $note . ' ?note. BIND ((?note + 1) AS ?newnote)}
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
                                    <' . $container . '> lodep:num_items_total "1"^^xsd:nonNegativeInteger.
                                    <' . $container . '> lodep:num_items_' . $note . ' "1"^^xsd:nonNegativeInteger.';

                    if ($containerURI === $container) {
                        $query .=' <' . $container . '> lodep:num_items_total_na "1"^^xsd:nonNegativeInteger.
                                    <' . $container . '> lodep:num_items_' . $note . '_na "1"^^xsd:nonNegativeInteger.';
                    }

                    if ($i > 1) {
                        $query.='<' . $container . '> a sioc:Thread.';
                    }

                    $query.='}';
                    SPARQL::runSPARQLUpdateQuery($query);
                    
                } else {
                    if ($containerTypeExists) {
                        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> { 
                                <' . $container . '> lodep:num_items_' . $note . ' "1"^^xsd:nonNegativeInteger. <' . $container .
                                '> lodep:num_items_' . $note . '_na "1"^^xsd:nonNegativeInteger.}';
                        SPARQL::runSPARQLUpdateQuery($query);
                      
                    }
                }
            }
            // create has_parent links 
            for ($i = sizeof($containers) - 2; $i > 0; $i--) {
                $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> '
                        . '{<' . $containers[$i] . '> sioc:has_parent <' . $containers[$i - 1] . '>}';

                SPARQL::runSPARQLUpdateQuery($query);
                
            }
        }
    }



    /**
     * Update attributes of the container and all of its ascendants
     * @param type $containerURI
     * @param type $note
     */
    function inverse_update_container_and_ascendants($containerURI, $note) {
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
        for ($i = $endPos + 1; $i < sizeOf($fields) - 1; $i++) {
            $curContainer .= '/' . $fields[$i];
            $ascendant_containers[] = $curContainer;
            $ascendant_containers[] = $curContainer . '/' . $lang;
        }
        $filter_container = Filter::create_filter_ascendant_containers($ascendant_containers);
        $query = 'SELECT DISTINCT ?container ?total ?totalna ?note ?notena WHERE {{OPTIONAL{?container lodep:num_items_total ?total.} OPTIONAL{?container lodep:num_items_' . $note . ' ?note.} OPTIONAL{?container lodep:num_items_' . $note . '_na ?notena.} OPTIONAL{?container lodep:num_items_total_na ?totalna.} FILTER(?container=<' . $containerURI . '>)} UNION {OPTIONAL{?container lodep:num_items_total ?total.} OPTIONAL{?container lodep:num_items_' . $note . ' ?note.}' . $filter_container . ' }}';
        $results = SPARQL::runSPARQLQuery($query);
        // update the container and all of its ascendants as well as their statistics ...
        $this->inverse_update_container($containerURI, $ascendant_containers, $results, $note);
    }

    /**
     * Update the container and its ascendants as well as their statistics given the results from SPARQL
     * @param URI $containerURI
     * @param array $containers
     * @param JSON $results
     * @param string $note
     */
    function inverse_update_container($containerURI, $containers, $results, $note) {
        // add the leave to the list of containers, which represents the complete hiearchy of containers from the leave to 
        // the root (top-down)
        $containers[] = $containerURI;
        $jsnResults = json_decode($results);
        if (!empty($jsnResults->results)) {
            // increments num_items of each container in the SPARQL results
            foreach ($jsnResults->results->bindings as $result) {//?total ?totalna ?note ?notena 
                // first of all, we delete old statistics (?total, ?totalna, ?note, ?notena) and replace by new statistics (?newTotal, ?newTotalna, ?newNote, ?newNotena)
                $query = 'WITH <' . env('LOD_GRAPH') . '> DELETE  {';
                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total ?total. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . ' ?note. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total_na ?totalna. ';
                }

                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . '_na ?notena. ';
                }
                $query .= '} ';
                // and update statistics about comments by increasing by 1
                $query .= 'INSERT {';
                if (!empty($result->total)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total ?newtotal. ';
                }
                if (!empty($result->note)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . ' ?newnote. ';
                }
                if (!empty($result->totalna)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_total_na ?newtotalna. ';
                }
                if (!empty($result->notena)) {
                    $query .= '<' . $result->container->value . '> lodep:num_items_' . $note . '_na ?newnotena. ';
                }
                $query .= '} ';
                $query .='WHERE {
                            OPTIONAL{<' . $result->container->value . '> lodep:num_items_total ?total. BIND ((?total - 1) AS ?newtotal)}
                            OPTIONAL{<' . $result->container->value . '> lodep:num_items_total_na ?totalna. BIND ((?totalna - 1) AS ?newtotalna)}
	                    OPTIONAL{<' . $result->container->value . '> lodep:num_items_' . $note . '_na ?notena. BIND ((?notena - 1) AS ?newnotena)}
                            OPTIONAL{<' . $result->container->value . '> lodep:num_items_' . $note . ' ?note. BIND ((?note - 1) AS ?newnote)}
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
                                    <' . $container . '> lodep:num_items_total "0"^^xsd:nonNegativeInteger.
                                    <' . $container . '> lodep:num_items_' . $note . ' "0"^^xsd:nonNegativeInteger.';
                    if ($containerURI === $container) {
                        $query .='<' . $container . '> lodep:num_items_total_na "0"^^xsd:nonNegativeInteger.
                                    <' . $container . '> lodep:num_items_' . $note . '_na "0"^^xsd:nonNegativeInteger.';
                    }
                    if ($i > 1) {
                        $query.='<' . $container . '> a sioc:Thread.';
                    }
                    $query.='}';
                    SPARQL::runSPARQLUpdateQuery($query);
                } else {
                    if ($containerTypeExists) {
                        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> { 
                                <' . $container . '> lodep:num_items_' . $note . ' "0"^^xsd:nonNegativeInteger. <' . $container .
                                '> lodep:num_items_' . $note . '_na "0"^^xsd:nonNegativeInteger.}';

                        SPARQL::runSPARQLUpdateQuery($query);
                    }
                }
            }
            // create has_parent links 
            for ($i = sizeof($containers) - 2; $i > 0; $i--) {
                $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> '
                        . '{<' . $containers[$i] . '> sioc:has_parent <' . $containers[$i - 1] . '>}';

                SPARQL::runSPARQLUpdateQuery($query);
            }
        }
    }

    /*
     * $post: site/date/timestemp/id_user/id_article
     * $comment: text value of comment
     * $container: webblog/formul
     * $replayed_post: another $post (not necessary)
     * $topic: thematic of the post (not obligartory, and can be selected from the container)
     * $user: id of user, i.e. mail adress or sioc:id.
     * $title_com: the title of the comment, e.g., 'thid_article_bla_bla' (not obligartory)
     * $num_item: the number of post for a formula i.e. a container. incremùental compte.
     * $note: agree, disagree or mixed
     * $lang_code: language code
     */

    function add_post($post, $user, $comment, $container, $replied_post, $note, $eli_lang_code) {
        if ($note == '' || empty($note)) {
            $note = 'mixed';
        }
        date_default_timezone_set('Europe/Paris');
        $timestamp = date('YmdHis');
        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '>
		{<' . $post . '> a sioc:Post.
		<' . $post . '> sioc:content "' . trim($comment) . '"@' . $eli_lang_code . '.
		<' . $post . '> sioc:has_creator <' . $user . '>.
		<' . $post . '> sioc:note "' . $note . '"^^rdfs:Literal.
		<' . $post . '> sioc:created_at "' . $timestamp . '". 
		<' . $post . '> sioc:has_container <' . $container . '>.
		<' . $post . '> lodep:num_like "0"^^xsd:nonNegativeInteger.
		<' . $post . '> lodep:num_dislike "0"^^xsd:nonNegativeInteger.}'; #lodep:num_dislike

        SPARQL::runSPARQLUpdateQuery($query);
    }

    /**
     *  Reply to a specific comment independantly from the container type
     * @param Request $request
     * @return type
     */
    public function replyUserComment(Request $request) {

        // build post URI for the answer
        $post = Post::buildPostURIContainer(Session::get('user')->user_id->value, $request->get('containerId'));
        $user = UserController::buildUserURI(Session::get('user')->user_id->value);
        $comment = str_replace(PHP_EOL, "<br />", $request->get('replyText'));
        //$note = $request->get('replyType');
        $eli_lang_code = $request->get('lang');
        // URI du commentaire qu'un user va repondre
        $replied_post = $request->get('postUri');

        $this->add_post_for_post($post, $user, $comment, $replied_post, $eli_lang_code);
    }

    /**
     * rturn "true" if the user was alrady give a note for a replied post
     * @param type $replied_post
     * @param type $user
     * @return string
     */
    function give_already_not_for_post($replied_post, $user) {
        $query = 'SELECT DISTINCT ?post WHERE{?post lodep:give_note_to ?replied_post. ?post sioc:has_creator ?user. 
		FILTER(<' . $replied_post . '>=?replied_post && <' . $user . '> = ?user)}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults))
            return "true";
        return "false";
    }

    /**
     * get not for replied post given by a specific user
     * @param Request $request
     * @return string
     */
    function getNoteForPost(Request $request) {
        $repliedPost = $request->get('repliedPost');
        $user = UserController::buildUserURI(Session::get('user')->user_id->value);
        $query = 'SELECT DISTINCT ?post ?note WHERE{?post lodep:give_note_to ?replied_post; sioc:note ?note; sioc:has_creator ?user. 
        FILTER(<' . $repliedPost . '>=?replied_post && <' . $user . '> = ?user)}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $result = [];
        if (!empty($jsnResults)) {
            if (isset($jsnResults->results->bindings[0])) {
                $result[] = $jsnResults->results->bindings[0]->note->value;
                $result[] = $jsnResults->results->bindings[0]->post->value;
                return $result;
            }
        }
        $result[] = 'any';
        return $result;
    }

    /**
     * return "true" if the user replies already the container, outherwise "false".
     * @param type $container
     * @param type $user
     * @return boolean
     */
    function replay_already_container($container, $user) {
        $query = 'SELECT DISTINCT ?post WHERE{
				?post sioc:has_container ?container. FILTER(<' . $container . '>=?container) 
				?post sioc:has_creator ?user. FILTER(<' . $user . '>=?user)}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults))
            return "true";
        return "false";
    }

    /**
     * add a post to rplies another post
     * @param type $post
     * @param type $user
     * @param type $comment
     * @param type $replied_post
     * @param type $eli_lang_code
     */
    function add_post_for_post($post, $user, $comment, $replied_post, $eli_lang_code) {
        date_default_timezone_set('Europe/Paris');
        $timestamp = date('YmdHis');
        if ($replied_post !== '' && $comment !== '') {
            $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '> {' .
                    '<' . $replied_post . '> sioc:has_reply <' . $post . '>.' .
                    '<' . $post . '> a sioc:Post; 
                             sioc:has_creator <' . $user . '>; 
                             sioc:content "' . trim($comment) . '"@' . $eli_lang_code . ';
                             sioc:created_at "' . $timestamp . '";
                             lodep:num_like "0"^^xsd:nonNegativeInteger;
                             lodep:num_dislike "0"^^xsd:nonNegativeInteger.}';
            SPARQL::runSPARQLUpdateQuery($query);
        }
    }

    /**
     * lick or dislick click, 
     * @param Request $request
     */
    function likeDislikePost(Request $request) {
        $user = Session::get('user')->user_id->value;
        $userUri = UserController::buildUserURI($user);
        $repliedPost = $request->get('repliedPost');
        $note = $request->get('note');
        $uri = $request->get('uri');
        $list = $this->getNoteForPost($request);
        $previousNote = $list[0];
        if ($previousNote === 'any') {
            $post = Post::buildPostURIContainer($user, $uri);
            date_default_timezone_set('Europe/Paris');
            $timestamp = date('YmdHis');
            $query = 'WITH GRAPH <' . env('LOD_GRAPH') . '> DELETE {';
            if ($note === 'yes') {
                $query .= '?replied_post lodep:num_like ?num_like.';
            }
            if ($note === 'no') {
                $query .= '?replied_post lodep:num_dislike ?num_dislike.';
            }

            $query .='} INSERT { <' . $post . '> a sioc:Post;
                                    lodep:give_note_to <' . $repliedPost . '>;
                                    sioc:has_creator <' . $userUri . '>; 					
                                    sioc:created_at "' . $timestamp . '"; 
                                    sioc:note "' . $note . '"^^rdfs:Literal.';
            if ($note === 'yes') {
                $query .= '<' . $repliedPost . '> lodep:num_like ?new_num_like.';
            }
            if ($note === 'no') {
                $query .= '<' . $repliedPost . '> lodep:num_dislike ?new_num_dislike.';
            }

            $query .= '} WHERE {';
            if ($note === 'yes') {
                $query .= ' ?replied_post lodep:num_like ?num_like. FILTER(<' . $repliedPost . '>=?replied_post) BIND ((?num_like + 1) as ?new_num_like)';
            }
            if ($note === 'no') {
                $query .= ' ?replied_post lodep:num_dislike ?num_dislike. FILTER(<' . $repliedPost . '>=?replied_post) BIND ((?num_dislike + 1) as ?new_num_dislike)';
            }
            $query .= '}';
        } else {
            $post = $list[1];
            if ($previousNote === $note) {

                $query = 'WITH GRAPH <' . env('LOD_GRAPH') . '> DELETE {
                                    ?post a sioc:Post.
                                    ?post lodep:give_note_to ?repliedPost;
                                    sioc:has_creator ?userUri; 					
                                    sioc:created_at ?timestamp; 
                                    sioc:note ?note.';
                if ($note === 'yes') {
                    $query .= '?replied_post lodep:num_like ?num_like.';
                }
                if ($note === 'no') {
                    $query .= '?replied_post lodep:num_dislike ?num_dislike.';
                }

                $query .='} INSERT {';
                if ($note === 'yes') {
                    $query .= '<' . $repliedPost . '> lodep:num_like ?new_num_like.';
                }
                if ($note === 'no') {
                    $query .= '<' . $repliedPost . '> lodep:num_dislike ?new_num_dislike.';
                }

                $query .= '} WHERE {?post a sioc:Post. FILTER(?post= <' . $post . '>)
                                    ?post lodep:give_note_to ?repliedPost;
                                    sioc:has_creator ?userUri; 					
                                    sioc:created_at ?timestamp; 
                                    sioc:note ?note.';
                if ($note === 'yes') {
                    $query .= ' ?replied_post lodep:num_like ?num_like. FILTER(<' . $repliedPost . '>=?replied_post) BIND ((?num_like -1) as ?new_num_like)';
                }
                if ($note === 'no') {
                    $query .= ' ?replied_post lodep:num_dislike ?num_dislike. FILTER(<' . $repliedPost . '>=?replied_post) BIND ((?num_dislike -1) as ?new_num_dislike)';
                }
                $query .= '}';
            } else {

                $query = 'WITH GRAPH <' . env('LOD_GRAPH') . '> 
            DELETE {?replied_post lodep:num_like ?num_like; 
                            lodep:num_dislike ?num_dislike. ?post sioc:note ?note.} 
            INSERT {<' . $post . '> sioc:note "' . $note . '"^^rdfs:Literal. 
                            <' . $repliedPost . '> lodep:num_like ?new_num_like. 
                            <' . $repliedPost . '> lodep:num_dislike ?new_num_dislike.} 
            WHERE {?post sioc:note ?note. FILTER(?post= <' . $post . '>)';

                if ($note === 'yes') {
                    $query .= ' ?replied_post lodep:num_like ?num_like. ?replied_post lodep:num_dislike ?num_dislike.  
            FILTER(<' . $repliedPost . '>=?replied_post) BIND ((?num_like + 1) as ?new_num_like) BIND ((?num_dislike -1) as ?new_num_dislike)';
                }
                if ($note === 'no') {
                    $query .= ' ?replied_post lodep:num_like ?num_like. ?replied_post lodep:num_dislike ?num_dislike.  
            FILTER(<' . $repliedPost . '>=?replied_post) BIND ((?num_like - 1) as ?new_num_like) BIND ((?num_dislike + 1) as ?new_num_dislike)';
                }
                $query .= '}';
            }
        }
        SPARQL::runSPARQLUpdateQuery($query);
    }

    /**
     * get the number of comments of a container, whitout concidering the comment of its subsections
     * @param type $container
     * @return type
     */
    function getNumberComment($container) {
        $query = 'SELECT DISTINCT ?totalna 
                              WHERE {
                              <' . $container . '> lodep:num_items_total_na ?totalna.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        $numberComment = 0;
        if (!empty($jsnResults))
            $numberComment = $jsnResults->results->bindings[0]->totalna->value;
        return $numberComment;
    }

    /**
     * return "true" if the post has replies post, outherwise "false"
     * @param type $post
     * @return string
     */
    function has_reply($post) {
        $query = 'SELECT ?replied_post WHERE{?replied_post sioc:has_reply <' . $post . '>.} limit 1' .
                $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults->results->bindings)) {
            return "true";
        } else {
            return "false";
        }
    }

    /**
     * Ammend post and also its amendment, in concedering the update of statistic and also the translation of the amended comment.
     * @param Request $request
     */
    function ammendPost(Request $request) {
        $post = $request->get('post');
        $comment = $request->get('comment');
        $note = $request->get('note');
        $eli_lang_code = '';
        if ($note == '') {
            $note = 'mixed';
        }

        $query = 'SELECT ?container ?note LANG(?content) as ?eli_lang_code WHERE{<' . $post . '> sioc:content ?content. '
                . 'OPTIONAL{<' . $post . '> sioc:has_container ?container.} OPTIONAL{<' . $post . '>  sioc:note ?note.} }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            $olde_note = $jsnResults->results->bindings[0]->note->value;
            $containerURI = $jsnResults->results->bindings[0]->container->value;
            $eli_lang_code = $jsnResults->results->bindings[0]->eli_lang_code->value;
        }
        if ($olde_note !== $note) {
            $this->inverse_update_container_and_ascendants($containerURI, $olde_note);
            $this->update_container_and_ascendants($containerURI, $note);
        }
        date_default_timezone_set('Europe/Paris');
        $timestamp = date('YmdHis');
        $query = 'WITH <' . env('LOD_GRAPH') . '> DELETE { ?post sioc:note ?note. ?post sioc:content ?content. ?post lodep:amended_at ?amended_at. } ';
        $query .= 'INSERT { ?post sioc:note "' . $note . '". ?post sioc:content "' . $comment . '"@' . $eli_lang_code . '. ?post lodep:amended_at "' . $timestamp . '". } ';
        $query .= 'WHERE { ?post a sioc:Post. FILTER (?post=<' . $post . '>) OPTIONAL{?post sioc:note ?note. } OPTIONAL{ ?post sioc:content ?content. } OPTIONAL{ ?post lodep:amended_at ?amended_at. } }';
        SPARQL::runSPARQLUpdateQuery($query);
        $ammendementController = new AmmendementController();
        $ammendementController->removeAmmendement($post);
        if ($request->modifications != null || !empty($request->modifications)) {
            $ammendementController->storeAmmendement($request, $containerURI, $post, $eli_lang_code);
        }
    }

    /**
     * Ammend post and also its amendment, in concedering the translation of the amended comment.
     * @param Request $request
     */
    function ammendPostForPost(Request $request) {
        $post = $request->get('post');
        $comment = $request->get('comment');
        $eli_lang_code = '';
        $query = 'SELECT LANG(?content) as ?eli_lang_code WHERE{<' . $post . '> sioc:content ?content.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            $eli_lang_code = $jsnResults->results->bindings[0]->eli_lang_code->value;
        }
        date_default_timezone_set('Europe/Paris');
        $timestamp = date('YmdHis');
        $query = 'WITH <' . env('LOD_GRAPH') . '> DELETE {?post sioc:content ?content. ?post lodep:amended_at ?amended_at. } ';
        $query .= 'INSERT { ?post sioc:content "' . $comment . '"@' . $eli_lang_code . '. ?post lodep:amended_at "' . $timestamp . '". } ';
        $query .= 'WHERE { ?post a sioc:Post. FILTER (?post=<' . $post . '>) OPTIONAL{ ?post sioc:content ?content. } OPTIONAL{ ?post lodep:amended_at ?amended_at. } }';
        SPARQL::runSPARQLUpdateQuery($query);
    }

    /**
     * Delete post and also its amendment, in concedering the update of statistic and also the deletion of its translated comments.
     * @param Request $request
     */
    function deletePost(Request $request) {
        $post = $request->get('post');
        $query = 'SELECT ?container ?note  WHERE{<' . $post . '> sioc:has_container ?container; sioc:note ?note.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            $note = $jsnResults->results->bindings[0]->note->value;
            $containerURI = $jsnResults->results->bindings[0]->container->value;
        }

        $this->inverse_update_container_and_ascendants($containerURI, $note);

        $query = 'WITH <' . env('LOD_GRAPH') . '> 
                DELETE { ?post a sioc:Post. 
                        ?translated_post a sioc:Post. 
                        ?post sioc:note ?note. 
                        ?post sioc:content ?content. 
                        ?post lodep:amended_at ?amended_at. 
                        ?post lodep:translated_to ?translated_post.
                        ?translated_post sioc:content ?translated_content.					
                        ?translated_post lodep:translated_from ?post.} 
                WHERE { ?post a sioc:Post. FILTER (?post=<' . $post . '>) 
                        OPTIONAL{?post sioc:note ?note. }
                        OPTIONAL{ ?post sioc:content ?content. } 
                        OPTIONAL{ ?post lodep:amended_at ?amended_at. } 
                        OPTIONAL{?translated_post lodep:translated_from ?post. 
                                ?post lodep:translated_to ?translated_post. 
                                ?translated_post sioc:content ?translated_content.} 
                        }';
        SPARQL::runSPARQLUpdateQuery($query);
        $ammendementController = new AmmendementController();
        $ammendementController->removeAmmendement($post);
    }

    /**
     * Delete post which replies another post, in concedering the deletion of the translated comments.
     * @param Request $request
     */
    function deletePostForPost(Request $request) {
        $post = $request->get('post');
        $query = 'WITH <' . env('LOD_GRAPH') . '> 
                DELETE { ?post a sioc:Post. 
                ?reply_post sioc:has_reply ?post.
                        ?translated_post a sioc:Post. 
                        ?post sioc:content ?content. 
                        ?post lodep:amended_at ?amended_at. 
                        ?post lodep:translated_to ?translated_post.
                        ?translated_post sioc:content ?translated_content.					
                        ?translated_post lodep:translated_from ?post.} 
                WHERE { ?post a sioc:Post. FILTER (?post=<' . $post . '>) 
                        ?reply_post sioc:has_reply ?post.
                        OPTIONAL{ ?post sioc:content ?content. } 
                        OPTIONAL{ ?post lodep:amended_at ?amended_at. } 
                        OPTIONAL{?translated_post lodep:translated_from ?post. 
                                        ?post lodep:translated_to ?translated_post. 
                                        ?translated_post sioc:content ?translated_content.} 
                        }';
        SPARQL::runSPARQLUpdateQuery($query);
    }
}
