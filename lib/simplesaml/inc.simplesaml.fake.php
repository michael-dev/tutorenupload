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

global $logoutUrl;

$AUTHGROUP = "tutor,tutor-master,ag-erstiwoche";
$ADMINGROUP = "ag-erstiwoche";

function requireAuth() {
	global $logoutUrl;
	$logoutUrl = "http://www.erstiwoche.de/";
}

function requireGroup($group) {
	requireAuth();
}

function authenticateUserAndGetUserData($group) {
	requireAuth();
	
	$returnArray = array();
	$returnArray["username"] = "exampleuser";
	$returnArray["fullname"] = "Example User";
	$returnArray["mail"] = "exa2mple@stura.tu-ilmenau.de";
	$returnArray["responsibility"] = "ba";
	return $returnArray;
}
