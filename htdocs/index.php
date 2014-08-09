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

// Zugriff prüfen mit Gruppenrecht "tutor, tutor-master, ag-erstiwoche"
$user = authenticateUserAndGetUserData($AUTHGROUP); 

$ed = new EditImage();

$stateIsBa = (strcmp($user["responsibility"], "ba") == 0);
$termIsWS = (strcmp($ed->getSetting('term'), "ws") == 0);

if (!$termIsWS && $stateIsBa) die("FEHLER #666<br />Bitte melde den Fehler an unsere IT.");
?>

<html>

<head>

	<title>Bildupload für Tutoren</title>
	
	<link rel="icon" href="http://www.erstiwoche.de/fileadmin/templates/gremien/ewo.ico" type="image/x-icon; charset=binary" />

</head>

<body>
<div style="width: 600px;">

<img alt="Logo der ErstiWoche" src="erstiwoche.png">

<h1>Bildupload für Tutoren der <?php echo $termIsWS ? 'ErstiWoche' : 'Mastereinführungstage';?></h1>

<p>Wie sich in den letzten Jahren herausgestellt hat, bereitet uns und Euch jedes Jahr der Upload der Tutorenbilder immer wieder viel Arbeit.
Es müssen für jedes Jahr und für jeden Tutor neue Bilder ausgesucht, auf die entsprechende Größe skaliert, entsprechend benannt und auf den StuRa-Server hochgeladen werden.</p>

<p>Daher haben wir uns dazu entschieden, den Prozess des Bilduploads etwas zu automatisieren.
Das bedeutet für Euch, dass Ihr einfach nur noch ein Bild von Eurem Computer über das unten stehende Formular auswählen müsst.
Alles andere passiert automatisch und Ihr müsst Euch nicht mehr mit Dateitypen, -namen oder Bildgrößen rumplagen.</p>

<div style="border: black solid 1px; padding: 5px;">
<p>Hallo <?php echo $user["fullname"]; ?>,</p>

<?php
if ($ed->getSetting('active')) {
?>

<form action="bildauswahl.php" method="post" enctype="multipart/form-data">
	<p>bitte wähle nun Dein Bild auf deinem PC aus!
	Welches Bild du auswählst, ist tendenziell egal - es sollte lediglich nicht zu groß sein.
	Die Grenze liegt hier bei <?php echo CONFIG_PICTURE_MAXSIZE/1000000; ?>MB.
	Du bekommst auch die Möglichkeit im nächsten Schritt ein Ausschnitt Deines Bildes auswählen.</p>
    <input name="datei" type="file" size="50" maxlength="<?php echo CONFIG_PICTURE_MAXSIZE; ?>" accept="image/*" /><br/>
    <p>Beachte: Je nach Größe des ausgewählten Bildes und der Geschwindigkeit deiner Internetverbindung kann das Absenden einige Zeit dauern.</p>
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

</div>

<br /><br />

</body>

</html>