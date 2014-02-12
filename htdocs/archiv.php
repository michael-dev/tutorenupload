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

require_once '../lib/EditImage.php';
require_once '../lib/inc.simplesaml.php';
requireGroup($ADMINGROUP);

// nur auf einen POST-Request reagieren
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$num = count($_POST["liste"]);
	
	if ($num  > 0) {
		$ed = new EditImage();
	
		$zip = new ZipArchive;
		
		// Erstelle neue Datei 'archiv.zip' auf dem Server
		$res = $zip->open('archiv.zip', ZipArchive::CREATE);
		if ($res === TRUE) {
			for ($i = 0; $i<$num; $i++) {
				$r = $ed->getPictureByID((int) $_POST["liste"][$i]);
				if ($r["success"]) {
					$zip->addFromString($i.'.png', $r["result"]);
				}
			}
		}
		
		$zip->close();
	}
	
	header("Content-Type: application/zip");
	header('Content-Disposition: attachment; filename="archiv.zip"');
	header("Content-Length: " . filesize('archiv.zip'));
	
	$contents = file_get_contents('archiv.zip');
	// Lösche Datei 'archiv.zip' wieder
	unlink('archiv.zip');
	
	echo $contents;
}

# require_once("../lib/Zip.php");
# $zip = new ZipStream("dateiname.zip");
# $zip->addFile("binäre bilddate","bild123.png");
# $zip->finalize();

