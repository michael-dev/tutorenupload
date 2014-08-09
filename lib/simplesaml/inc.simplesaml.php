<?php
/**
 * Copyright (C) 2014 Michael Braun <michael-dev@fami-braun.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE, $attributes, $logoutUrl;

$SIMPLESAML = dirname(dirname(dirname(dirname(__FILE__))))."/simplesamlphp";
$SIMPLESAMLAUTHSOURCE = "wayfinder";
$AUTHGROUP = "tutor,tutor-master,ag-erstiwoche";
$ADMINGROUP = "ag-erstiwoche";

function requireAuth() {
  global $SIMPLESAML, $SIMPLESAMLAUTHSOURCE;
  global $attributes, $logoutUrl;

  require_once($SIMPLESAML.'/lib/_autoload.php');
  $as = new SimpleSAML_Auth_Simple($SIMPLESAMLAUTHSOURCE);
  $as->requireAuth();

  $attributes = $as->getAttributes();
  $logoutUrl = $as->getLogoutURL("http://www.erstiwoche.de/");
}

function requireGroup($group) {
	global $attributes;

	requireAuth();

	if (count(array_intersect(explode(",",$group), $attributes["groups"])) == 0) {
		header('HTTP/1.0 401 Unauthorized');
		include SGISBASE."/template/permission-denied.tpl";
		die();
	}
}

function authenticateUserAndGetUserData($gr) {
	global $attributes;
	
	// wenn Authentifikation nicht erfolgreich, wird hier abgebrochen
	requireGroup($gr);
	
	// baue Ergebnisse zusammen
	$returnArray = array();
	
	if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0])) 
		$returnArray["username"] = $attributes["eduPersonPrincipalName"][0];
	else if (isset($attributes["mail"]) && isset($attributes["mail"][0])) 
		$returnArray["username"] = $attributes["mail"][0];
	else
		$returnArray["username"] = NULL;
	
	if (isset($attributes["displayName"]) && isset($attributes["displayName"][0])) 
		$returnArray["fullname"] = $attributes["displayName"][0];
	else if (isset($attributes["mail"]) && isset($attributes["mail"][0])) 
		$returnArray["fullname"] = $attributes["mail"][0];
	else
		$returnArray["fullname"] = NULL;
		
	if (isset($attributes["displayName"]) && isset($attributes["displayName"][0])) 
		$returnArray["mail"] = $attributes["mail"][0];
	else
		$returnArray["mail"] = NULL;
	
	if (count(array_intersect(array("tutor"), $attributes["groups"])) == 0) {
		$returnArray["responsibility"] = "ba";
	} else {
		$returnArray["responsibility"] = "ma";
	}
	
	return $returnArray;
}
