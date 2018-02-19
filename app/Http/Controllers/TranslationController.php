<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Http\Controllers\MetadataController;
use App\Concepts\Post;
use Illuminate\Support\Facades\Session;
use SPARQL;

class TranslationController extends Controller {
    

     function sendTranslation2(){
         $soap_query ='<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:mtat="http://mtatec.dgt.ec.europa.eu/">
					   <soapenv:Header/>
					   <soapenv:Body>
						  <mtat:asktranslation>
							 <arg0>
								 <textToTranslate>My text to translate</textToTranslate>
								<requestType>txt</requestType>
								<priority>5</priority> 
								<externalReference>Reference001</externalReference>
								<requesterCallback>http://eparticipation-dev.opoce.cec.eu.int/public/callback</requesterCallback>
								<errorCallback>http://eparticipation-dev.opoce.cec.eu.int/public/errorcallback</errorCallback>
								<sourceLanguage>EN</sourceLanguage>
								<targetLanguage>FR,IT</targetLanguage>
								<targetTranslationPath>email:brahim.batouche@ext.publications.europa.eu</targetTranslationPath>
								<username>batouchb</username>
								<applicationName>eParticipation_20160601</applicationName>
								<domains>all</domains>
								<institution>eu.europa.publications</institution>
								<departmentNumber>A2003</departmentNumber>
								<outputFormat></outputFormat>
								<originalFileName></originalFileName>
							 </arg0>
						  </mtat:asktranslation>
					   </soapenv:Body>
					</soapenv:Envelope>';
			//json		
			
		$data =Array("Content-Type" =>"text/xml", "charset"=>"UTF-8", "SOAPAction" =>"askTranslation",
                            "textToTranslate" => "My text to translate", 
                            "requestType" => "txt", 
                            "priority" => "5", 
                            "externalReference" => "Reference001", 
                            "requesterCallback" => "http://eparticipation-dev.opoce.cec.eu.int/public/callback", 
                            "errorCallback" => "http://eparticipation-dev.opoce.cec.eu.int/public/errorcallback", 
                            "sourceLanguage" => "EN", 
                            "targetLanguage" => "FR,IT", 																		 
                            "targetTranslationPath" => "email:Brahim.BATOUCHE@ext.publications.europa.eu",
                            "username" => "batouchb",
                            "applicationName" => "eParticipation_20160601",
                            "domains" => "all", 
                            "institution" => "eu.europa.publications", 
                            "departmentNumber" => "A2003", 								
                            "outputFormat" => "json");

						
		//$soap_query = http_build_query($data) ;
		$url = "https://mtatecservice.ec.testa.eu/mtatec/public-services/translationService.wsdl";//?externalReference=Reference001"; 		
		$operation = "askTranslation"; // https://mtatecservice.ec.testa.eu/mtatec/public-services/translationService.
                
$command = 'curl -X POST -H "Content-Type: xml" -H "SOAPAction: '. $operation .'" -d @soap_query.xml '.$url; //.xml 

                shell_exec($command);
                echo $command;
                
		/*$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml; charset=utf-8','SOAPAction: askTranslation'));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_VERBOSE, 0); // 1 = for debugging purpose & 0 = production
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_query);
		curl_setopt($ch, CURLOPT_POST, $soap_query);
		curl_exec($ch);
		curl_close($ch);*/

     }
     
     
    function sendTranslation1(){
		$wsdl = "https://mtatecservice.ec.testa.eu/mtatec/public-services/translationService.wsdl";
		$client = new \nusoap_client($wsdl, false, array('content-type' => 'xml'));//,false, false, false,  false, 0, 30, ''); //("https://mtatecservice.ec.testa.eu/mtatec/public-services/translationService.wsdl");//,'', 'content-type: text'); //'',	
		//$header = Array('content-type' => 'text/xml');
                 //$client = $this->setHeaders($client);
                 //$client->setCredentials($DB["user"], $DB["password"]);
                //$client->__setSoapHeaders($header);
              //  $client->setHeaders('');//Array('content-type' => 'text/xml')); // <locale>$locale</locale>  "<content-type>text</content-type>"
                
                $client->setHeaders('<soap:Header><content-type>xml</content-type></soap:Header>');
                $client->soap_defencoding = 'UTF-8';
		$client -> setEndpoint('https://mtatecservice.ec.testa.eu/mtatec/public-services/translationService'); 
		$error = $client->getError();
		if ($error) {
                    echo "<h2>Constructor error</h2><pre>" . $error . "</pre>";
		}
		echo 'test translation NUSOAP .sdfhg .....';	
		
			
		$data =Array("textToTranslate" => "My text to translate the text ...", 
                                "content-type" => "xml", 
                                "requestType" => "txt", 
                                "priority" => "5", 
                                "externalReference" => "Reference001", 
                                "requesterCallback" => "http://eparticipation-dev.opoce.cec.eu.int/public/callback", 
                                "errorCallback" => "http://eparticipation-dev.opoce.cec.eu.int/public/errorcallback", 
                                "sourceLanguage" => "EN", 
                                "targetLanguage" => "FR,IT", 																		 
                                "targetTranslationPath" => "email:Brahim.BATOUCHE@ext.publications.europa.eu",
                                "username" => "batouchb",
                                "applicationName" => "eParticipation_20160601",
                                "domains" => "all", 
                                "institution" => "eu.europa.publications", 
                                "departmentNumber" => "A2003", 								
                                "outputFormat" => "json");
		//$data = http_build_query($data) ;
		$operation='asktranslation'; //https://mtatecservice.ec.testa.eu/mtatec/public-services/translationService.
                // echo 'test translation NUSOAP ......';	
		$result = $client->call($operation, $data);//, '', '', false, null, 'rpc', 'encoded');//,'', '', false, true);
		echo 'aaaa  '. empty($result);
		if ($client->fault) {
			echo "<h2>Fault</h2><pre>";
			print_r($result);
			echo "</pre>";
		}
		else {
			$error = $client->getError();
			if ($error) {
				echo "<h2>Error </h2><pre>" . $error . "</pre>";
			}
			else {
				echo "<h2>the result of translated text are: </h2><pre>";
				echo empty($result); // give the id of the translation, 
				//$this->storeIdDgt($post, $id_dgt);				
				echo "</pre>";
			}
		}
    }
    
    /**
     * Detect the lang of text 
     * @param Request $request: input text
     * @return langue (lang_code), ex, English(en), ...
     */
    public function detectLanguage(Request $request){ 
            $text = $request->get('text');
            $command = "java -cp send_dgt.jar lodep.LanguageDetection " . $text;
            $lang_code = shell_exec($command);
            return MetadataController::get_lang_name($lang_code).'('.$lang_code.')';
    }
    /**
     * This function return true if there exist comments not translated.
     * @return string boolean: "true" or "false".
     */

    public function show_link_update_translation(){
            $query='SELECT DISTINCT ?post ?translated_post  WHERE{?post sioc:created_at ?created_at; sioc:content ?content. OPTIONAL{?post lodep:translated_to ?translated_post.}}';
            $results= SPARQL::runSPARQLQuery($query);
            $jsnResults = json_decode($results);
            if (!empty($jsnResults)){
                    foreach($jsnResults->results->bindings as $rs){
                                     if (empty($rs->translated_post->value))return 'true';
                    }
            }
            return 'false';
    }

    /**
     * This fuction select all not translated comments and translate them.
     * @return previos page
     */

    static public function update_translation(){
        $query = 'SELECT DISTINCT ?post ?translated_post ?comment LANG(?comment) as ?lang  WHERE{?post sioc:created_at ?created_at. ?post sioc:content ?comment. OPTIONAL{?post lodep:translated_to ?translated_post.}}';
        $results= SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)){
            foreach($jsnResults->results->bindings as $rs){
                if (empty($rs->translated_post->value)){
                   
                                            TranslationController::sendTranslation($rs->post->value, $rs->comment->value, $rs->lang->value);
                }
            }
        }
        return redirect()->intended('dashboard/espace-admin');

    }
    
    /**
     * Send DGT, the text to translate, in using java codd (.jar)
     * @param type $post: the uri of the comment
     * @param type $textToTranslate: the text to be translated
     * @param string $sourceLanguage: the lang_code (ex:en, fr,...) of the text
     */
    static public function sendTranslation($post, $textToTranslate, $sourceLanguage){
        $list_lang_code = Array("eng" =>"en","bul" =>"bg","ces" =>"cs","dan" =>"da","deu" =>"de","ell" =>"el","est" =>"et","fin" =>"fi","fra" =>"fr","gle" =>"ga","hrv" =>"hr","hun" =>"hu","ita" =>"it","lav" =>"lv","lit" =>"lt","mlt" =>"mt","nld" =>"nl","pol" =>"pl","por" =>"pt","ron" =>"ro","slk" =>"sk","spa" =>"es","srp" =>"sr","swe" =>"sv");
        $sourceLanguage = $list_lang_code[$sourceLanguage];
        if ($sourceLanguage==='sv') {
            $targetLanguage = str_replace(','.$sourceLanguage, '', 'en,bg,cs,da,de,el,et,fi,fr,ga,hr,hu,it,lv,lt,mt,nl,pl,pt,ro,sk,es,sr,sv');
        } else {
            $targetLanguage = str_replace($sourceLanguage.',', '', 'en,bg,cs,da,de,el,et,fi,fr,ga,hr,hu,it,lv,lt,mt,nl,pl,pt,ro,sk,es,sr,sv');
        }
        $requesterCallback = env('CALLBACK');
        $errorCallback = env('ERRORCALLBACK');
        chdir(base_path("app/Libraries"));
        $command ='java -cp send_dgt.jar lodep.Launcher "'.$textToTranslate.'" "'.$post.'" "'.$requesterCallback.'" "'.$errorCallback.'" "'.$sourceLanguage.'" "'.$targetLanguage.'" > /dev/null 2>/dev/null &';		
        exec($command);
    }
    
    public function backTranslation(Request $request){
        $uri = $request->get("uri");
        if ($uri !== null) {
            $post = Post::buildPostURIContainer(Session::get('user')->user_id->value, $uri);
        } else {
            $post = $request->get("post");
        }
        $textToTranslate = trim($request->get("textToTranslate"));
        $sourceLanguage = $request->get("sourceLanguage");
        $list_lang_code = Array("eng" =>"en","bul" =>"bg","ces" =>"cs","dan" =>"da","deu" =>"de","ell" =>"el","est" =>"et","fin" =>"fi","fra" =>"fr","gle" =>"ga","hrv" =>"hr","hun" =>"hu","ita" =>"it","lav" =>"lv","lit" =>"lt","mlt" =>"mt","nld" =>"nl","pol" =>"pl","por" =>"pt","ron" =>"ro","slk" =>"sk","spa" =>"es","srp" =>"sr","swe" =>"sv");
        $sourceLanguage = $list_lang_code[$sourceLanguage];
        if ($sourceLanguage==='sv') {
            $targetLanguage = str_replace(','.$sourceLanguage, '', 'en,bg,cs,da,de,el,et,fi,fr,ga,hr,hu,it,lv,lt,mt,nl,pl,pt,ro,sk,es,sr,sv');
        } else {
            $targetLanguage = str_replace($sourceLanguage.',', '', 'en,bg,cs,da,de,el,et,fi,fr,ga,hr,hu,it,lv,lt,mt,nl,pl,pt,ro,sk,es,sr,sv');
        }
        $requesterCallback = env('CALLBACK');
        $errorCallback = env('ERRORCALLBACK');
        chdir(base_path("app/Libraries"));
        $command ='java -cp send_dgt.jar lodep.Launcher "'.$textToTranslate.'" "'.$post.'" "'.$requesterCallback.'" "'.$errorCallback.'" "'.$sourceLanguage.'" "'.$targetLanguage.'" > /dev/null 2>/dev/null &';		
        exec($command);
    }


    /**
     *  get the target languages, i.e., selecte all languages without the language of the original text
     * @param type $lang_code: the lang_code of the original text.
     * @return string: list of target language code, ex: en,fr,....
     */

    static public function get_target_lang($lang_code){
            $query='SELECT ?lang_code WHERE{?s at:legacy-code ?lang_code. FILTER (?lang_code!="'.strtolower($lang_code).'"^^<http://www.w3.org/2000/01/rdf-schema#Literal>)}';
            $results= SPARQL::runSPARQLQuery($query);
            $jsnResults = json_decode($results);
            $target_langs = "";
            if (!empty($jsnResults)){
                            foreach($jsnResults->results->bindings as $rs){
                                             $target_langs.=$rs->lang_code->value.',';
                                    }
                    return substr($target_langs, 0, -1);
            }
            else {return "";}		
    }

    /**
     * The callback service, where DGT answer will be sended
     */
    public function callback(){		
        $targetLanguage = $_POST['targetLanguage'];//$deliveryUrl = $_POST['deliveryUrl'];
            $translatedText= $_POST['translatedText']; //
            $post = $_GET['externalReference'];		
            echo 'success callback ';//.$request_id;
            $this->load_answer_query($post, $translatedText,  $targetLanguage );
    }
    /**
     * The errorcallback, where DGT error will be sended
     */	
    public function errorcallback(){	
            //$request_id=$_POST['requestId']; $errorCode =$_POST["errorCode"];	
            $targetLanguage	=$_POST["targetLanguage"];
            $errorMessage =$_POST["errorMessage"];
            $post = $_GET['externalReference'];		
            echo 'success errorcallback ';//.$request_id;		
            $this->load_error_query($post, $errorMessage,  $targetLanguage);		
    }

    /**
     * get the lang_code from two letters to tree latters
     * @param type $lang_code: lang_code of two letters
     * @return type $lang_code: lang_code of three letters
     */
    public function get_eli_lang_code($lang_code) {
    $query = 'SELECT DISTINCT ?lang_code_3 WHERE { ?concept at:op-code ?lang_code_3; at:op-mapped-code ?mapped_concept. ?mapped_concept dc:source "iso-639-1"^^rdfs:Literal; at:legacy-code "' . strtolower($lang_code) . '"^^rdfs:Literal.}';
    $results = SPARQL::runSPARQLQuery($query);
    $jsnResults = json_decode($results);

        if (!empty($jsnResults)) {
            return strtolower($jsnResults->results->bindings[0]->lang_code_3->value); //$jsnResults->results->bindings[0]->lang_code_3->value;
        }
    }

    /**
     *  get the lang_code from three letters to two latters
     * @param type $eli_lang_code: lang_code in three letters
     * @return type lang_code: lan_code in two letters
     */
    static  public function get_lang_code($eli_lang_code) {
    $query = 'SELECT DISTINCT ?lang_code_2  WHERE {
            ?concept at:op-code "' . strtoupper($eli_lang_code) . '"^^rdfs:Literal.
            ?concept at:op-mapped-code ?mapped_concept. 
            ?mapped_concept dc:source "iso-639-1"^^rdfs:Literal.
            ?mapped_concept at:legacy-code ?lang_code_2. 
                    }';
    $results = SPARQL::runSPARQLQuery($query);
    $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            return strtolower($jsnResults->results->bindings[0]->lang_code_2->value); 
        }
    }

    /**
     * Our callback service call this function to put the answers at our triple stors.
     * @param type $post: the uri of the translated comment
     * @param type $translated_comment: the text of the translated comment
     * @param type $lang_code: the lang_code (two letters) of the translated comment
     */

    public function load_answer_query($post, $translated_comment, $lang_code){
                    //$eli_lang_code=$this->get_eli_lang_code($lang_code);
                    $list_eli_lang_code = Array ("en" =>"eng","bg" =>"bul","cs" =>"ces","da" =>"dan","de" =>"deu","el" =>"ell","et" =>"est","fi" =>"fin","fr" =>"fra","ga" =>"gle","hr" =>"hrv","hu" =>"hun","it" =>"ita","lv" =>"lav","lt" =>"lit","mt" =>"mlt","nl" =>"nld","pl" =>"pol","pt" =>"por","ro" =>"ron","sk" =>"slk","es" =>"spa","sr" =>"srp","sv" =>"swe");
                    $eli_lang_code = $list_eli_lang_code[strtolower($lang_code)];
                    $this->add_translated_post($post, $translated_comment, $eli_lang_code);		
    }

    /**
     * Our errorcallback service call this function to put the error message at our triple stors.
     * @param type $post: the uri of the translated comment
     * @param type $translated_comment: the text of the translated comment
     * @param type $lang_code: the lang_code (two letters) of the translated comment
     */
    public function load_error_query($post, $errorMessage, $lang_code){
                    $eli_lang_code=$this->get_eli_lang_code($lang_code);
                    $this->add_translated_post($post, $errorMessage, $eli_lang_code);		
    }

    /**
     * add the translated post at triple store..
     * @param type $post: the uri of the translated comment
     * @param type $translated_comment: the text of the translated comment
     * @param type $eli_lang_code: the lang_code (three letters) of the translated comment
     */

    public function add_translated_post($post, $translated_comment, $eli_lang_code) {

            $query = 'WITH <' . env('LOD_GRAPH') . '> 
                              DELETE { ?translated_post sioc:content ?content.}
                              INSERT { <' . $post . '_to_' . $eli_lang_code . '> sioc:content "' . trim($translated_comment) . '"@' . $eli_lang_code .'.
                                              <' . $post . '_to_' . $eli_lang_code . '> lodep:translated_from <' . $post . '>.
                                              <' . $post . '> lodep:translated_to <' . $post . '_to_' . $eli_lang_code . '>.
                                              <' . $post . '_to_' . $eli_lang_code . '> a sioc:Post.
                                            }
                              WHERE {OPTIONAL {?translated_post sioc:content ?content. 
                                            FILTER(?translated_post=<' . $post . '_to_' . $eli_lang_code . '>)}}';

            SPARQL::runSPARQLUpdateQuery($query);
    }
    /**
    * This function show the translated text
    * @param Request $request: the input paramets: the post and the eli_lang_code
    * @return string: the translated text.
    */
    public function getTranslatedText(Request $request){
        $post = $request->get('post');
        $target_eli_lang_code = $request->get('target_eli_lang_code');
        $query = 'SELECT ?content WHERE{<' . $post . '_to_' . strtolower($target_eli_lang_code) . '> sioc:content ?content.}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)){
            if(isset($jsnResults->results->bindings[0])){
                return $jsnResults->results->bindings[0]->content->value;
            } else {
                return trans('lodepart.not-translate');
            }
        }
    }
       
}
