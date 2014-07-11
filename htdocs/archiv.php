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

define("TEMPFILE", 'archiv.zip');

// nur auf einen POST-Request reagieren
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if (isset($_POST["liste"])) {
		$num = count($_POST["liste"]);
		
		if ($num  > 0) {
			$ed = new EditImage();
			$zip = new ZipArchive;
			
			// Erstelle neue Datei 'archiv.zip' auf dem Server
			$res = $zip->open(TEMPFILE, ZipArchive::CREATE);
			if ($res === TRUE) {
				for ($i = 0; $i<$num; $i++) {
					$r = $ed->getImageAsStringByID((int) $_POST["liste"][$i]);
					
					if ($r["success"]) {
						$zip->addFromString($r["result"].".jpg", $r["image"]);
					}
				}
			}
			
			$zip->close();
		}
		
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename='.TEMPFILE);
		
		$contents = file_get_contents(TEMPFILE);
		
		// Lösche temporäre Datei wieder
		unlink(TEMPFILE);
		
		$ed->saveSetting('lastDownload', time());
		
		echo $contents;
		
		# andere Variante:	
		# require_once("../lib/Zip.php");
		# $zip = new ZipStream("dateiname.zip");
		# $zip->addFile("binäre bilddate","bild123.jpeg");
		# $zip->finalize();
	} else {
		header('Content-Type: text/html; charset=utf-8');
		echo "Fehler! Keine Auswahl von Bildern getroffen.";
	}
}