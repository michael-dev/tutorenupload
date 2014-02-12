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
// Gruppenrecht: tutor-<Jahr>

header('Content-Type: text/html; charset=utf-8');
require_once '../lib/EditImage.php';
require_once '../lib/inc.simplesaml.php';
requireGroup($AUTHGROUP);
$email = getUserMail();
$name = getFullName();

$ed = new EditImage();
?>

<html>

<head>

<title>Bildupload für Tutoren</title>

</head>

<body>
<div style="width: 600px;">

<img alt="Logo der ErstiWoche" src="erstiwoche.png">

<h1>Bildupload für Tutoren</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Verhindern, dass Personen einen POST-Request absetzen können obwohl der Upload nicht aktiviert ist...
	if (!$ed->getSetting('active')) exit;
	
	$filename = $_FILES['datei']['tmp_name'];
	
	if (is_uploaded_file($filename)) {
		// in $r["result"] steht dann die Datenbank-ID des hochgeladenen Bildes
		$r = $ed->resizeAndUpload("Johannes", "Hein", $filename);
		if ($r["success"] == true) {
?>

<p>Danke! Dein Bild wurde erfolgreich hochgeladen und wird schnellstmöglich durch unsere IT in die Webseite eingearbeitet.</p>

<p>Dein Bild wird wie folgt auf der Webseite aussehen:</p>

<p><img alt="Dein Bild" src="data:image/png;base64,<?php echo base64_encode($r["image"]); ?>" /></p>

<p>Nicht zufrieden mit deinem Bild? Dann lade <a href="index.php">hier</a> einfach ein neues hoch und überschriebe somit Dein altes.</p>

<?php
		} else {
			echo $r["result"];
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

<?php
	if ($ed->getSetting('active')) {
?>

<p>Bitte wähle nun Dein Bild auf deinem PC aus! Da das Bild recht klein wird und Du gut erkennbar sein sollst, achte bitte darauf, dass das Bild nur Dein Gesicht beinhaltet.</p>

<form action="index.php" method="post" enctype="multipart/form-data">
    <input name="datei" type="file" size="50" maxlength="<?php echo EditImage::MAXIMAGESIZE ?>" accept="image/*" /><br/>
    <br/>
    <input name="absenden" type="submit" value="Abschicken" /><br/>
</form>

<?php
	} else {
?>

<p>Der Bilderupload ist im Moment deaktiviert.</p>
<p>Falls Ihr schon die Aufforderung zur Einreichung des Bildes bekommen habt, meldet Euch bitte bei <a href="mailto:it@erstiwoche.de">it@erstiwoche.de</a>.
Ansonsten wird demnächst unsere IT den Upload freigeben.</p>

<?php
	}
?>

</div>

<?php
}
?>

<p>Bitte nach Benutzen wieder <a href="<?echo $logoutUrl;?>">ausloggen</a>!</p>

</div>
</body>

</html>
