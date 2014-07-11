<?php
/**
 * Copyright (C) 2014 Johannes Hein <johannes.hein@tu-ilmenau.de>
 * Copyright (C) 2014 Michael Braun <michael-dev@fami-braun.de>
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
require_once PATH_FACE_DETECTION_CLASS; 

// To use this class, enable the pdo-database driver on the server in file 'php.ini'.
// To do this, comment out the line 'extension=php_pdo_mysql.dll' in 'php.ini' for a mysql database.
class EditImage {

    private $database;

	private $settings = array();
	
    function __construct() {
		// Einstellungen definieren
		array_push(
			$this->settings,
			array("name" => "showPicture",   "initialValue" => 0),
			array("name" => "state",         "initialValue" => "ba"),
			array("name" => "lastDownload",  "initialValue" => 0),
			array("name" => "active",        "initialValue" => 0)
		);
		
		try {
            $this->database = new PDO('mysql:host='.CONFIG_DBHOST.';dbname='.CONFIG_DBNAME, CONFIG_DBUSER, CONFIG_DBPASS);
			$this->initalizeDB();
        } catch (PDOException $e) {
            die("<br/><br/>Error! ".$e->getMessage()."<br/><br/>");
        }
    }
	
    function __destruct() {
        $this->database = null;
    }

	private function returnValue($succ = false, $pictureData = NULL, $res = NULL, $err = "Unknown error occurred.") {
		return array(
			"success" => $succ,
			"image"   => $pictureData,
			"result"  => $res,
			"error"   => $err
		);
	}
	
    public function initalizeDB() {
		self::checkIfTableExistsAndCreateIfNot(TABSETTINGSNAME, TABSETTINGSSCHEME);
		self::checkSettingsAndPossibleCreateThem();
        self::checkIfTableExistsAndCreateIfNot(TABDATANAME, TABDATASCHEME);
    }

    private function checkIfTableExistsAndCreateIfNot($name, $schema) {
		// Nachschauen, ob Tabelle vorhanden
		$success = $this->database->query("SELECT COUNT(*) FROM ".$name);
		
		if ($success === false) {
			// Erstelle Tabelle
			$stmt = $this->database->prepare("CREATE TABLE `".$name."` (".$schema.")");
			$r = $stmt->execute();
			
			if ($r === false) {
				// Falls Tabelle nicht erstellt werden kann, gib Fehler aus
				var_dump($stmt->errorInfo());
				die("<br/><br/>FEHLER! Die Tabelle '".$name."' konnte nicht in der Datenbank erstellt werden!<br/><br/>");
			}
        }
    }

	private function checkSettingsAndPossibleCreateThem() {
		foreach ($this->settings as $e) {
			$stmt = $this->database->prepare("SELECT * FROM ".TABSETTINGSNAME." WHERE feature = ?");
			$success = $stmt->execute(array($e["name"]));
			
			if (count($stmt->fetchAll()) < 1) {
				// wenn nicht, erstelle und intialisiere sie
				$stmt = $this->database->prepare("INSERT INTO `".TABSETTINGSNAME."` (feature, value) VALUES (?,?)");
				$r = $stmt->execute(array($e["name"], $e["initialValue"]));
					
				if ($r === false) {
					// Falls Eigenschaft nicht erstellt werden kann, gib Fehler aus und brich ab
					var_dump($stmt->errorInfo());
					die("<br/><br/>Fehler! Einstellung '".$e["name"]."' konnte nicht in der Tabelle '".TABSETTINGSNAME."' erstellt werden!<br/><br/>");
				}
			}
		}
    }

    public function saveSetting($name, $value) {
		$settingNameIsValid = false;
		foreach ($this->settings as $e) {
			if ($e["name"] == $name) {
				$settingNameIsValid = true;
				break;
			}
		}
		
		if ($settingNameIsValid) {
			$stmt = $this->database->prepare("UPDATE ".TABSETTINGSNAME." SET value = '".$value."' WHERE feature = '".$name."'");
			$stmt->execute();
        }
    }

    public function getSetting($name) {
		$settingNameIsValid = false;
		foreach ($this->settings as $e) {
			if ($e["name"] == $name) $settingNameIsValid = true;
		}
		
		if ($settingNameIsValid) {
			$stmt = $this->database->prepare("SELECT value FROM ".TABSETTINGSNAME." WHERE feature = '".$name."'");
			$stmt->execute();
			return $stmt->fetchColumn();
        }
    }

	// ADMIN.PHP
	public function getListOfUploadedImagesAtYearAndState($year) {
        $stmt = $this->database->prepare("SELECT id, faculty, course, name, email, uploaddatum FROM ".TABDATANAME." WHERE tutorenjahr = ".$year." AND state = '".$this->getSetting('state')."' ORDER BY uploaddatum DESC");
		$succ = $stmt->execute();
		$data = $stmt->fetchAll();
		
		// Escapes aus $data entfernen
		for ($i=0; $i<count($data); $i++) {
			$data[$i]["name"] = str_replace("'", "", $data[$i]["name"]);
			$data[$i]["email"] = str_replace("'", "", $data[$i]["email"]);
		}
		
        return $this->returnValue($succ, NULL, $data, "");
    }
	
	
	public function getUploadedYears() {
		$stmt = $this->database->prepare("SELECT DISTINCT tutorenjahr FROM `tutorenupload__data` ORDER BY tutorenjahr DESC");
		$succ = $stmt->execute();
		$data = $stmt->fetchAll();
		
		return $this->returnValue($succ, NULL, $data, "Fehler beim Abrufen der Jahre.");
	}

    // INDEX.PHP
	public function detectFace($filename) {
		$imageResource = $this->getJpegResourceFromAnyImage($filename);
		if ($imageResource !== false) {
			if (imagesx($imageResource) > CONFIG_PICTURE_MAXWIDTH) {
				$imageResource = $this->resizeDownJpegWithAspectRatio($imageResource);
			}
		
			$fd = new FaceDetector(PATH_FACE_DETECTION_DATA);
			$success = $fd->faceDetect($imageResource);
			
			ob_start();
			$fd->toJpeg();
			$data = ob_get_contents();
			ob_end_clean();
			
			$width = imagesx($imageResource);
			$height = imagesy($imageResource);
			if ($success) {
				$coordinates = $fd->getFace();

				// Werte anpassen, falls außerhalb der Grenzen
				if (($coordinates["y"] + $this->calculateHeight($coordinates["w"])) > $height) {
					$coordinates["w"] = $height - $coordinates["y"] - 70;
				}
				if (($coordinates["x"] + $coordinates["w"]) > $width) {
					$coordinates["w"] = $width - $coordinates["x"] - 70;
				}
				
				$coordinates["width"] = $width;
				$coordinates["height"] = $height;
				return $this->returnValue(true, $data, $coordinates, NULL);
			} else {
				$coordinates = array(
					"x" => 20,
					"y" => 20,
					"w" => 50,
					"width" => $width,
					"height" => $height
				);
				return $this->returnValue(false, $data, $coordinates, "Gesichtserkennung schlug fehl!");
			}
		} else {
			return $this->returnValue(false, NULL, NULL, "Dateiformat wird nicht untersützt!");
		}
	}
	
	private function getJpegResourceFromAnyImage($filename) {
		$imageMetaData = getimagesize($filename);
		$type = $imageMetaData[2];
		
		$source;
        switch($type) {
            case IMAGETYPE_GIF:
                $picdata = file_get_contents($filename);
				$source = imagecreatetruecolor(imagesx($picdata), imagesy($picdata));
				imagefill($source, 0, 0, imagecolorallocate($source, 255, 255, 255));
				imagealphablending($source, TRUE);
				imagecopy($source, $picdata, 0, 0, 0, 0, imagesx($picdata), imagesy($picdata));
                break;

            case IMAGETYPE_JPEG;
                $source = imagecreatefromjpeg($filename);
                break;

            case IMAGETYPE_PNG:
                $picdata = imagecreatefrompng($filename);
				$source = imagecreatetruecolor(imagesx($picdata), imagesy($picdata));
				imagefill($source, 0, 0, imagecolorallocate($source, 255, 255, 255));
				imagealphablending($source, TRUE);
				imagecopy($source, $picdata, 0, 0, 0, 0, imagesx($picdata), imagesy($picdata));
                break;

            default:
                return false;
        }
		
		return $source;
	}
	
    public function resizeAndUpload($faculty, $course, $name, $mail, $face, $datastring) {
        $source = imagecreatefromstring($datastring);
		
        $thumb = imagecreatetruecolor(CONFIG_PICTURE_WIDTH, CONFIG_PICTURE_HEIGHT);
        imagealphablending($thumb, false);
        imagecopyresized($thumb, $source, 0, 0, $face["x"], $face["y"], CONFIG_PICTURE_WIDTH, CONFIG_PICTURE_HEIGHT, $face["w"], $this->calculateHeight($face["w"]));

        // Ausgabe puffern, d.h. den rohen Datenstrom in die Variable $contents schreiben statt ihn auszugeben
        ob_start();
        imagejpeg($thumb);
        $contents =  ob_get_contents();
        ob_end_clean();

        if ($contents !== false) {
            return $this->saveImage($faculty, $course, $name, $mail, $contents);
        } else {
            return $this->returnValue();
        }
    }

	private function calculateHeight($width) {
		return intval($width * 5 / 4);
	}
    
    private function resizeDownJpegWithAspectRatio($imageResource) {
		$originalWidth = imagesx($imageResource);
		$originalHeight = imagesy($imageResource);
		
        if ($originalWidth <= 0 || $originalHeight <= 0) return false;
		
		$newWidth = CONFIG_PICTURE_MAXWIDTH;
		$newHeight = intval($originalHeight * (CONFIG_PICTURE_MAXWIDTH / $originalWidth));
		
		$thumb = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresized($thumb, $imageResource, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
		return $thumb;
	}
	
    private function saveImage($faculty, $course, $name, $email, $data) {
		// schaue nach, ob es bereits schon ein Eintrag derjenigen Person im aktuellen Jahr gibt
		$stmt = $this->database->prepare("SELECT id FROM ".TABDATANAME." WHERE email = '\\'".$email."\'' AND tutorenjahr = ".date("Y")." AND state = '".$this->getSetting('state')."'");
		$stmt->execute();
		$ret = $stmt->fetchAll();
		
		if (count($ret) > 1) {
			// Fehler, da eine ID mehrmals vorhanden :-(
			return $this->returnValue();
		} elseif (count($ret) == 1) {
			$stmt = $this->database->prepare("UPDATE ".TABDATANAME." SET faculty = ?, course = ?, uploaddatum = ?, bild = ? WHERE id = ".$ret[0]["id"]);
			$stmt->bindParam(1, $faculty);
			$stmt->bindParam(2, $course);
			$stmt->bindParam(3, time());
			$stmt->bindParam(4, $data);
		} else {		
			$stmt = $this->database->prepare("INSERT INTO ".TABDATANAME." (tutorenjahr, state, faculty, course, name, email, uploaddatum, bild) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
			$stmt->bindParam(1, date("Y"));
			$stmt->bindParam(2, $this->getSetting('state'));
			$stmt->bindParam(3, $faculty);
			$stmt->bindParam(4, $course);
			$stmt->bindParam(5, $this->database->quote($name));
			$stmt->bindParam(6, $this->database->quote($email));
			$stmt->bindParam(7, time());
			$stmt->bindParam(8, $data);
		}
		
		$succ = $stmt->execute();

		if ($succ) {
			return $this->returnValue(true, $data, $this->database->lastInsertId(), "");
		} else {
			return $this->returnValue(false, NULL, NULL, "Bild konnte nicht gespeichert werden.");
		}
    }

    // BILD.PHP, ARCHIV.PHP
    public function getImageAsStringByID($id) {
        $stmt = $this->database->prepare("SELECT faculty, course, name, bild FROM ".TABDATANAME." WHERE id = ".$id);

        $succ = $stmt->execute();
        $val = $stmt->fetch();

		$name = str_replace("'", "", $val["name"]);
		$name = strtolower($name);
		$name = str_replace(" ", "-", $name);
		if ($this->getSetting('state') == "ba") {
			$name = $val["course"]."-".$name;
		}
		$name = self::getSetting('state')."-".$val["faculty"]."-".$name;
		
		$bild = $val["bild"];
		
        if ($val !== false) {
			return $this->returnValue(true, $bild, $name, "");
        } else {
            return $this->returnValue(false, NULL, NULL, "Fehler! Keine solche ID vorhanden.");
        }
    }
}
