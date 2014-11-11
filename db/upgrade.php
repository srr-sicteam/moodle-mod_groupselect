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
 * Group self selection interface
 *
 * @package    mod
 * @subpackage groupformation
 * @copyright  2008-2011 Petr Skoda (http://skodak.org)
 * @copyright  2014 Tampere University of Technology, P. Pyykkönen (pirkka.pyykkonen ÄT tut.fi)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

function xmldb_groupformation_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2009020600) {
        $table = new xmldb_table('groupformation');

        // Define field timecreated to be added to groupformation
        $field_timecreated_new = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'timedue');

        // Conditionally launch add temporary fields
        if (!$dbman->field_exists($table, $field_timecreated_new)) {
            $dbman->add_field($table, $field_timecreated_new);
        }

        // search savepoint reached
        upgrade_mod_savepoint(true, 2009020600, 'groupformation');

    }

    if ($oldversion < 2009030500) {

        // Define field targetgrouping to be added to groupformation
        $table = new xmldb_table('groupformation');
        $field_targetgrouping_new = new xmldb_field('targetgrouping', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'intro');
        // Conditionally launch adding fields
        if (!$dbman->field_exists($table, $field_targetgrouping_new)) {
            $dbman->add_field($table, $field_targetgrouping_new);
        }

        // search savepoint reached
        upgrade_mod_savepoint(true, 2009030500, 'groupformation');

    }

    // ==== Moodle 2.0 upgrade line =====

    if ($oldversion < 2010010100) {
        // Define field introformat to be added to groupformation
        $table = new xmldb_table('groupformation');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('groupformation', 'introformat', FORMAT_HTML, array());

        // groupformation savepoint reached
        upgrade_mod_savepoint(true, 2010010100, 'groupformation');
    }

    if ($oldversion < 2010010102) {
        $table = new xmldb_table('groupformation');

        // Define field signuptype to be added to groupformation
        $field_signuptype = new xmldb_field('signuptype', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, null, '0', 'targetgrouping');

        // Conditionally launch removing fields
        if ($dbman->field_exists($table, $field_signuptype)) {
            $dbman->drop_field($table, $field_signuptype);
        }

        // search savepoint reached
        upgrade_mod_savepoint(true, 2010010102, 'groupformation');

    }

    if ($oldversion < 2011101800) {
        $table = new xmldb_table('groupformation');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, 'name');

        // Make text field bigger
        $dbman->change_field_precision($table, $field);

        // savepoint reached
        upgrade_mod_savepoint(true, 2011101800, 'groupformation');
    }
    
    if ($oldversion < 2014090201) {
        $table = new xmldb_table('groupformation');
        $field = new xmldb_field('hidefullgroups', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'timemodified');
        
        if (!$dbman->field_exists($table, $field)) {
        	$dbman->add_field($table, $field);
        }
        //$table = new xmldb_table('groupformation_passwords');
       
    	// search savepoint reached
    	upgrade_mod_savepoint(true, 2014090201, 'groupformation');
    }

    return true;
}
