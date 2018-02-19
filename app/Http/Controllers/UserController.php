<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use SPARQL;
use App\Images\ImageProcessing;

/**
 * This class is for the user controller
 * @author: Vivien Touly, Duy Dinh
 * @date: 22/07/2016 
 * * */
class UserController extends Controller {

    /**
     * Method for update token for reset password
     * @param type $userId
     * @param type $tokenId
     * @param type $tokenDate
     */
    public static function updateToken($userId, $tokenId, $tokenDate){
        $query = 'WITH <' . env('LOD_GRAPH') . '/users> 
                DELETE  {?user lodep:token_id ?token_id;
                lodep:token_date ?token_date.}
                INSERT {?user lodep:token_id "' . md5($tokenId) . '";
                lodep:token_date "' . $tokenDate . '"} 
                WHERE {
                ?user sioc:id "' . $userId . '".
                OPTIONAL{?user lodep:token_id ?token_id.}
                OPTIONAL{?user lodep:token_date ?token_date.} 				
                }';
       SPARQL::runSPARQLUpdateQuery($query);	
    }
    
    /**
     * Method for select user from token for password reset
     * @param type $tokenId
     * @return type
     */
    public static function selectUserFromToken($tokenId){
        $query = 'SELECT ?user_id ?label ?avatar ?role ?user_group ?user_name ?family_name ?nationality ?mail ?token_date
                WHERE{ ?user a sioc:UserAccount;
                foaf:mbox ?mail;
                lodep:token_id "' . md5($tokenId) . '";
                lodep:token_date ?token_date;
                sioc:id ?user_id;
                sioc:name ?label;	 
                sioc:avatar ?avatar;
                sioc:has_function ?role;
                sioc:member_of ?user_group;				
                foaf:name ?user_name;
                foaf:familyName ?family_name; 
                lodep:nationality ?nationality. }';
				
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults) && sizeof($jsnResults->results->bindings) > 0) {
            return $jsnResults->results->bindings[0];
        } else {
            return null;
        }
    }
    
    /**
     * Method for select one user
     * @param type $userId
     * @return type
     */
    public function selectUserFromId($userId){
        $query = 'SELECT ?user_id ?avatar ?role ?user_group ?user_name ?family_name ?nationality ?mail
                WHERE{ ?user a sioc:UserAccount;
                foaf:mbox ?mail;
                sioc:id ?user_id;
                sioc:name ?label;	 
                sioc:avatar ?avatar;
                sioc:has_function ?role;
                sioc:member_of ?user_group;				
                foaf:name ?user_name;
                foaf:familyName ?family_name; 
                lodep:nationality ?nationality;
                sioc:id \'' . $userId . '\'. }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults) && sizeof($jsnResults->results->bindings) > 0) {
            $user = $jsnResults->results->bindings[0];
            $user->user_group->value = str_replace(env('SITE_NAME') . '/user_group/','',$user->user_group->value);
            $user->nationality->value = str_replace(env('SITE_NAME') . '/nationality/','',$user->nationality->value);
            return \Response::json($user);
        } else {
            return null;
        }
    }
    
    /**
     * method to build user URI
     * @param type $userId
     * @return type
     */
    public static function buildUserURI($userId) {
        $userURI = sprintf("%s/user/id_user_%s", env('SITE_NAME'), $userId);
        return $userURI;
    }
    
    /**
     * Method to build user label URI
     * @param type $id
     * @param type $user
     * @return string
     */
    public static function buildUserLabel($id, $user) {
        $label = $user['first_name'] . '_' . $user['last_name'];
        $query = "SELECT * WHERE { ?user sioc:name '" . $label . "'}";
        $results = SPARQL::runSPARQLQuery($query);
        $jsonLabels = json_decode($results, true);
        if (sizeof($jsonLabels['results']['bindings']) == 0) {
            return $label;
        } else {
            return $label . "_" . $id;
        }
    }
    
    /**
     * Method to build user role URI
     * @param type $user
     * @return type
     */
    public static function buildUserRoleURI($user) {
        return env('SITE_NAME') . '/user_role/' . $user['role'];
    }
    
    /**
     * Method to build user group URI
     * @param type $user
     * @return type
     */
    public static function buildUserGroupURI($user) {
        return env('SITE_NAME') . '/user_group/' . str_replace(' ', '_', $user['group']);
    }
    
    /**
     * Method to build user nationality URI
     * @param type $user
     * @return type
     */
    public static function buildUserNationalityURI($user) {
        return env('SITE_NAME') . '/nationality/' . $user['nationality'];
    }

    /**
     * Relative location to the user avatar
     * @param type $avatar
     * @return type
     */
    public static function buildUserAvatarURI($avatar) {
        return env('SITE_NAME') . $avatar;
    }
	
     /**
      * Load default users
      * @param Request $request
      * @return type
      */
    function loadUsers() {
        $users = array(
            array('first_name' => 'admin', 'last_name' => 'admin',
                'group' => 'Publications Office', 'email' => 'lod@gmail.com',
                'password' => 'Lod2015*', 'role' => 'admin',
                'nationality' => 'France'),
            array('first_name' => 'Duy', 'last_name' => 'Dinh',
                'group' => 'infeurope', 'email' => 'dinhbaduy@gmail.com',
                'password' => 'Lod2015*', 'role' => 'admin',
                'nationality' => 'France')
            ,
            array('first_name' => 'Brahim', 'last_name' => 'Batouche',
                'group' => 'Publications Office', 'email' => 'brahim.batouche@gmail.com',
                'password' => 'Lod2015*', 'role' => 'admin',
                'nationality' => 'Luxembourg')
            ,
            array('first_name' => 'Vivien', 'last_name' => 'Touly',
                'group' => 'ARHS CUBE', 'email' => 'vivien.touly@ext.publications.europa.eu',
                'password' => 'Lod2015*', 'role' => 'admin',
                'nationality' => 'France')
        );

        foreach ($users as $user) {
            $userId = $this->idUsersMax() + 1;
            $userURI = $this->buildUserURI($userId);
            $label = $this->buildUserLabel($userId, $user);
            $role = $this->buildUserRoleURI($user);
            $group = $this->buildUserGroupURI($user);
            $nationality = $this->buildUserNationalityURI($user);
            $avatar = $this->buildUserAvatarURI("/images/avatars/avatar-default.png");
            $this->addUser($userURI, $userId, $user['first_name'], $user['last_name'], $label, $role, $group, $nationality, $avatar, $user['email'], $user['password']);
			$this->activateAccount($user['email']);
        }
        return redirect()->intended('/');
    }
    
    /**
     * Update additional information about the user
     * @param Request $request
     */
    public function updateUser(Request $request) {
        $userURI = $this->buildUserURI($request->get('id')); // build user URI from the user id.
        $query_begin = 'WITH GRAPH <' . env('LOD_GRAPH') . '/users> ';
        $query_delete = ' DELETE{';
        $query_insert = ' INSERT{';
        $query_where = ' WHERE{';
        // last name
        $lastName = $request->get('lastName');
        if (!empty($lastName)) {
            $query_delete .= ' <' . $userURI . '> foaf:familyName ?family_name.';
            $query_insert .= ' <' . $userURI . '> foaf:familyName "' . $lastName . '".';
            $query_where .= ' <' . $userURI . '> foaf:familyName ?family_name.';
        }
        // first name
        $firstName = $request->get('firstName');
        if (!empty($firstName)) {
            $query_delete .= ' <' . $userURI . '> foaf:name ?user_name.';
            $query_insert .= ' <' . $userURI . '> foaf:name "' . $firstName . '".';
            $query_where .= ' <' . $userURI . '> foaf:name ?user_name.';
        }
        // group
        $group = env('SITE_NAME') . '/user_group/' . str_replace(' ', '_', $request->get('group'));
        if (!empty($group)) {
            $query_delete .= ' <' . $userURI . '> sioc:member_of ?group.';
            $query_insert .= ' <' . $userURI . '> sioc:member_of <' . $group . '>.';
            $query_where .= ' <' . $userURI . '> sioc:member_of ?group.';
        }
        // nationality
        $nationality = env('SITE_NAME') . '/nationality/' . $request->get('nationality');
        if (!empty($nationality)) {
            $query_delete .= ' <' . $userURI . '> lodep:nationality ?nationality.';
            $query_insert .= ' <' . $userURI . '> lodep:nationality <' . $nationality . '>.';
            $query_where .= ' <' . $userURI . '> lodep:nationality ?nationality.';
        }
        $query_delete .= '}';
        $query_insert .= '}';
        $query_where .= '}';
        $query = $query_begin . $query_delete . $query_insert . $query_where;
        SPARQL::runSPARQLUpdateQuery($query);
    }
    
    /**
     * Update the user avatar
     * @param Request $request
     */
    public function updateAvatar(Request $request) {
        $userURI = $this->buildUserURI($request->get('id')); // build user URI from the user id.
        $query_begin = 'WITH GRAPH <' . env('LOD_GRAPH') . '/users> ';
        $query_delete = ' DELETE{';
        $query_insert = ' INSERT{';
        $query_where = ' WHERE{';
        // avatar
        if ($request->hasFile('avatar')) {
            $avatarFileName = ImageProcessing::processPhotoAndResize($request->file('avatar'), "images/avatars", 30, $request->get('id'));
            $avatarURI = $this->buildUserAvatarURI($avatarFileName);
            $query_delete .= ' <' . $userURI . '> sioc:avatar ?avatar.';
            $query_insert .= ' <' . $userURI . '> sioc:avatar <' . $avatarURI . '>.';
            $query_where .= ' <' . $userURI . '> sioc:avatar ?avatar.';
        } else {
            $avatarURI = $this->buildUserAvatarURI('/images/avatars/avatar-default.png');
            $query_delete .= ' <' . $userURI . '> sioc:avatar ?avatar.';
            $query_insert .= ' <' . $userURI . '> sioc:avatar <' . $avatarURI . '>.';
            $query_where .= ' <' . $userURI . '> sioc:avatar ?avatar.';
        }
        $query_delete .= '}';
        $query_insert .= '}';
        $query_where .= '}';
        $query = $query_begin . $query_delete . $query_insert . $query_where;
        SPARQL::runSPARQLUpdateQuery($query);
        return redirect()->intended('dashboard/espace-user?id=' . $request->get('id') . '&lang=' . $request->get('lang'));
    }
    
    /**
     * Update user password
     * @param Request $request
     */
    public function changePassword(Request $request) {
        $userId = $request->get('id');
        $userURI = $this->buildUserURI($userId);
        $currentPassword = $request->get('currentPassword');
        $selectQuery = 'SELECT * WHERE{ ?user a sioc:UserAccount; sioc:id \'' . $userId . '\'; lodep:password "' . md5($currentPassword) . '". }';
        $results = SPARQL::runSPARQLQuery($selectQuery);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults) && sizeof($jsnResults->results->bindings) > 0) {
            $newPassword = $request->get('newPassword');
            if ($newPassword === $request->get('confirmPassword')) {
                if (strlen($newPassword) > 5) {
                    $updateQuery = 'WITH GRAPH <' . env('LOD_GRAPH') . '/users>
                            DELETE{<' . $userURI . '> lodep:password ?password.} 
                            INSERT {<' . $userURI . '> lodep:password "' . md5($newPassword) . '".} 
                            WHERE {<' . $userURI . '> lodep:password ?password.}';
                    SPARQL::runSPARQLUpdateQuery($updateQuery);
                    return 3;
                } else {
                    return 2;
                }
            } else {
                return 1;
            }
        } else {
            return 0;
        }
    }

    /**
     * Method for add a new user
     * @param type $userURI
     * @param type $user_id
     * @param type $user_name
     * @param type $family_name
     * @param type $label
     * @param type $role
     * @param type $user_group
     * @param type $user_nationality
     * @param type $avatar
     * @param type $mail
     * @param type $password
     */
    public static function addUser($userURI, $user_id, $user_name, $family_name, $label, $role, $user_group, $user_nationality, $avatar, $mail, $password) {
        $query = 'INSERT IN GRAPH <' . env('LOD_GRAPH') . '/users>
            {<' . $userURI . '> a sioc:UserAccount.
            <' . $userURI . '> sioc:id "' . $user_id . '".
            <' . $userURI . '> sioc:name "' . $label . '".	
            <' . $userURI . '> sioc:avatar <' . $avatar . '> .
            <' . $userURI . '> sioc:has_function <' . $role . '>.
            <' . $userURI . '> sioc:member_of <' . $user_group . '>.	
            <' . $userURI . '> a foaf:Person.
            <' . $userURI . '> owl:sameAs <' . $userURI . '>.
            <' . $userURI . '> foaf:name "' . $user_name . '".
            <' . $userURI . '> foaf:familyName "' . $family_name . '". 
            <' . $userURI . '> a schema:Person. 
            <' . $userURI . '> lodep:nationality <' . $user_nationality . '>.
            <' . $userURI . '> foaf:mbox <'. md5($mail). '>. 
            <' . $userURI . '> lodep:password "'. md5($password).'".
            <' . $userURI . '> lodep:activated_account "false".
            }';
        SPARQL::runSPARQLUpdateQuery($query);
    }
    
    public static function activateAccount($mail){
        $query = 'WITH GRAPH <' . env('LOD_GRAPH') . '/users>
                  DELETE{?user lodep:activated_account ?active.} 
                  INSERT {?user lodep:activated_account "true".} 
                  WHERE {?user foaf:mbox <'. md5($mail). '>.
                         ?user lodep:activated_account ?active.}';
        SPARQL::runSPARQLUpdateQuery($query);
    }

    public static function isActiveAccount($mail){
         $query = 'SELECT ?user
                  WHERE {?user foaf:mbox <'. md5($mail). '>.
                         ?user lodep:activated_account "true".}';
         $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {return "true";}
        else {return "false";}
    }
    
   /**
    * Method for check login and password of user
    * @param type $mail
    * @param type $password
    */
    public static function validatePassword($mail, $password) {
        $query = 'SELECT ?user WHERE{ ?user foaf:mbox <' . md5($mail) . '>. ?user lodep:password "' . md5($password) . '".}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            $user_data = $this->selectMetaDataUser($jsnResults->results->bindings[0]->user->value);
            $jsnResultsUser = json_decode($user_data);
        } else {
            echo 'fail mail or password';
        }
    }

    /**
     * Method for select metadata of user
     * @param type $userURI
     * @return type
     */
    public static function selectMetaDataUser($userURI) {
        $query = 'SELECT ?user_id ?label ?avatar ?role ?user_group ?user_name ?family_name ?nationality ??active
                WHERE{
                <' . $userURI . '> a sioc:UserAccount;
                sioc:id ?user_id;
                sioc:name ?label;	 
                sioc:avatar ?avatar;
                sioc:has_function ?role;
                sioc:member_of ?user_group;				
                foaf:name ?user_name;
                foaf:familyName ?family_name; 
                lodep:nationality ?nationality;
                lodep:activated_account ?active.
                }';
        return SPARQL::runSPARQLQuery($query);
    }
    
     /**
      * Method to select user from his mail
      * @param type $mail
      * @return type
      */
    public static function selectUser($mail) {
        $query = 'SELECT ?user_id ?label ?avatar ?role ?user_group ?user_name ?family_name ?nationality ?mail ?active
                WHERE{
                ?user a sioc:UserAccount;
                foaf:mbox <' . md5($mail) . '>;
                foaf:mbox ?mail;
                sioc:id ?user_id;
                sioc:name ?label;	 
                sioc:avatar ?avatar;
                sioc:has_function ?role;
                sioc:member_of ?user_group;				
                foaf:name ?user_name;
                foaf:familyName ?family_name; 
                lodep:nationality ?nationality;
                lodep:activated_account ?active.
                }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults) && sizeof($jsnResults->results->bindings) > 0) {
            return $jsnResults->results->bindings[0];
        } else {
            return null;
        }
    }
    
    /**
     * Method to select user from his mail and password
     * @param type $mail
     * @param type $password
     * @return type
     */
    public static function connectUser($mail,$password) {
        //if (!UserController::is_active_account($mail)){echo "you should activate your account...";}
        $query = 'SELECT ?user_id ?label ?avatar ?role ?user_group ?user_name ?family_name ?nationality
                WHERE{
                ?user a sioc:UserAccount;
                foaf:mbox <' . md5($mail) . '>;
                lodep:password "' . md5($password) . '";
				lodep:activated_account ?active;
                sioc:id ?user_id;
                sioc:name ?label;	 
                sioc:avatar ?avatar;
                sioc:has_function ?role;
                sioc:member_of ?user_group;				
                foaf:name ?user_name;
                foaf:familyName ?family_name; 
                lodep:nationality ?nationality.
                }';
        
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults) && sizeof($jsnResults->results->bindings) > 0) {
            return $jsnResults->results->bindings[0];
        } else {
            return null;
        }
    }
    
    
     /**
      * Method for create a new id
      * @return type
      */
    public static function idUsersMax() {
    	$query = 'SELECT MAX(strdt(?uid, xsd:integer)) as ?max WHERE{ ?user a sioc:UserAccount ; sioc:id ?uid . }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
	if (!empty($jsnResults) && sizeof($jsnResults->results->bindings) > 0 && property_exists($jsnResults->results->bindings[0],"max")) {
            return $jsnResults->results->bindings[0]->max->value;
	} else {
	    return 0;
	}
    }
    
     /**
      * Method for count nb total users
      * @return type
      */
    public static function nbUsers() {
        $query = 'SELECT count(?user) as ?nb WHERE{ ?user a sioc:UserAccount. }';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            return $jsnResults->results->bindings[0]->nb->value;
        }
    }

    /**
     * Method for forget password
     * @param type $mail
     */
    public static function forgetPassword($mail) {
        $query = 'SELECT ?user WHERE{?user foaf:mbox <' . md5($mail) . '>.".}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            $userURI = $jsnResults->results->bindings[0]->user->value;
            $this->updatePassword($userURI, $password);
        } else {
            echo 'fail mail ';
        }
    }

    /**
     * Method for ammend password
     * @param type $mail
     * @param type $password
     */
    public static function ammend_password($mail, $password) {
        $query = 'SELECT ?user WHERE{?user foaf:mbox <' . md5($mail) . '>. ?user lodep:password "' . md5($password) . '".}';
        $results = SPARQL::runSPARQLQuery($query);
        $jsnResults = json_decode($results);
        if (!empty($jsnResults)) {
            $userURI = $jsnResults->results->bindings[0]->user->value;
            $last_password = $jsnResults->results->bindings[0]->password->value;
            // open windows to check the validation of the password $check_password
            if (md5($check_password) === md5($last_password)) {
                // consider the ammendment by ubdating the $new_password..
                ubdate_password($userURI, $new_password);
            } else {
                echo "the password not match";
            }
        } else {
            echo 'fail mail or password';
        }
    }

    /**
     * Merhod to update password
     * @param type $userURI
     * @param type $password
     */
    public static function updatePassword($userURI, $password) {
        $query = 'WITH GRAPH <' . env('LOD_GRAPH') . '/users>
		DELETE{<' . $userURI . '> lodep:password ?password.} 
		INSERT {<' . $userURI . '> lodep:password "' . md5($password) . '".} 
		WHERE {<' . $userURI . '> lodep:password ?password.}';
        SPARQL::runSPARQLUpdateQuery($query);
    }
}