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

// Zugriff prüfen
// Gruppenrecht: ewo-<Jahr>

header('Content-Type: text/html; charset=utf-8');
require_once '../lib/EditImage.php';
require_once '../lib/inc.simplesaml.php';
requireGroup($ADMINGROUP);
?>

<html>

<head>

<title>Bildupload für Tutoren - Adminbereich</title>

<style>
.tabelle td {
    border: black solid 1px;
    padding: 3px;
    text-align: center;
}

.warning {
    color: red;
}
</style>

</head>

<body>

<div style="width: 700px;">

<img alt="Logo der ErstiWoche" src="erstiwoche.png">

<h1>Bildupload für Tutoren - Adminbereich</h1>

<h3>Upload-Einstellungen</h3>

<?php
$ed = new EditImage();
$active;
$showPicture;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	$message = '<p class="warning">Folgende Einstellung(en) wurden gespeichert:</p><p class="warning">';
	$changed = false;
	$old = $ed->getSetting('active');
	$active = isset($_POST['activeCheckbox']);
	if ($old != $active) {
		if ($active) {
			$ed->saveSetting('active', 1);
		} else {
			$ed->saveSetting('active', 0);
		}
		$message = $message.'Upload aktiviert?';
		$changed = true;
	}
	
	$old = $ed->getSetting('showPicture');
	$showPicture = isset($_POST['showPictureCheckbox']);
	if ($old != $showPicture) {
		if ($showPicture) {
			$ed->saveSetting('showPicture', 1);
		} else {
			$ed->saveSetting('showPicture', 0);
		}
		$message = $message.', Zeige unten Bilder?';
		$changed = true;
	}
	$message = $message.'</p>';
	
	if ($changed) echo $message;
} else {
	$active = $ed->getSetting('active');
	$showPicture = $ed->getSetting('showPicture');
}
?>

<form action="admin.php" method="post">
    <table class="tabelle" width="400px" style="border-collapse: collapse; border: solid black 1px;">
	<tr>
	    <td width="200px"><b>Einstellung</b></td>
	    <td><b>Wert</b></td>
	</tr>
        <tr>
	    <td>Upload aktiviert?</td>
	    <td><input type="checkbox" name="activeCheckbox" value="Liste ist aktiviert." <?php echo $active ? 'checked="checked"' : ''; ?> /></td>
        </tr>
        <tr>
	    <td>Zeige unten Bilder?</td>
	    <td><input type="checkbox" name="showPictureCheckbox" value="Bilder werden angezeigt." <?php echo $showPicture ? 'checked="checked"' : ''; ?> /></td>
        </tr>
        <tr>
	    <td colspan="2"><input type="submit" name="savesettings" value="Einstellungen speichern" /></td>
        </tr>
    </table>
</form>

<h3>Bisher hochgeladene Bilder</h3>

<p>Hier stíehst du alle Bilder, die die Tutoren bisher im Jahr <?php echo date("Y"); ?> hochgeladen haben.
Über den Button unten kannst du alle mit einem Häckchen ausgewählten Bilder in einem ZIP-Archiv herunterladen.</p>

<form action="archiv.php" method="post">
<table class="tabelle" width="700px" style="border-collapse: collapse; border: solid black 1px;">
    <tr>
	<td><input type="checkbox" name="selectCheckbox" value="0" checked="checked" /></td>
	<?php if ($showPicture) {?>
	<td><b>Bild</b></td>
	<?php } ?>
	<td><b>Name</b></td>
	<td><b>Änderungsdatum</b></td>
    </tr>

<?php
$r = $ed->getListOfUploadedImages();
$zero = (count($r["result"]) == 0);

if ($zero) {
?>

    <tr>
	<td colspan="<?php echo $showPicture ? 4 : 3; ?>">Es gibt aktuell keine Uploads für das Jahr <?php echo date("Y"); ?>.</td>
    </tr>


<?php
} else {
	foreach($r["result"] as $line) {
?>

    <tr>
	<td><input type="checkbox" name="liste[]" value="<?php echo $line["id"] ?>" checked="checked" /></td>
	<?php if ($showPicture) {?>
	<td><img alt="Bild-<?php echo $line["id"] ?>" src="<?php echo 'bild.php?id='.$line["id"] ?>" /></td>
	<?php } ?>
	<td><?php echo str_replace("'", "", $line["vorname"])." ".str_replace("'", "", $line["nachname"]); ?></td>
	<td><?php echo $line["uploaddatum"]; ?> Uhr</td>
    </tr>

<?php
	}
}
?>

    <tr>
	<td colspan="4"><input type="submit" name="submitButton" value="Bilder-Download als ZIP" <?php if ($zero) echo 'disabled'; ?>></td>
    </tr>
</table>
</form>

<h3>Logout und Zurück</h3>

<p>Bitte nach Benutzen wieder <a href="<?echo $logoutUrl;?>">ausloggen</a>!</p>

</div>
</body>

</html>
