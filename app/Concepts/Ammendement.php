<?php

namespace App\Concepts;

/**
 * Description of Ammend which concerns a comment
 *
 * @author lod
 */
class Ammendement {

		/**
		
				$amendment_type=""; // delet, inser, supst
				$deleted_content="";
				$new_content="";
				$begin_position="";
				$end_position="";
				$ammendmentURI = "";
	*/
    public static function buildAmmendURIContainer($userID, $container, $amendment_type, $begin_position, $end_position){
		 $timestamp = date('YmdHis');
		 $amendment_URI= $container .'/'. $amendment_type .'/'.  $timestamp .'/user_'. $userID.'/bgpst_'. $begin_position;
		 if (!empty($end_position)){$amendment_URI .='/edpst_'.$end_position;}
		 return $amendment_URI;		 
	}

}
