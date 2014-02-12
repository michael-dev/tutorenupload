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

/*
 * To use this class, enable the pdo-database driver in the 'php.ini'.
 * To do this, comment out the line 'extension=php_pdo_mysql.dll' in 'php.ini' for a mysql database.
 */

require_once("../config/config.php");

class EditImage {
    // maximale Größe eines Bildes
    // VORSICHT: BEI ÄNDERUNG DES ZAHLENWERTS MUSS DAS DB-SCHEMA (WEITER UNTEN) ANGEPASST WERDEN!
    const MAXIMAGESIZE = 100000; // bytes

    // Abmaße der neuen Bilder in Pixel
    const WIDTH = 75;
    const HEIGHT = 100;

    // Tabellen der Datenbank
    const TABSETTINGSNAME = 'tutorenupload__settings';
    const TABSETTINGSSCHEME =
        '`feature` VARCHAR(30) NOT NULL,
        `value` BOOLEAN,
        PRIMARY KEY (`feature`)';

    const TABDATANAME = 'tutorenupload__data';
    const TABDATASCHEME =
        '`id` INT NOT NULL auto_increment,
        `tutorenjahr` INT NOT NULL,
        `vorname` VARCHAR(30),
        `nachname` VARCHAR(30),
        `uploaddatum` DATETIME,
        `bild` VARBINARY(100000),
        PRIMARY KEY (`id`)';

    private $database;

    function __construct() {
        $this->database = self::getDB();
        $this->initalizeDB();
    }

    private function getDB() {
        try {
            return new PDO('mysql:host='.CONFIG_DBHOST.';dbname='.CONFIG_DBNAME, CONFIG_DBUSER, CONFIG_DBPASS);
        } catch (PDOException $e) {
            die("<br/><br/>Error! " . $e->getMessage() . "<br/><br/>");
        }
    }

    function __destruct() {
        $this->database = null;
    }

    // ADMIN.PHP
    public function initalizeDB() {
        $r = $this->database->query("SELECT COUNT(*) FROM ".self::TABSETTINGSNAME);
        if ($r === false) {
          if (!self::createNewTable(self::TABSETTINGSNAME, self::TABSETTINGSSCHEME)) {
            die("<br/><br/>Error! Die Tabelle '".self::TABSETTINGSNAME."' konnte nicht in der Datenbank erstellt werden!<br/><br/>");
          }
        }

        $stmt = $this->database->prepare("SELECT * FROM ".self::TABSETTINGSNAME." WHERE feature = ?;");
        $stmt->execute(Array('active')) or die("error query settings");
        if (count($stmt->fetchAll()) < 1) {
          if (!self::createNewSetting('active', 0)) {
            die("<br/><br/>Error! Einstellung 'active' konnte nicht in der Tabelle '".self::TABSETTINGSNAME."' erstellt werden!<br/><br/>");
          }
        }

        $stmt->execute(Array('showPicture')) or die("error query settings");
        if (count($stmt->fetchAll()) < 1) {
          if (!self::createNewSetting('showPicture', 0)) {
            die("<br/><br/>Error! Einstellung 'showPicture' konnte nicht in der Tabelle '".self::TABSETTINGSNAME."' erstellt werden!<br/><br/>");
          }
        }

        $r = $this->database->query("SELECT COUNT(*) FROM ".self::TABDATANAME);
        if ($r === false) {
          if (!self::createNewTable(self::TABDATANAME, self::TABDATASCHEME)) {
            die("<br/><br/>Error! Die Tabelle '".self::TABDATANAME."' konnte nicht in der Datenbank erstellt werden!<br/><br/>");
          }
        }
    }

    private function createNewTable($name, $schema) {
        $stmt = $this->database->prepare("CREATE TABLE `".$name."` (".$schema.")");
        $r = $stmt->execute();
        if ($r === false) var_dump($stmt->errorInfo());
        return $r;
    }

    private function createNewSetting($name, $value) {
        $stmt = $this->database->prepare("INSERT INTO `".self::TABSETTINGSNAME."` (feature, value) VALUES (?,?);");
        $r = $stmt->execute(Array($name, $value));
        if ($r === false) var_dump($stmt->errorInfo());
        return $r;
    }

    public function saveSetting($name, $value) {
        switch ($name) {
            case 'active':
            case 'showPicture':
                $stmt = $this->database->prepare("UPDATE ".self::TABSETTINGSNAME." SET value = ".$value." WHERE feature = '".$name."'");
                $stmt->execute();
                break;

            default:
                // Fehlermeldung
        }
    }

    public function getSetting($settingName) {
        switch ($settingName) {
            case 'active':
            case 'showPicture':
                $stmt = $this->database->prepare("SELECT value FROM ".self::TABSETTINGSNAME." WHERE feature = '".$settingName."'");
                $stmt->execute();
                return $stmt->fetchColumn();

            default:
                return NULL;
        }
    }

    public function getListOfUploadedImages() {
        $stmt = $this->database->prepare("SELECT id, tutorenjahr, vorname, nachname, uploaddatum FROM ".self::TABDATANAME." ORDER BY uploaddatum DESC");

        return array(
            "success" => $stmt->execute(),
            "result" => $stmt->fetchAll()
        );
    }

    // INDEX.PHP
    public function resizeAndUpload($firstname, $lastname, $filename) {
        $imageMetaData = getimagesize($filename);

        $source;
        switch($imageMetaData[2]) {
            case IMAGETYPE_GIF:
                $source = imagecreatefromgif($filename);
                break;

            case IMAGETYPE_JPEG;
                $source = imagecreatefromjpeg($filename);
                break;

            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($filename);
                break;

            default:
                return array(
                    "success" => false,
                    "result" => "Dateiformat wird nicht untersützt!",
                    "image" => NULL
                );
        }

        $width = $imageMetaData[0];
        $height = $imageMetaData[1];

        $thumb = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagealphablending($thumb, false);
        imagecopyresized($thumb, $source, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, $width, $height);

        // Ausgabe puffern (den rohen Datenstrom in die Variable $contents schreiben statt ihn auszugeben)
        ob_start();
        imagepng($thumb);
        $contents =  ob_get_contents();
        ob_end_clean();

        if ($contents !== false) {
            return self::saveImage($firstname, $lastname, $contents);
        } else {
            return array(
                "success" => false,
                "result" => NULL,
                "image" => NULL
            );
        }
    }

    private function saveImage($vorname, $nachname, $data) {
        $stmt = $this->database->prepare("INSERT INTO ".self::TABDATANAME." (tutorenjahr, vorname, nachname, uploaddatum, bild) VALUES (NOW(), ?, ?, NOW(), ?)");

        $stmt->bindParam(1, $this->database->quote($vorname));
        $stmt->bindParam(2, $this->database->quote($nachname));
        // $stmt->bindParam(3, base64_encode($data));
        $stmt->bindParam(3, $data);

        $succ = $stmt->execute();

        return array(
            "success" => $succ,
            "result" => $this->database->lastInsertId(),
            "image" => $data
        );
    }

    // BILD.PHP
    // ARCHIV.PHP
    public function getPictureByID($id) {
        $stmt = $this->database->prepare("SELECT bild FROM ".self::TABDATANAME." WHERE id = ".$id);

        $succ = $stmt->execute();
        $val = $stmt->fetchColumn();

        if ($val !== false) {
            // $val = base64_decode($val);
            return array(
                "success" => $succ,
                "result" => $val
            );
        } else {
            return array(
                "success" => false,
                "result" => NULL
            );
        }
    }
}

