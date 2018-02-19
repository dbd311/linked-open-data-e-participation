<?php

namespace App\Concepts;

use SPARQL;

/**
 * Description of Post which concerns a comment
 *
 * @author lod
 */
class Post {

    /**
     * build post URI for a container
     */
    public static function buildPostURIContainer($userID, $containerID) {
        $timestamp = date('YmdHis');

        $uri = env('SITE_NAME') . '/posts/id_user_' . $userID . '/' . $timestamp;
        if ($containerID[0] === '/') {
            $uri .= $containerID;
        } else {
            $uri .= '/' . $containerID;
        }
        return $uri;
    }

    /**
     * build post URI for an act
     * @param $userID
     * @param $actID
     * @param $lang_code
     */
    public static function buildPostURIAct($userID, $actID, $lang_code) {
        $timestamp = date('YmdHis');
        return env('SITE_NAME') . '/posts/id_user_' . $userID . '/' . $timestamp . '/id_act_' . $actID . '/' . $lang_code;
    }

    /**
     * build post URI for an article
     * @param $userID
     * @param $actID
     * @param $artID
     * @param $lang_code
     */
    public static function buildPostURIArticle($userID, $actID, $artID, $lang_code) {
        $timestamp = date('YmdHis');
        return env('SITE_NAME') . '/posts/id_user_' . $userID . '/' . $timestamp . '/id_act_' . $actID . '/' . $lang_code . '/id_article_' . $artID;
    }

    /**
     * build post URI for a paragraph
     */
    public static function buildPostURIParagraph($userID, $actID, $artID, $PID, $lang_code) {
        $timestamp = date('YmdHis');
        return env('SITE_NAME') . '/posts/id_user_' . $userID . '/' . $timestamp . '/id_act_' . $actID . '/' . $lang_code . '/id_article_' . $artID . '/id_paragraph_' . $PID;
    }

    /**
     * extract container ID from post URI
     */
    public static function extractContainerIDfromPostURI($uri) {
        $pos = 0;
        for ($i = 0; $i < strlen($uri) && $pos < 7; $i++) {
            if ($uri{$i} == '/') {
                $pos++;
            }
        }

        return substr($uri, $i);
    }

    /**
     * Get replied posts of a post
     * @param type $post
     */
    public static function getRepliedPosts($post) {
//        $query = 'PREFIX sioc: <http://rdfs.org/sioc/ns#> 
        //			select ?creator ?note ?comment 
//			where {<' . $post . '> sioc:has_reply ?post. }'; //not empty.
        $query = '  PREFIX sioc: <http://rdfs.org/sioc/ns#> 
                    PREFIX foaf: <http://xmlns.com/foaf/0.1/>                    
                    SELECT ?firstName ?lastName ?avatar ?note ?comment ?timestamp
                    WHERE {
                        <' . $post . '> sioc:has_reply ?post. 
			?post sioc:has_creator ?creator.
                        ?creator sioc:avatar ?avatar.
                        ?creator foaf:name ?firstName.
                        ?creator foaf:familyName ?lastName.
                        ?post sioc:created_at  ?timestamp.
                        OPTIONAL{?post sioc:content ?comment.}
                        ?post sioc:note ?note.
                    } ORDER BY DESC(?timestamp)';

        $jsonRes = SPARQL::runSPARQLQuery($query);

        $jsnComments = json_decode($jsonRes);

        $results = '';
        if (!empty($jsnComments->results)) {
            foreach ($jsnComments->results->bindings as $comment) {

                $results .= '<div class = "row">';
                if (!empty($comment->avatar)) {
                    $results .= '<div class = "col-md-4"><img alt="avatar" src="' . $comment->avatar->value . '"> <span class="user-info">' . $comment->firstName->value . ' ' . $comment->lastName->value . '</span> <span class="post-timestamp">' . date_create($comment->timestamp->value)->format('Y/m/d H:i') . '</span></div>';
                }

                $results .=
                        '<div class = "col-md-8">' .
                        ' <span name="previous_comment_text">' .
                        $comment->comment->value . '</span>' .
                        '</div></div><hr>';
            }
        } else {
            return '';
        }

        return $results;
    }

}
