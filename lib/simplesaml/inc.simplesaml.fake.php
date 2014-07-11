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

$logoutUrl = "http://www.erstiwoche.de/";

$AUTHGROUP = "tutor,ag-erstiwoche";
$ADMINGROUP = "ag-erstiwoche";

function getUserMail() {
  return "example@stura.tu-ilmenau.de";
}

function requireAuth() {
}

function requireGroup($group) {
}

function getUsername() {
  return "exampleuser";
}

function getFullName() {
  return "Example User";
}
