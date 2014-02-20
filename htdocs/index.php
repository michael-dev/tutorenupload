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
require_once '../lib/EditImage.php';

if (CONFIG_ONLINE) require_once '../lib/inc.simplesaml.php';
else require_once '../lib/inc.simplesaml.fake.php';
// Zugriff prüfen mit Gruppenrecht "tutor, ag-erstiwoche"
requireGroup($AUTHGROUP);

$email = getUserMail();
$name = getFullName();

$ed = new EditImage();

$stateIsBa = ($ed->getSetting('state') == "ba");
?>

<html>

<head>

<title>Bildupload für Tutoren</title>

<script type="text/javascript">
function handleFacultySelection() {
	var sf = document.bildupload.selectFaculty;
	var sc = document.bildupload.selectCourse;
	
	// alle Optionen löschen
	for (var i=0; i<sc.options.length; i++) {
		sc.options[i] = null;
	}
	
	// je nach Auswahl neue Elemente einfügen
	var add;
	switch (sf.selectedIndex) {
		case 0: // EI
			add = new Option("Elektrotechnik und Informationstechnik", "ei", false, true); sc.options[0] = add;
			add = new Option("Medientechnologie", "mt", false, false); sc.options[1] = add;
			add = new Option("Werkstoffwissenschaft", "ww", false, false); sc.options[2] = add;
			break;
		case 1: // IA
			add = new Option("Informatik", "in", false, true); sc.options[0] = add;
			add = new Option("Ingenieurinformatik", "ii", false, false); sc.options[1] = add;
			add = new Option("Biomedizinische Technik", "bmt", false, false); sc.options[2] = add;
			add = new Option("Technische Kybernetik und Systemtheorie", "tks", false, false); sc.options[3] = add;
			break;
		case 2: // MB
			add = new Option("Fahrzeugtechnik", "fzt", false, true); sc.options[0] = add;
			add = new Option("Maschinenbau", "mb", false, false); sc.options[1] = add;
			add = new Option("Mechatronik", "me", false, false); sc.options[2] = add;
			add = new Option("Optische Systemtechnik / Optronik", "op", false, false); sc.options[3] = add;
			add = new Option("Polyvalenter Bachelor mit Lehramtsoption für berufs...", "la", false, false); sc.options[4] = add;
			break;
		case 3: // MN
			add = new Option("Biotechnische Chemie", "btc", false, true); sc.options[0] = add;
			add = new Option("Mathematik", "ma", false, false); sc.options[1] = add;
			add = new Option("Technische Physik", "tph", false, false); sc.options[2] = add;
			break;
		case 4: // WM
			add = new Option("Angewandte Medien- und Kommunikationswissenschaft", "amw", false, true); sc.options[0] = add;
			add = new Option("Medienwirtschaft", "mw", false, false); sc.options[1] = add;
			add = new Option("Wirtschaftsinformatik", "wi", false, false); sc.options[2] = add;
			add = new Option("Wirtschaftsingeneurwesen", "wiw", false, false); sc.options[3] = add;
	}
	
	return 0;
}

function fillSelectCourse() {
	var arr = document.bildupload.selectCourse.options;
	var add = new Option("Elektrotechnik und Informationstechnik", "ei", false, true); arr[0] = add;
	add = new Option("Medientechnologie", "mt", false, false); arr[1] = add;
	add = new Option("Werkstoffwissenschaft", "ww", false, false); arr[2] = add;
	
	document.bildupload.selectFaculty.selectedIndex = 0;
	
	return 0;
}
</script>

</head>

<body <?php if ($stateIsBa) echo 'onload="fillSelectCourse()"';?>>
<div style="width: 600px;">

<img alt="Logo der ErstiWoche" src="erstiwoche.png">

<h1>Bildupload für Tutoren der <?php echo $stateIsBa ? 'ErstiWoche' : 'Mastereinführungstage';?></h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Verhindern, dass Personen einen POST-Request absetzen können obwohl der Upload nicht aktiviert ist...
	if (!$ed->getSetting('active')) exit;
	
	$filename = $_FILES['datei']['tmp_name'];
	
	if (is_uploaded_file($filename) && isset($_POST["selectFaculty"])) {
		$fak = $_POST["selectFaculty"];
		
		$kurs = NULL;
		if ($stateIsBa && isset($_POST["selectCourse"])) {
			$kurs = $_POST["selectCourse"];
		}
		
		$r = $ed->resizeAndUpload($fak, $kurs, $name, $email, $filename);
		
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
	} else {
?>

<p style="font-size: 50pt;">WHATZZZ UP??? KEIN BOCK ODER WIE?</p>
<p>Da hast du wohl kein Bild ausgewählt! <a href="index.php">Hier</a> gehts zurück.</p>

<?php
	}
} else {
?>

<p>Wie sich in den letzten Jahren herausgestellt hat, bereitet uns und Euch jedes Jahr der Upload der Tutorenbilder immer wieder viel Arbeit.
Es müssen für jedes Jahr und für jeden Tutor neue Bilder ausgesucht, auf die entsprechende Größe skaliert, entsprechend benannt und auf den StuRa-Server hochgeladen werden.</p>

<p>Daher haben wir uns dazu entschieden, den Prozess des Bilduploads etwas zu automatisieren.
Das bedeutet für Euch, dass Ihr einfach nur noch ein Bild von Eurem Computer über das unten stehende Formular auswählen müsst.
Alles andere passiert automatisch und Ihr müsst Euch nicht mehr mit Dateitypen, -namen oder Bildgrößen rumplagen.</p>

<div style="border: black solid 1px; padding: 5px;">
<p>Hallo <?php echo $name; ?>,</p>

<?php
	if ($ed->getSetting('active')) {
?>

<form name="bildupload" action="index.php" method="post" enctype="multipart/form-data">
	<p>bitte wähle nun Dein Bild auf deinem PC aus!
	Da das Bild recht klein wird und Du gut erkennbar sein sollst, achte bitte darauf, dass das Bild möglichst nur Dein Gesicht beinhaltet.</p>
    <input name="datei" type="file" size="50" maxlength="<?php echo CONFIG_MAXIMAGESIZE; ?>" accept="image/*" /><br/>
	<p>Wähle bitte nun noch die Fakultät<?php if ($stateIsBa) echo ' und den Studiengang';?> aus, für die<?php if ($stateIsBa) echo ' / den';?> Du Tutor bist.</p>
	<p>Fakultät&nbsp;&nbsp;
		<select name="selectFaculty"<?php if ($stateIsBa) echo ' onchange="handleFacultySelection()"';?>>
			<option value="ei" selected>EI</option>
			<option value="ia">IA</option>
			<option value="mb">MB</option>
			<option value="mn">MN</option>
			<option value="wm">WM</option>
		</select>
		<?php if ($stateIsBa) { ?>
	&nbsp;&nbsp;&nbsp;&nbsp;Studiengang&nbsp;&nbsp;
		<select name="selectCourse">
			<option></option>
		</select>
		<?php } ?>
	</p>
    <p>Kontrolliere bitte noch einmal deine Angaben.
	Sende uns dann dein Bild mit einem Klick auf den Button zu.
	Beachte: Je nach Größe des ausgewählten Bildes und der Geschwindigkeit deiner Internetverbindung kann dies einige Zeit dauern.</p>
    <input name="absenden" type="submit" value="Abschicken" /><br/>
	<p>Wenn du jetzt dein Bild doch nicht hochladen willst, logge dich <a href="<?echo htmlspecialchars($logoutUrl);?>">HIER</a> wieder aus.</p>
</form>

<?php
	} else {
?>

<p>der Bilderupload ist im Moment deaktiviert.</p>
<p>Falls Du schon eine Aufforderung zur Einreichung des Bildes bekommen hast, melde Dich bitte bei <a href="mailto:it@erstiwoche.de">it@erstiwoche.de</a>.
Ansonsten wird demnächst unsere IT den Upload freigeben.</p>
<form action="<? echo htmlspecialchars($logoutUrl); ?>" method="POST">
	<p><br /><input type="submit" value="Logout"></p>
</form>

<?php
	}
?>

</div>

<?php
}
?>

</div>

<br /><br />

</body>

</html>
