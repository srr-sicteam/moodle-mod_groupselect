<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Group self selection
 *
 * @package    mod
 * @subpackage groupselect
 * @copyright  2008-2011 Petr Skoda (http://skodak.org)
 * @copyright  2014 Tampere University of Technology, P. Pyykkönen (pirkka.pyykkonen ÄT tut.fi)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assignedteacher'] = 'Betreuung';
$string['eventgroupteacheradded'] = 'Betreuung zugeordnet';
$string['eventexportlinkcreated'] = 'Exportlink erstellt';
$string['maxgroupmembership'] = 'Maximale Anzahl der Gruppenteilnahme pro Teilnehmenden ';
$string['maxgroupmembership_error_low'] = 'Fehler: Es muss mindestens eine (1) Gruppe wählbar sein!';
$string['modulename_help'] = '<p>Teilnehmer/innen können Gruppen erstellen und wählen: </p><ul><li>Teilnehmer/innen können Gruppen erstellen, diesen eine Beschreibung geben und, falls gewünscht, mit einem Passwort schützen</li><li>Teilnehmer/innen können Gruppen auswählen und betreten</li><li>Trainer/innen ohne Bearbeitungsrechte können Gruppen hinzugefügt werden</li><li>Trainer/in kann die Gruppenliste des Kurses als CSV-Datei exportieren</li><li>Volle Kompatibilität zu Basis Moodle-Gruppen: Gruppen können auch durch andere Plugins erzeugt werden.</li></ul>';
$string['notifyexpiredselection'] = 'Zeige Meldung, wenn das Einschreibeende vorüber ist';
$string['notifyexpiredselection_help'] = 'Wenn gesetzt, wird eine Meldung angezeigt falls das Einschreibeende vorüber ist';
$string['studentcansetenrolmentkey'] = 'Teilnehmende können Passwörter setzen um Gruppen beizutreten';
$string['studentcansetenrolmentkey_help'] = 'Wenn gesetzt, kann ein/e Teilnehmer/in ein Gruppenpasswort setzen';
$string['studentcansetgroupname'] = 'Teilnehmende dürfen Gruppennamen selbst bestimmen.';
$string['studentcansetgroupname_help'] = 'Wenn gesetzt, kann ein/e Teilnehmer/in einen Gruppennamen setzen';
$string['supervisionrole'] = 'Rolle für die Betreuung';
$string['supervisionrole_help'] = 'Supervisorenrolle festlegen für die Betreuung der Gruppen festlegen (Standard: Lehrer ohne Bearbeitungsrecht)';
$string['timeavailable_error_past_timedue'] = 'Fehler: Aktivität kann nicht nach dem Ende anfangen!';
$string['timedue_error_pre_timeavailable'] = 'Fehler: Aktivität kann nicht vor dem Start enden!';
