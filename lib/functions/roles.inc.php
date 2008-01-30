<?php
/**
 * TestLink Open Source Project - http://testlink.sourceforge.net/
 * 
 * @filesource $RCSfile: roles.inc.php,v $
 * @version $Revision: 1.43 $
 * @modified $Date: 2008/01/30 20:37:44 $ by $Author: schlundus $
 * @author Martin Havlat, Chad Rosen
 * 
 * This script provides the get_rights and has_rights functions for
 *           verifying user level permissions.
 *
 *
 * Default USER RIGHTS:
 *
 * 'guest' 	- testplan_metrics, mgt_view_tc, mgt_view_key
 * 'tester' - testplan_execute, testplan_metrics
 * 'senior tester' 	- testplan_execute, testplan_metrics, mgt_view_tc, mgt_modify_tc, mgt_view_key
 * 'lead' 	- testplan_execute, testplan_create_build, testplan_metrics, testplan_planning, testplan_assign_rights,
 *				mgt_view_tc, mgt_modify_tc, mgt_view_key, mgt_modify_key
 * 'admin' 	- testplan_execute, testplan_create_build, testplan_metrics, testplan_planning, testplan_assign_rights,
 *				mgt_view_tc, mgt_modify_tc, mgt_view_key, mgt_modify_key,
 *				mgt_modify_product, mgt_users
 *
 *
 * OPTIONS: FUNCTIONALITY ALLOWED FOR USER:
 * 
 * mgt_view_tc, testplan_metrics, mgt_view_key - allow browse basic data
 * testplan_execute - edit Test Results
 * mgt_modify_tc - edit Test Cases
 * mgt_modify_key - edit Keywords
 * mgt_modify_req - edit Requirements
 * testplan_planning, testplan_create_build, testplan_assign_rights - Test Leader plans/prepares a testing
 * mgt_modify_product, mgt_users - just Admin edits Products and Users
 *
 *
 *       20070901 - franciscom - BUGID 1016
 *       20070819 - franciscom - added get_tplan_effective_role(), get_tproject_effective_role()
 */
 
require_once( dirname(__FILE__). '/lang_api.php' );

// 
// This can seems weird but we have this problem:
//
// lang_get() is used to translate user rights description and needs $_SESSION info.
// If no _SESSION info is found, then default locale is used.
// We need to be sure _SESSION info exists before using lang_get(); in any module.
//  
// Then we need to explicitily init this globals to get right localization.
// With previous implementation we always get localization on TL DEFAULT LOCALE
//
init_global_rights_maps();


/*
  function: init_global_rights_maps
            init global map with user rights and user rights description localized.
            

  args :
  
  returns: 

*/

function init_global_rights_maps()
{
// Important:
// Every array, defines a section in the define role page
//
global $g_rights_tp;
global $g_rights_mgttc;
global $g_rights_kw;
global $g_rights_req;
global $g_rights_product;
global $g_rights_cf;
global $g_rights_users_global;
global $g_rights_users;
global $g_propRights_global;
global $g_propRights_product;


$g_rights_tp = array (	"testplan_execute" => lang_get('desc_testplan_execute'),
						"testplan_create_build" => lang_get('desc_testplan_create_build'),
						"testplan_metrics" => lang_get('desc_testplan_metrics'),
						"testplan_planning" => lang_get('desc_testplan_planning'),
						"testplan_user_role_assignment" => lang_get('desc_user_role_assignment'),
					);

					
$g_rights_mgttc = array (	"mgt_view_tc" => lang_get('desc_mgt_view_tc'),
							"mgt_modify_tc" => lang_get('desc_mgt_modify_tc'),
							"mgt_testplan_create" => lang_get('mgt_testplan_create'),
						);

$g_rights_kw = array (	
							"mgt_view_key" => lang_get('desc_mgt_view_key'),
							"mgt_modify_key" => lang_get('desc_mgt_modify_key'),
						);
$g_rights_req = array (	
							"mgt_view_req" => lang_get('desc_mgt_view_req'),
							"mgt_modify_req" => lang_get('desc_mgt_modify_req'),
						);

						
$g_rights_product = array (	
							"mgt_modify_product" => lang_get('desc_mgt_modify_product'),
						);						

// 20061231 - franciscom
$g_rights_cf = array (	
							"cfield_view" => lang_get('desc_cfield_view'),
							"cfield_management" => lang_get('desc_cfield_management'));


$g_rights_users_global = array (	
							"mgt_users" => lang_get('desc_mgt_modify_users'),
							"role_management" => lang_get('desc_role_management'),
							"user_role_assignment" => lang_get('desc_user_role_assignment'),
							"mgt_view_events" => lang_get('desc_mgt_view_events')
							); 
						
						
$g_rights_users = $g_rights_users_global;
						
						
$g_propRights_global = array_merge($g_rights_users_global,$g_rights_product);
$g_propRights_product = array_merge($g_propRights_global,$g_rights_mgttc,$g_rights_kw,$g_rights_req);
}

/** 
* function takes a roleQuestion from a specified link and returns whether 
* the user has rights to view it
*/
function has_rights(&$db,$roleQuestion,$tprojectID = null,$tplanID = null)
{
	return $_SESSION['currentUser']->hasRight($db,$roleQuestion,$tprojectID,$tplanID);
}

/*
  function: 

  args :
   
  returns: 
*/
function propagateRights($fromRights,$propRights,&$toRights)
{
	//the mgt_users right isn't product-related so this right is inherited from
	//the global role (if set)
	foreach($propRights as $right => $desc)
	{
		if (in_array($right,$fromRights) && !in_array($right,$toRights))
			$toRights[] = $right;
	}
}

/**
 * Function-Documentation
 *
 * @param type $rights documentation
 * @param type $roleQuestion documentation
 * @param type $bAND [default = 1] documentation
 * @return type documentation
 *
 * @author Andreas Morsing <schlundus@web.de>
 * @since 20.02.2006, 20:30:07
 *
 **/
function checkForRights($rights,$roleQuestion,$bAND = 1)
{
	$ret = null;
	//check to see if the $roleQuestion variable appears in the $roles variable
	if (is_array($roleQuestion))
	{
		$r = array_intersect($roleQuestion,$rights);
		if ($bAND)
		{
			//for AND all rights must be present
			if (sizeof($r) == sizeof($roleQuestion))
				$ret = 'yes';
		}	
		else 
		{
			//for OR one of all must be present
			if (sizeof($r))
				$ret = 'yes';
		}	
	}
	else
		$ret = (in_array($roleQuestion,$rights) ? 'yes' : null);
	
	return $ret;
}

/*
  function: get_tproject_effective_role()
            Get info about user(s) role at test project level,
            with indication about the nature of role: inherited or assigned.
             
            To get a user role we consider a 3 layer model:
            
            layer 1 - user           <--- uplayer
            layer 2 - test project   <--- in this fuction we are interested in this level.
            layer 3 - test plan
            
            
  
  args : $tproject_id
         [$user_id]
  
  returns: map with effetive_role in context ($tproject_id)
           key: user_id 
           value: map with keys:
                  login                (from users table - useful for debug)
                  user_role_id         (from users table - useful for debug)
                  uplayer_role_id      (always = user_role_id)
                  uplayer_is_inherited
                  effective_role_id  user role for test project
                  is_inherited
  

*/
//SCHLUNDUS: removed code by using the roles in the tlUser class, so additional queries for the testproject-user-roles aren't needed
//also removed the SQL related code, should be done inside the users class
//added the user and the effective role object to avoid queries later
function get_tproject_effective_role(&$db,$tproject_id,$user_id = null)
{
	$effective_role = array();
	if (!is_null($user_id))
		$users = tlUser::getByIDs($db,(array)$user_id);
	else
		$users = tlUser::getAll($db);
	if ($users)
	{
		foreach($users as $id => $user)
		{
			$isInherited = 1;
			$effectiveRoleID = $user->globalRoleID;
			$effectiveRole = $user->globalRole;
			if(isset($user->tprojectRoles[$tproject_id]))
			{
				$isInherited = 0;
				$effectiveRoleID = $user->tprojectRoles[$tproject_id]->dbID;
				$effectiveRole = $user->tprojectRoles[$tproject_id];
			}  

			$effective_role[$id] = array('login' => $user->login,
										 'user' => $user,
										 'user_role_id' => $user->globalRoleID,
										 'uplayer_role_id' => $user->globalRoleID,
										 'uplayer_is_inherited' => 0,
										 'effective_role_id' => $effectiveRoleID,
										 'effective_role' => $effectiveRole,
										 'is_inherited' => $isInherited);
		}  
	}
	return $effective_role;
}


/*
  function: get_tplan_effective_role()
            Get info about user(s) role at test plan level,
            with indication about the nature of role: inherited or assigned.

            To get a user role we consider a 3 layer model:
            
            layer 1 - user
            layer 2 - test project   <--- uplayer
            layer 3 - test plan      <--- in this fuction we are interested in this level.
             
  
  args : $tplan_id
         $tproject_id
         [$user_id]
  
  returns: map with effetive_role in context ($tplan_id)
           key: user_id 
           value: map with keys:
                  login                (from users table - useful for debug)
                  user_role_id         (from users table - useful for debug)
                  uplayer_role_id      user role for test project
                  uplayer_is_inherited 1 -> uplayer role is inherited 
                                       0 -> uplayer role is written in table
                                       
                  effective_role_id    user role for test plan
                  is_inherited       
  

*/
//SCHLUNDUS: combined the two loops to one
//added the user and the effective role object to avoid queries later
function get_tplan_effective_role(&$db,$tplan_id,$tproject_id,$user_id = null)
{
	$effective_role = get_tproject_effective_role($db,$tproject_id,$user_id);   

	foreach($effective_role as $user_id => $row)
	{
		$isInherited = 1;
		$effective_role[$user_id]['uplayer_role_id'] = $effective_role[$user_id]['effective_role_id'];
		$effective_role[$user_id]['uplayer_is_inherited'] = $effective_role[$user_id]['is_inherited'];
		
		if(isset($row['user']->tplanRoles[$tplan_id]))
		{
			$isInherited = 0;
			$effective_role[$user_id]['effective_role_id'] = $row['user']->tplanRoles[$tplan_id]->dbID;  
			$effective_role[$user_id]['effective_role'] = $row['user']->tplanRoles[$tplan_id];
		}

		$effective_role[$user_id]['is_inherited'] = $isInherited;
	}
	return $effective_role;
}

function getRoleErrorMessage($code)
{
	$msg = 'ok';
	switch($code)
	{
		case tlRole::E_NAMEALREADYEXISTS:
			$msg = lang_get('error_duplicate_rolename');
			break;
		case tlRole::E_NAMELENGTH:
			$msg = lang_get('error_role_no_rolename');
			break;
		case tlRole::E_EMPTYROLE:
			$msg = lang_get('error_role_no_rights');
			break;
		case tl::OK:
			break;
		case ERROR:
		case tlRole::E_DBERROR:
		default:
			$msg = lang_get('error_role_not_updated');
	}
	return $msg;
}
?>
