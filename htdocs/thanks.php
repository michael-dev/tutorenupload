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

header('Content-Type: text/html; charset=utf-8');
require_once("../config/config.php");
require_once PATH_EDIT_IMAGE_CLASS;

if (CONFIG_ONLINE) require_once PATH_SIMPLESAML;
else require_once PATH_SIMPLESAML_FAKE;

requireGroup($AUTHGROUP); // Zugriff prüfen mit Gruppenrecht "tutor, ag-erstiwoche"

$email = getUserMail();
$name = getFullName();

$ed = new EditImage();

$stateIsBa = ($ed->getSetting('state') == "ba");
?>

<html>

<head>

	<title>Bildupload für Tutoren</title>
	
	<link rel="icon" href="http://www.erstiwoche.de/fileadmin/templates/gremien/ewo.ico" type="image/x-icon; charset=binary" />

</head>

<body>
<div style="width: 600px;">

<img alt="Logo der ErstiWoche" src="erstiwoche.png">

<h1>Bildupload für Tutoren der <?php echo $stateIsBa ? 'ErstiWoche' : 'Mastereinführungstage';?></h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Verhindern, dass Personen einen POST-Request absetzen können obwohl der Upload nicht aktiviert ist...
	if (!$ed->getSetting('active')) exit;
	
	if (isset($_POST["image"]) &&
	    isset($_POST["selectFaculty"]) &&
		isset($_POST["imagex"]) &&
		isset($_POST["imagey"]) &&
		isset($_POST["imagew"])) {
		
		$imagestring = base64_decode($_POST['image']);
		$fak = $_POST["selectFaculty"];
		
		$face = array(
			"x" => (int)$_POST["imagex"],
			"y" => (int)$_POST["imagey"],
			"w" => (int)$_POST["imagew"]
		);
		
		$kurs = NULL;
		if ($stateIsBa && isset($_POST["selectCourse"])) {
			$kurs = $_POST["selectCourse"];
		}
		
		$r = $ed->resizeAndUpload($fak, $kurs, $name, $email, $face, $imagestring);
		
		// in $r["result"] steht nun die Datenbank-ID des hochgeladenen Bildes
		// $r["image"] enthält das hochgeladene Bild
		if ($r["success"] == true) {
?>

<div style="border: black solid 1px; padding: 5px;">
<p>Danke, <?php echo $name; ?>! Dein Bild wurde erfolgreich hochgeladen und wird schnellstmöglich durch unsere IT in die Webseite eingearbeitet.</p>
<p>Dein Bild wird wie folgt auf der Webseite aussehen:</p>
<p><img alt="Dein Bild" src="data:image/jpeg;base64,<?php echo base64_encode($r["image"]); ?>" /></p>
<p>Nicht zufrieden mit deinem Bild? Dann lade <a href="index.php">hier</a> einfach ein neues hoch und überschriebe somit dein altes.
Ansonsten kannst du dich wieder ausloggen.</p>
<form action="<? echo $logoutUrl; ?>">
	<p><br /><input type="submit" value="Logout"></p>
</form>
</div>

<?php
		} else {
			echo $r["error"];
		}
	}
}
?>

</div>

<br /><br />

</body>

</html>
