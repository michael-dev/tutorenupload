<?php
/**
 * Copyright (C) 2014 Johannes Hein <johannes.hein@tu-ilmenau.de>
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

require_once("../config/config.php");
require_once PATH_EDIT_IMAGE_CLASS;

if (CONFIG_ONLINE) require_once PATH_SIMPLESAML;
else require_once PATH_SIMPLESAML_FAKE;

requireGroup($ADMINGROUP); // Zugriff prüfen mit Gruppenrecht "ag-erstiwoche"

if (isset($_GET["id"])) {

	$id = (int) $_GET["id"];

	$ed = new EditImage();

	$r = $ed->getImageAsStringByID($id);

	if ($r["success"]) {
		header('Content-Type: image/jpeg');
		header('Content-Length: '.strlen($r["image"]));
		header('Content-Disposition: inline; filename="'.$id.'.jpg"');
		header('Content-Transfer-Encoding: binary');

		echo $r["image"];
	} else {
		header('Content-Type: text/html; charset=utf-8');
		echo $r["error"];
	}
} else {
	header('Content-Type: text/html; charset=utf-8');
	echo "Fehler! Nötiger Parameter nicht vorhanden.";
}