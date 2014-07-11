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

$SIMPLESAML = dirname(dirname(dirname(__FILE__)))."/simplesamlphp";
$SIMPLESAMLAUTHSOURCE = "wayfinder";
$AUTHGROUP = "tutor,ag-erstiwoche,admin,konsul";
$ADMINGROUP = "konsul,admin,ag-erstiwoche";

function getUserMail() {
  global $attributes;
  requireAuth();
  return $attributes["mail"][0];
}

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

function getUsername() {
  global $attributes;
  if (isset($attributes["eduPersonPrincipalName"]) && isset($attributes["eduPersonPrincipalName"][0])) 
    return $attributes["eduPersonPrincipalName"][0];
  if (isset($attributes["mail"]) && isset($attributes["mail"][0])) 
    return $attributes["mail"][0];
  return NULL;
}

function getFullName() {
  global $attributes;
  if (isset($attributes["displayName"]) && isset($attributes["displayName"][0])) 
    return $attributes["displayName"][0];
  if (isset($attributes["mail"]) && isset($attributes["mail"][0])) 
    return $attributes["mail"][0];
  return NULL;
}
