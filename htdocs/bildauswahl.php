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

<script type="text/javascript" src="jquery-1.11.1.js"></script>
<script type="text/javascript">
// Konstanten
var svgName = "mysvg";
var uploadFormularName = "bildupload";
var SVGXOffset = 15;
var abweichung = 10;
var rectBorder = 2;

// folgende Konstanten / Variablen werden weiter unten initialisiert
var SVGWidth;
var SVGHeight;
var SVGImageString;
var rx;
var ry;
var rectWidth;
var rectHeight;

// Zustandsvariablen
var mousePressed;
var sperre;
var oldX;
var oldY;
var NS;
var EW;

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
}

function getVerticalPos(element) {
	/*
	var myHeight = 0;
 
    if( typeof( window.innerWidth ) == 'number' ) {
        //Non-IE
        myHeight = window.innerHeight;
    } else if( document.documentElement && ( document.documentElement.clientWidth || document.documentElement.clientHeight ) ) {
        //IE 6+ in 'standards compliant mode'
        myHeight = document.documentElement.clientHeight;
    } else if( document.body && ( document.body.clientWidth || document.body.clientHeight ) ) {
        //IE 4 compatible
        myHeight = document.body.clientHeight;
    }*/
	
	var scrOfY = 0;
 
    if (typeof(window.pageYOffset) == 'number') {
        //Netscape compliant
        scrOfY = window.pageYOffset;
    } else if (document.body && (document.body.scrollLeft || document.body.scrollTop)) {
        //DOM compliant
        scrOfY = document.body.scrollTop;
    } else if (document.documentElement && (document.documentElement.scrollLeft || document.documentElement.scrollTop)) {
        //IE6 standards compliant mode
        scrOfY = document.documentElement.scrollTop;
    }
	
	//var offset = element.getBoundingClientRect().top;
	//var bodyOffset = document.body.getBoundingClientRect().top;
	//parseInt($("#"+svgName).position().top);
	
    return parseInt(365 - scrOfY);
}

function init() {
	var svg = document.getElementById(svgName);
	svg.setAttribute("width", SVGWidth);
	svg.setAttribute("height", SVGHeight);
	svg.setAttribute("viewBox", "0 0 " + SVGWidth + " " + SVGHeight);
	svg.setAttribute("style", "border: solid black 2px; background-image: url(data:image/jpeg;base64," + SVGImageString + ")");
	
	var myRect = document.createElementNS("http://www.w3.org/2000/svg", "rect");
    myRect.setAttributeNS(null,"x",rx);
    myRect.setAttributeNS(null,"y",ry);
    myRect.setAttributeNS(null,"width",rectWidth);
	myRect.setAttributeNS(null,"height",calculateHight(rectWidth));
	myRect.setAttributeNS(null,"stroke-width",rectBorder + "px");
    myRect.setAttributeNS(null,"fill","none");
    myRect.setAttributeNS(null,"stroke","red");
	svg.appendChild(myRect);
}

function resetValues() {
	mousePressed = false;
	sperre = false;
}

function updateFormularValues(rechteck) {
	document.bildupload.imagex.value = rechteck.getAttributeNS(null,"x");
	document.bildupload.imagey.value = rechteck.getAttributeNS(null,"y");
	document.bildupload.imagew.value = rechteck.getAttributeNS(null,"width");
}

function handleEvents(evt) {
	var svg = document.getElementById(svgName);
	var rechteck = svg.childNodes[0];
	
	switch (evt.type) {
		case "mousedown":
			var x = evt.clientX;
			var y = evt.clientY;
			var currX = parseInt(rechteck.getAttributeNS(null,"x"));
			var currY = parseInt(rechteck.getAttributeNS(null,"y"));
			
			if (is_mouse_nw(x,y,currX,currY)) {
				NS = true;
				EW = false;
				sperre = true;
			} else if (is_mouse_ne(x,y,currX,currY)) {
				NS = true;
				EW = true;
				sperre = true;
			} else if (is_mouse_se(x,y,currX,currY)) {
				NS = false;
				EW = true;
				sperre = true;
			} else if (is_mouse_sw(x,y,currX,currY)) {
				NS = false;
				EW = false;
				sperre = true;
			} else {
				sperre = false;
			}
			
			mousePressed = true;
			
			oldX = x;
			oldY = y;
			break;
			
		case "mouseup":
			resetValues();
			break;
		
		case "mousemove":
			var x = evt.clientX;
			var y = evt.clientY;
			var currX = parseInt(rechteck.getAttributeNS(null,"x"));
			var currY = parseInt(rechteck.getAttributeNS(null,"y"));
			
			if (mousePressed) {
				if (sperre) {
					var currW = parseInt(rechteck.getAttributeNS(null,"width"));
					var currH = parseInt(rechteck.getAttributeNS(null,"height"));
					if ((NS == true) && (EW == true)) resize_rect(rechteck, currX, currY, currW, currH, x-oldX, oldY-y, NS, EW);
					else if ((NS == false) && (EW == true)) resize_rect(rechteck, currX, currY, currW, currH, x-oldX, y-oldY, NS, EW);
					else if ((NS == true) && (EW == false)) resize_rect(rechteck, currX, currY, currW, currH, oldX-x, oldY-y, NS, EW);
					else resize_rect(rechteck, currX, currY, currW, currH, oldX-x, y-oldY, NS, EW);
				} else {
					move_rect(rechteck, currX, currY, oldX-x, oldY-y);
				}
			} else {
				if (is_mouse_nw(x,y,currX,currY)) svg.style.cursor = "nw-resize";
				else if (is_mouse_ne(x,y,currX,currY)) svg.style.cursor = "ne-resize";
				else if (is_mouse_se(x,y,currX,currY)) svg.style.cursor = "se-resize";
				else if (is_mouse_sw(x,y,currX,currY)) svg.style.cursor = "sw-resize";
				else if (!sperre) svg.style.cursor = "move";
			}
			
			oldX = x;
			oldY = y;
			break;
		
		case "mouseenter":
			svg.style.cursor = "move";
			break;
		
		case "mouseleave":
			svg.style.cursor = "auto";
			resetValues();
			break;
    }
}

function equalsRange(x, value) {
	return x >= (value - abweichung) && x <= (value + abweichung);
}

function calculateHight(width) {
	return parseInt(parseInt(width) * 5 / 4);
}

function is_mouse_nw(x,y,currX,currY) {
	return equalsRange(x - SVGXOffset, currX) &&
		   equalsRange(y - getVerticalPos(), currY);
}

function is_mouse_ne(x,y,currX,currY) {
	return equalsRange(x - SVGXOffset, currX + rectWidth) &&
		   equalsRange(y - getVerticalPos(), currY);
}

function is_mouse_se(x,y,currX,currY) {
	return equalsRange(x - SVGXOffset, currX + rectWidth) &&
		   equalsRange(y - getVerticalPos(), currY + rectHeight);
}

function is_mouse_sw(x,y,currX,currY) {
	return equalsRange(x - SVGXOffset, currX) &&
	       equalsRange(y - getVerticalPos(), currY + rectHeight);
}

function resize_rect(rechteck, rectX, rectY, rectW, rectH, dx, dy, nordSouth, eastWest) {
	var d = (Math.max(dx, dy) > 0) ? Math.max(dx, dy) : Math.min(dx, dy);
	var newx = rectX - d;
	var newW = rectW + d;
	var newy = rectY - d;
	var newH = calculateHight(newW);
	if ((newW >= 15) && (newW + 10 < SVGWidth) && (newH >= 20) && (newH + 10 < SVGHeight)) {
		if (nordSouth) {
			if (eastWest) {
				rechteck.setAttributeNS(null,"y",newy);
			} else {
				rechteck.setAttributeNS(null,"x",newx);
				rechteck.setAttributeNS(null,"y",newy);
			}
		} else {
			if (eastWest) {
				// keine Aktion erforderlich
			} else {
				rechteck.setAttributeNS(null,"x",newx);
			}
		}
		
		rechteck.setAttributeNS(null,"width",newW);
		rechteck.setAttributeNS(null,"height",newH);
		rectWidth = newW;
		rectHeight =  newH;
		
		updateFormularValues(rechteck);
	}
}

function move_rect(rechteck, rectX, rectY, dx, dy) {
	var newx = rectX - dx;
	if ((newx >= 0) && (newx < (SVGWidth - rectWidth))) {
		rechteck.setAttributeNS(null,"x",newx);
	} else if (rectX + rectWidth >= SVGWidth) {
		rechteck.setAttributeNS(null,"x",parseInt(SVGWidth - rectWidth));
	} else if (rectX < 0) {
		rechteck.setAttributeNS(null,"x",0);
	}
	
	var newy = rectY - dy;
	if ((newy >= 0) && (newy <= SVGHeight - rectHeight)) {
		rechteck.setAttributeNS(null,"y",newy);
	} else if (rectY + rectHeight >= SVGHeight) {
		rechteck.setAttributeNS(null,"y",parseInt(SVGHeight - rectHeight));
	} else if (rectY < 0) {
		rechteck.setAttributeNS(null,"y",0);
	}
	
	updateFormularValues(rechteck);
}

$(document).ready(function() {
	// fuelle die Studiengangsliste (oder auch nicht ;-))
	<?php if ($stateIsBa) echo "fillSelectCourse();";?>

	
	// fuelle svg-Grafik
	init();
	
	// schreibe Startwerte für Automat
	resetValues();
	rectHeight = calculateHight(rectWidth);
	
	// Registrierung der Event-Listener
	$("#" + svgName)
		.mousedown(function(e) { handleEvents(e); })
		.mouseup(function(e) { handleEvents(e); })
		.mousemove(function(e) { handleEvents(e); })
		.mouseenter(function(e) { handleEvents(e); })
		.mouseleave(function(e) { handleEvents(e); });
		
	// der Handler für handleFacultySelection() wird weiter unten registriert
});
</script>
</head>

<body>
<div style="width: 600px;">

<img alt="Logo der ErstiWoche" src="erstiwoche.png">

<h1>Bildupload für Tutoren der <?php echo $stateIsBa ? 'ErstiWoche' : 'Mastereinführungstage';?></h1>

<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	// Verhindern, dass Personen einen POST-Request absetzen können obwohl der Upload nicht aktiviert ist...
	if (!$ed->getSetting('active')) exit;
	
	$filename = $_FILES['datei']['tmp_name'];
	
	if (is_uploaded_file($filename)) {
		if (filesize($filename) > CONFIG_PICTURE_MAXSIZE) {
?>

<p>Da war dein Bild wohl zu groß! <a href="index.php">Hier</a> gehts zurück.</p>

<?php
		} else {
			$r = $ed->detectFace($filename);
?>
<div id="content" style="border: black solid 1px; padding: 5px;">
<p>So, <?php echo $name; ?>!
<?php
			// $r["result"] enthält die Koordinaten des erkannten Gesichts
			// $r["image"] enthält das hochgeladene Bild
			if (($r["result"] != NULL) && ($r["image"] != NULL)) {
				if ($r["success"] == true) {
?>
Dein Bild wurde mittels einer Gesichtserkennung analysiert.
Je nach verwendetem Foto funktioniert dies mehr oder weniger gut.
Man sollte ein Foto verwenden, bei dem man möglichst gerade in die Linse schaut, ohne den Kopf zu weit zu drehen.
Jetzt sollte dein komplettes Gesicht mit einem roten Rechteck umramt sein.</p>
<p>Falls du nicht mit der Erkennung einverstanden bist, hast du jetzt noch die Möglichkeit, das rote Rechteck so hinzuschieben, dass es nur dein Kopf beinhaltet.
Bei Bedarf verändere auch die Größe des Rechtecks, indem Du die Ecken ziehst.</p>
<?php
				} else {
?>
Bei Deinem Bild schlug leider unsere Gesichtserkennung fehl.
Bitte umrahme jetzt dein komplettes Gesicht mit einem roten Rechteck.
Dazu schiebe das rote Rechteck bitte so hin, dass es nur dein Kopf beinhaltet.
Bei Bedarf verändere auch die Größe des Rechtecks, indem Du die Ecken ziehst.</p>
<?php
				}
?>
<script type="text/javascript">
SVGWidth = <?php echo $r["result"]["width"]; ?>;
SVGHeight = <?php echo $r["result"]["height"]; ?>;
SVGImageString = "<?php echo base64_encode($r["image"]); ?>";
rx = <?php echo $r["result"]["x"]; ?>;
ry = <?php echo $r["result"]["y"]; ?>;
rectWidth = <?php echo $r["result"]["w"]; ?>;
</script>
<?php
			} else {
				echo $r["error"];
			}
?>
<p><svg id="mysvg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" /></p>
<form name="bildupload" action="thanks.php" method="post">
	<br />
    <input name="image" type="hidden" value="<?php echo base64_encode($r["image"]); ?>" />
	<input name="imagex" type="hidden" value="<?php echo $r["result"]["x"]; ?>" />
	<input name="imagey" type="hidden" value="<?php echo $r["result"]["y"]; ?>" />
	<input name="imagew" type="hidden" value="<?php echo $r["result"]["w"]; ?>" />
	<p>Wähle bitte nun noch die Fakultät<?php if ($stateIsBa) echo ' und den Studiengang';?> aus, für die<?php if ($stateIsBa) echo ' / den';?> Du Tutor bist.</p>
	<p>Fakultät&nbsp;&nbsp;
		<select name="selectFaculty" <?php if ($stateIsBa) echo ' onchange="handleFacultySelection()"';?>>
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
	Sende uns dann dein Bild mit einem Klick auf den Button zu.</p>
    <input name="absenden" type="submit" value="So finde ich das gut!">
</form>
<form action="<? echo $logoutUrl; ?>">
	<p><input type="submit" value="Logout"></p>
</form>
<form action="index.php">
	<p><input type="submit" value="Zurück"></p>
</form>
</div>

<?php
		}
	} else {
?>

<p style="font-size: 50pt;">WHATZZZ UP??? KEIN BOCK ODER WIE?</p>
<p>Da hast du wohl kein Bild ausgewählt! <a href="index.php">Hier</a> gehts zurück.</p>

<?php
	}
}
?>

</div>

<br /><br />

</body>

</html>