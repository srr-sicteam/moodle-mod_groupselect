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
 * @package    mod_groupselect
 * @copyright  2018 HTW Chur Roger Barras
 * @copyright  2008-2011 Petr Skoda (http://skodak.org)
 * @copyright  2014 Tampere University of Technology, P. Pyykkönen (pirkka.pyykkonen ÄT tut.fi)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade function
 *
 * @param int $oldversion The version we are upgrading form.
 * @return bool Returns true on success.
 * @throws coding_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_groupselect_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2009020600) {
        $table = new xmldb_table('groupselect');

        // Define field timecreated to be added to groupselect.
        $fieldtimecreatednew = new xmldb_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL,
            null, null, null, '0', 'timedue');

        // Conditionally launch add temporary fields.
        if (!$dbman->field_exists($table, $fieldtimecreatednew)) {
            $dbman->add_field($table, $fieldtimecreatednew);
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2009020600, 'groupselect');

    }

    if ($oldversion < 2009030500) {

        // Define field targetgrouping to be added to groupselect.
        $table = new xmldb_table('groupselect');
        $fieldtargetgroupingnew = new xmldb_field('targetgrouping', XMLDB_TYPE_INTEGER, '10',
            XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'intro');
        // Conditionally launch adding fields.
        if (!$dbman->field_exists($table, $fieldtargetgroupingnew)) {
            $dbman->add_field($table, $fieldtargetgroupingnew);
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2009030500, 'groupselect');

    }

    // Moodle 2.0 upgrade line.

    if ($oldversion < 2010010100) {
        // Define field introformat to be added to groupselect.
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('introformat', XMLDB_TYPE_INTEGER, '4', XMLDB_UNSIGNED,
            XMLDB_NOTNULL, null, '0', 'intro');

        // Launch add field introformat.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $DB->set_field('groupselect', 'introformat', FORMAT_HTML, array());

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2010010100, 'groupselect');
    }

    if ($oldversion < 2010010102) {
        $table = new xmldb_table('groupselect');

        // Define field signuptype to be added to groupselect.
        $fieldsignuptype = new xmldb_field('signuptype', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
            null, null, null, null, '0', 'targetgrouping');

        // Conditionally launch removing fields.
        if ($dbman->field_exists($table, $fieldsignuptype)) {
            $dbman->drop_field($table, $fieldsignuptype);
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2010010102, 'groupselect');

    }

    if ($oldversion < 2011101800) {
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, 'big', null, XMLDB_NOTNULL, null, null, 'name');

        // Make text field bigger.
        $dbman->change_field_precision($table, $field);

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2011101800, 'groupselect');
    }
    // Group self-formation update.
    if ($oldversion < 2014090201) {

        // Update module settings table.
        $table = new xmldb_table('groupselect');
        $fields[] = new xmldb_field('hidefullgroups', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'timemodified');
        $fields[] = new xmldb_field('deleteemptygroups', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '1', 'hidefullgroups');
        $fields[] = new xmldb_field('studentcancreate', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '1', 'deleteemptygroups');
        $fields[] = new xmldb_field('minmembers', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'studentcancreate');
        $fields[] = new xmldb_field('assignteachers', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'minmembers');
        $fields[] = new xmldb_field('studentcansetdesc', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '1', 'assignteachers');
        $fields[] = new xmldb_field('showassignedteacher', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'studentcansetdesc');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Add a new table for group passwords.
        $table = new xmldb_table('groupselect_passwords');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('password', XMLDB_TYPE_CHAR, '60', null, XMLDB_NOTNULL, null, null, 'groupid');
        $table->add_field('instance_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'password');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add a new table for group-teacher relations.
        $table = new xmldb_table('groupselect_groups_teachers');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('teacherid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'groupid');
        $table->add_field('instance_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'teacherid');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2014090201, 'groupselect');
    }

    if ($oldversion < 2015032500) {
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('password', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL,
            null, null, null, 'maxmembers');
        if ($dbman->table_exists( $table ) && $dbman->field_exists($table, $field)) {
             $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2015032500, 'groupselect');
    }

    if ($oldversion < 2016060200) {

        // Update wrong instace_ids.
        $table = new xmldb_table('groupselect_groups_teachers');
        if ($dbman->table_exists( $table )) {
            $gsteachers = $DB->get_records('groupselect_groups_teachers');

            foreach ($gsteachers as $gsteacher) {
                $coursemodule = $DB->get_record('course_modules', array('id' => $gsteacher->instance_id));
                if (isset($coursemodule->instance)) {
                    $gsteacher->instance_id = $coursemodule->instance;
                    $DB->update_record('groupselect_groups_teachers', $gsteacher, $bulk = false);
                } else {
                    $DB->delete_records('groupselect_groups_teachers', array('id' => $gsteacher->id));
                }
            }
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2016060200, 'groupselect');
    }
    if ($oldversion < 2016060603) {

        // Update module settings table.
        $fields = array();
        $table = new xmldb_table('groupselect');
        $fields[] = new xmldb_field('studentcansetenrolmentkey', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'showassignedteacher');
        $fields[] = new xmldb_field('studentcansetgroupname', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '1', 'studentcansetenrolmentkey');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2016060603, 'groupselect');
    }
    if ($oldversion < 2016061100) {

        // Update module settings table.
        $fields = array();
        $table = new xmldb_table('groupselect');
        $fields[] = new xmldb_field('notifyexpiredselection', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '1', 'studentcansetgroupname');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2016061100, 'groupselect');
    }

    if ($oldversion < 2017061205) {

        // Get default teacher role.
        $teacherrole = $DB->get_record( 'role', array (
            'shortname' => "teacher"
        ), '*', IGNORE_MISSING );

        if (empty($teacherrole)) {
            $teacherroleid = 4;
        } else {
            $teacherroleid = $teacherrole->id;
        }

        // Update module settings table.
        $fields = array();
        $table = new xmldb_table('groupselect');
        $fields[] = new xmldb_field('supervisionrole', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
            null, $teacherroleid, 'notifyexpiredselection');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2017061205, 'groupselect');
    }

    if ($oldversion < 2017061302) {

        // Update module settings table.
        $fields = array();
        $table = new xmldb_table('groupselect');
        $fields[] = new xmldb_field('maxgroupmembership', XMLDB_TYPE_INTEGER, 10, null, XMLDB_NOTNULL,
            null, '1', 'supervisionrole');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2017061302, 'groupselect');
    }

    if ($oldversion < 2018031606) {

        // Update module settings table.
        $fields = array();
        $table = new xmldb_table('groupselect');
        $fields[] = new xmldb_field('studentcanjoin', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '1', 'maxgroupmembership');
        $fields[] = new xmldb_field('studentcanleave', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '1', 'studentcanjoin');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        // Update default capabilities for teacher, editingteacher and manager.
        // Create, select and unselect groups.
        $editingteacherroleid = $DB->get_field('role', 'id', array('shortname' => 'editingteacher'));
        $teacherroleid = $DB->get_field('role', 'id', array('shortname' => 'teacher'));
        $managerroleid = $DB->get_field('role', 'id', array('shortname' => 'manager'));
        if (!empty($editingteacherroleid)) {
            role_change_permission($editingteacherroleid, context_system::instance(0),
                                    'mod/groupselect:create', CAP_ALLOW);
            role_change_permission($editingteacherroleid, context_system::instance(0),
                                    'mod/groupselect:select', CAP_ALLOW);
            role_change_permission($editingteacherroleid, context_system::instance(0),
                                    'mod/groupselect:unselect', CAP_ALLOW);
        }
        if (!empty($teacherroleid)) {
            role_change_permission($teacherroleid, context_system::instance(0),
                                    'mod/groupselect:create', CAP_ALLOW);
            role_change_permission($teacherroleid, context_system::instance(0),
                                    'mod/groupselect:select', CAP_ALLOW);
            role_change_permission($teacherroleid, context_system::instance(0),
                                    'mod/groupselect:unselect', CAP_ALLOW);
        }
        if (!empty($managerroleid)) {
            role_change_permission($managerroleid, context_system::instance(0),
                                    'mod/groupselect:create', CAP_ALLOW);
            role_change_permission($managerroleid, context_system::instance(0),
                                    'mod/groupselect:select', CAP_ALLOW);
            role_change_permission($managerroleid, context_system::instance(0),
                                    'mod/groupselect:unselect', CAP_ALLOW);
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2018031606, 'groupselect');
    }

    if ($oldversion < 2018051901) {

        // Changing nullability of field intro on table groupselect to null.
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('intro', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');

        // Launch change of nullability for field intro.
        $dbman->change_field_notnull($table, $field);

        // Changing the default of field supervisionrole on table groupselect to 1.
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('supervisionrole', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL,
            null, '1', 'notifyexpiredselection');

        // Launch change of default for field supervisionrole.
        $dbman->change_field_default($table, $field);

        // Define field signuptype to be dropped from groupselect.
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('signuptype');

        // Conditionally launch drop field signuptype.
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2018051901, 'groupselect');
    }
    if ($oldversion < 2020020500) {

        // Change the length of minmembers from 1 to 10.
        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('minmembers', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'studentcancreate');

        // Launch change of nullability for field intro.
        $dbman->change_field_precision($table, $field);

        // Update module settings table.
        $fields = array();
        $fields[] = new xmldb_field('hidesuspendedstudents', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'hidefullgroups');
        $fields[] = new xmldb_field('hidegroupmembers', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL,
            null, '0', 'hidesuspendedstudents');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, 2020020500, 'groupselect');
    }
    $newversion = 2024102100;
    if ($oldversion < $newversion) {

        // Change the length of minmembers from 1 to 10.
        $table = new xmldb_table('groupselect');

        // Update module settings table.
        $fields = array();
        $fields[] = new xmldb_field('leadercanleaveonlywhenalone', XMLDB_TYPE_INTEGER, '1',
            null, XMLDB_NOTNULL, null, '0');
        $fields[] = new xmldb_field('restrictleavewhenmodcompleted', XMLDB_TYPE_INTEGER, '10');

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        // Groupselect savepoint reached.
        upgrade_mod_savepoint(true, $newversion, 'groupselect');
    }

    $newversion = 2024102101;
    if ($oldversion < $newversion) {

        $table = new xmldb_table('groupselect');
        $field = new xmldb_field('leaderid', XMLDB_TYPE_INTEGER, '10');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        $table = new xmldb_table('groupselect_groups');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('groupselectid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('leaderid', XMLDB_TYPE_INTEGER, '10');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('groupid', XMLDB_KEY_FOREIGN, array('groupid'), 'groups', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Fill in the data of new table
        $sql = "insert into {groupselect_groups} (groupid, groupselectid, leaderid) 
                select distinct gm.groupid,
                       gs.id as groupselectid,
                       first_value(gm.userid) over (partition by gm.groupid ORDER BY gm.timeadded asc) as leaderid
                from {groupselect} gs
                       join {groupings_groups} gg on gg.groupingid = gs.targetgrouping
                       join {groups} g on g.id = gg.groupid
                       join {groups_members} gm on gm.groupid = g.id
                group by gm.groupid, gs.id, gm.userid, gm.timeadded";

        $DB->execute($sql);

        upgrade_mod_savepoint(true, $newversion, 'groupselect');
    }

    $newversion = 2024102102;
    if ($oldversion < $newversion) {

        $table = new xmldb_table('groupselect_groups');
        $field = $table->add_field('groupselectid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'groupselect');
    }

    $newversion = 2024102107;
    if ($oldversion < $newversion) {

        $table = new xmldb_table('groupselect_groups');
        $field = $table->add_field('modcompleted', XMLDB_TYPE_INTEGER, '10', null,
            XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'groupselect');
    }

    $newversion = 2024111501;
    if ($oldversion < $newversion) {

        $table = new xmldb_table('groupselect');
        $field = $table->add_field('showleadericon', XMLDB_TYPE_INTEGER, '1', null,
            XMLDB_NOTNULL, null, 0);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, $newversion, 'groupselect');
    }

    return true;
}
