<?php

namespace App\Concepts;

/**
 * Description of Container
 *
 * @author lod
 */
class Container {

    /**
     * Creates URI for a container
     * @param type $doc_code
     * @param type $year
     * @param type $num
     * @param type $id_fmx_element ID of the fragment in formex
     * @param type $eli_lang_code
     * @return type
     */
    public static function get_URI_container($doc_code, $uri) {
        $URI_container = env('SITE_NAME') . '/eli/' . $doc_code . '/' . $uri;
        return $URI_container;
    }
	/**
	* Check if the container is chapter or session
	*/
	public static function isChapterOrSession($container){
	$tmpContainer= str_replace(env('.$SITE_NAME.'), '', $container);
	$fields = explode("/", $tmpContainer);
		if (sizeof($fields)>6){
			 $name=explode("_", $fields[6]);
			 if ($name[0]=='chp' || $name[0]=='ses' ) return true;	 
		}
	return false;
	}
	
		/**
	* Check if the container is act
	*/
	public static function isAct($container){
		$tmpContainer= str_replace(env('.$SITE_NAME.'), '', $container);
		$fields = explode("/", $tmpContainer);
			if (sizeof($fields)===7){
			   return true;	 
			}
		return false;
	}
    
    /**
     * Creates URI for a container
     * @param type $doc_code
     * @param type $year
     * @param type $num
     * @param type $id_fmx_elemnet ID of the fragment in formex
     * @param type $eli_lang_code
     * @return type
     */
    public static function get_complete_URI_container($doc_code, $year, $num, $id_fmx_elemnet, $eli_lang_code) {

        $URI_container = env('SITE_NAME') . '/eli/' . $doc_code . '/' . $year . '/' . $num;
        if (strlen(trim($id_fmx_elemnet)) > 3) { // remove 'ACT'
            $fmx_elements = explode(".", $id_fmx_elemnet);
            foreach ($fmx_elements as $elm) {
                $type = strtolower(substr($elm, 0, 3));
//                $type = substr($elm, 0, 3);
                /*if ($type === 'chp') {
                    $type = 'cpt';
                }*/
                $num_elm = substr($elm, 3, strlen($elm)); // substr($elm, 3, 6)reduire la position de 4 à 2 ou 3....
                $URI_container .= '/' . $type . '_' . $num_elm;
            }
        }
        return $URI_container . '/' . $eli_lang_code;
    }
}
