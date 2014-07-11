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


requireGroup($ADMINGROUP); // Zugriff prüfen mit Gruppenrecht "ag-erstiwoche"
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

tr.new td {
	background: #F7819F;
}
</style>

<script type="text/javascript">
function handleCheckboxClick($box) {
	var checkBoxGroup = document.getElementsByName("liste[]");
	for (var i=0; i<checkBoxGroup.length; i++) {
		checkBoxGroup[i].checked = $box.checked;
	}
}
</script>

</head>

<body>

<div style="width: 700px;">

<img alt="Logo der ErstiWoche" src="erstiwoche.png">

<h1>Bildupload für Tutoren - Adminbereich</h1>

<p>Hallo, <?php echo getFullName(); ?>!
Hier kannst du den Upload einstellen und die hochgeladenen Bilder wieder downloaden.
Bitte logge dich unten nach dem Herunterladen wieder aus!</p>

<?php
$ed = new EditImage();

$active = $ed->getSetting('active');
$showPicture = $ed->getSetting('showPicture');
$state = $ed->getSetting('state');
$selectedYear;
$changed = false;

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
	$selectedYear = date('Y');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	switch (true) {
		case isset($_POST['settingsSend']):
			$message = '<p class="warning">Folgende Einstellung(en) wurden gespeichert: ';

			$old = $ed->getSetting('active');
			$active = isset($_POST['settingActiveCheckbox']);
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
			$showPicture = isset($_POST['settingShowPictureCheckbox']);
			if ($old != $showPicture) {
				if ($showPicture) {
					$ed->saveSetting('showPicture', 1);
				} else {
					$ed->saveSetting('showPicture', 0);
				}
				$message = $message.', Zeige unten Bilder?';
				$changed = true;
			}
			
			$old = $ed->getSetting('state');
			$state = $_POST['selectState'];
			if ($old != $state) {
				$ed->saveSetting('state', $state);
				$message = $message.', Upload für';
				$changed = true;
			}

			$message = $message.'</p>';
			
			$selectedYear = (int) $_POST['selectedYear'];
			break;
			
		case isset($_POST['selectYearSend']):
			$selectedYear = (int) $_POST['selectYear'];
			break;
			
		default:
			exit;
	}	
}
?>

<h3>Upload-Einstellungen</h3>

<?php if ($changed) echo $message; ?>

<form name="SettingsForm" action="admin.php" method="post">
	
    <table class="tabelle" width="400px" style="border-collapse: collapse; border: solid black 1px;">
	<tr>
	    <td width="200px"><b>Einstellung</b></td>
	    <td><b>Wert</b></td>
	</tr>
        <tr>
	    <td>Upload aktiviert?</td>
	    <td><input type="checkbox" name="settingActiveCheckbox" value="Liste ist aktiviert." <?php echo $active ? 'checked="checked"' : ''; ?> /></td>
        </tr>
        <tr>
	    <td>Zeige unten Bilder?</td>
	    <td><input type="checkbox" name="settingShowPictureCheckbox" value="Bilder werden angezeigt." <?php echo $showPicture ? 'checked="checked"' : ''; ?> /></td>
        </tr>
		<tr>
	    <td>Upload für</td>
	    <td>
			<select name="selectState">
				<option value="ba" <?php if ($state == "ba") echo "selected"; ?>>ErstiWoche</option>
				<option value="ma" <?php if ($state == "ma") echo "selected"; ?>>Mastereinführungstage</option>
			</select>
		</td>
        </tr>
		<tr>
	    <td>letzter Bilddownload</td>
	    <td><?php $t = $ed->getSetting('lastDownload'); echo ($t == 0) ? "noch keiner" : "am ".date('d.m.Y', $t)."<br />um ".date('G:i:s', $t)." Uhr"; ?></td>
        </tr>
        <tr>
	    <td colspan="2"><input type="submit" name="saveSettings" value="Einstellungen speichern" /></td>
        </tr>
    </table>
	
	<input type="hidden" name="selectedYear" value="<?php echo $selectedYear; ?>">
	<input type="hidden" name="settingsSend" value="">
</form>

<h3>Bisher hochgeladene Bilder</h3>

<p>Hier stíehst du alle Bilder, die die Tutoren im unten ausgewählten Jahr hochgeladen haben.
Die Anzeige ist ebenso davon abhängig, was bei "Upload für" eingestellt wurde.
Über den Button unten kannst du alle mit einem Häckchen ausgewählten Bilder in einem ZIP-Archiv herunterladen.
Alle rot markierten Zeilen sind neue Bilder, die seit dem letzten Download hinzugekommen sind.</p>

<form name="SelectYearForm" action="admin.php" method="post">
	<p>Jahr&nbsp;&nbsp;&nbsp;
		<select name="selectYear" onchange="this.form.submit()">
		<?php
		$r = $ed->getUploadedYears();
		// falls keine Daten vorhanden sind, zeige zumindest das aktuelle Jahr
		$r["result"] = $r["result"] + array(array("tutorenjahr" => date('Y')));
		
		foreach($r["result"] as $line) {
			$y = (int) $line["tutorenjahr"];
		?>
			<option <?php if ($selectedYear == $y) echo 'selected'; ?>><?php echo $y; ?></option>
		<?php
		}
		?>
		</select>
	</p>
	
	<input type="hidden" name="selectYearSend" value="">
</form>

<form name="ShowUploadsForm" action="archiv.php" method="post">
<table class="tabelle" width="700px" style="border-collapse: collapse; border: solid black 1px;">
    <tr>
	<td><input type="checkbox" onclick="handleCheckboxClick(this)" name="selectCheckbox" value="0" checked="checked" /></td>
	<?php if ($showPicture) { ?><td><b>Bild</b></td><?php } ?>
	<td><b>Status</b></td>
	<td><b>Name</b></td>
	<td><b>E-Mail</b></td>
	<td><b>Änderungsdatum</b></td>
    </tr>

<?php
$r = $ed->getListOfUploadedImagesAtYearAndState($selectedYear);

$isZero = (count($r["result"]) == 0);

if ($isZero) {
?>

    <tr>
	<td colspan="<?php echo $showPicture ? 6 : 5; ?>">Es gibt aktuell keine Uploads für die <?php echo ($state == "ma") ? "Mastereinführungstage " : "ErstiWoche "; echo $selectedYear; ?>.</td>
    </tr>

<?php
} else {
	$lastDownload = $ed->getSetting('lastDownload');
	foreach($r["result"] as $line) {
		$imageIsNew = ($line["uploaddatum"] > $lastDownload);
?>

    <tr <?php if ($imageIsNew) echo 'class="new"';?>>
	<td><input type="checkbox" name="liste[]" value="<?php echo $line["id"] ?>" <?php if ($imageIsNew) echo 'checked="checked"';?> /></td>
	<?php if ($showPicture) {?>
	<td><img alt="bild-<?php echo $line["id"] ?>" src="bild.php?id=<?php echo $line["id"] ?>" /></td>
	<?php } ?>
	<td><?php echo ($ed->getSetting('state') == "ba") ? "ba, ".$line["faculty"].", ".$line["course"] : "ma, ".$line["faculty"]; ?></td>
	<td><?php echo $line["name"]; ?></td>
	<td><?php echo $line["email"]; ?></td>
	<td><?php echo date('G:i:s', $line["uploaddatum"]); ?> Uhr<br /><?php echo date('d-m-Y', $line["uploaddatum"]); ?></td>
    </tr>

<?php
	}
}
?>

    <tr>
	<td colspan="<?php echo $showPicture ? 6 : 5; ?>"><input type="submit" name="submitButton" value="Bilder-Download als ZIP" <?php if ($isZero) echo 'disabled'; ?>></td>
    </tr>
</table>
</form>

<form action="<? echo htmlspecialchars($logoutUrl); ?>" method="POST">
	<p><br /><input type="submit" value="Logout"></p>
</form>

</div>

<br /><br />

</body>

</html>
