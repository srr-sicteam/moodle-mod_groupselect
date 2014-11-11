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
 * Library of functions and constants of Group selection module
 *
 * @package    mod
 * @subpackage groupformation
 * @copyright  2008-2011 Petr Skoda (http://skodak.org)
 * @copyright  2014 Tampere University of Technology, P. Pyykkönen (pirkka.pyykkonen ÄT tut.fi)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * List of features supported in groupformation module
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function groupformation_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_ARCHETYPE:           return MOD_ARCHETYPE_OTHER;
        case FEATURE_GROUPS:                  return true;  // only separate mode makes sense - you hide members of other groups here
        case FEATURE_GROUPINGS:               return false;
        case FEATURE_GROUPMEMBERSONLY:        return false;  // this could be very confusing
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return false;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return false; //TODO

        default: return null;
    }
}

/**
 * Returns all other caps used in module
 * @return array
 */
function groupformation_get_extra_capabilities() {
    return array('moodle/site:accessallgroups', 'moodle/site:viewfullnames');
}

/**
 * Given an object containing all the necessary data, (defined by the form in mod.html)
 * this function will create a new instance and return the id number of the new instance.
 *
 * @param object $groupformation Object containing all the necessary data defined by the form in mod_form.php
 * $return int The id of the newly created instance
 */
function groupformation_add_instance($groupformation) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');

    $groupformation->timecreated = time();
    $groupformation->timemodified = time();

    $groupformation->id = $DB->insert_record('groupformation', $groupformation);

    if ($groupformation->timedue) {
        $event = new stdClass();
        $event->name         = $groupformation->name;
        $event->description  = format_module_intro('groupformation', $groupformation, $groupformation->coursemodule); // TODO: this is weird
        $event->courseid     = $groupformation->course;
        $event->groupid      = 0;
        $event->userid       = 0;
        $event->modulename   = 'groupformation';
        $event->instance     = $groupformation->id;
        $event->eventtype    = 'due';
        $event->timestart    = $groupformation->timedue;
        $event->timeduration = 0;

        calendar_event::create($event);
    }

    return $groupformation->id;
}


/**
 * Update an existing instance with new data.
 *
 * @param object $groupformation An object containing all the necessary data defined by the mod_form.php
 * @return bool
 */
function groupformation_update_instance($groupformation) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/calendar/lib.php');

    $groupformation->timemodified = time();
    $groupformation->id = $groupformation->instance;

    $DB->update_record('groupformation', $groupformation);

    if ($groupformation->timedue) {
        if ($event->id = $DB->get_field('event', 'id', array('modulename'=>'groupformation', 'instance'=>$groupformation->id))) {
            $event->name         = $groupformation->name;
            $event->description  = format_module_intro('groupformation', $groupformation, $groupformation->coursemodule);
            $event->timestart    = $groupformation->timedue;

            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);

        } else {
            $event = new stdClass();
            $event->name         = $groupformation->name;
            $event->description  = format_module_intro('groupformation', $groupformation, $groupformation->coursemodule);// TODO: this is weird
            $event->courseid     = $groupformation->course;
            $event->groupid      = 0;
            $event->userid       = 0;
            $event->modulename   = 'groupformation';
            $event->instance     = $groupformation->id;
            $event->eventtype    = 'due';
            $event->timestart    = $groupformation->timedue;
            $event->timeduration = 0;

            calendar_event::create($event);
        }

    } else {
        $DB->delete_records('event', array('modulename'=>'groupformation', 'instance'=>$groupformation->id));
    }

    return true;
}


/**
 * Permanently delete the instance of the module and any data that depends on it.
 *
 * @param int $id Instance id
 * @return bool
 */
function groupformation_delete_instance($id) {
    global $DB;
    // delete group password rows related to this instance (but not the groups)
    $DB->delete_records('groupformation_passwords', array('instance_id'=>$id)); 
    
    $DB->delete_records('groupformation_groups_teache', array('instance_id'=>$id));
    
    $DB->delete_records('groupformation', array('id'=>$id));

    return true;
}


/**
 * Returns the users with data in this module
 *
 * We have no data/users here but this must exists in every module
 *
 * @param int $groupformationid
 * @return bool
 */
function groupformation_get_participants($groupformationid) {
    // no participants here - all data is stored in the group tables
    return false;
}


/**
 * groupformation_get_view_actions
 *
 * @return array
 */
function groupformation_get_view_actions() {
    return array('view', 'export');
}


/**
 * groupformation_get_post_actions
 *
 * @return array
 */
function groupformation_get_post_actions() {
    return array('select', 'unselect', 'create');
}


/**
 * This function is used by the reset_course_userdata function in moodlelib.
 *
 * @param $data the data submitted from the reset course.
 * @return array status array
 */
function groupformation_reset_userdata($data) {
    // no resetting here - all data is stored in the group tables
    return array();
}

function groupformation_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // Check the contextlevel is as expected - if your plugin is a block, this becomes CONTEXT_BLOCK, etc.
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false; 
    }
 
    // Make sure the filearea is one of those used by the plugin.
    if ($filearea !== 'export') { //&& $filearea !== 'anotherexpectedfilearea') {
        return false;
    }
 
    // Make sure the user is logged in and has access to the module (plugins that are not course modules should leave out the 'cm' part).
    require_login($course, true, $cm);
 
    // Check the relevant capabilities - these may vary depending on the filearea being accessed.
    if (!has_capability('mod/groupformation:export', $context)) {
        return false;
    }
 
    // Leave this line out if you set the itemid to null in make_pluginfile_url (set $itemid to 0 instead).
    $itemid = array_shift($args); // The first item in the $args array.
 
    // Use the itemid to retrieve any relevant data records and perform any security checks to see if the
    // user really does have access to the file in question.
 
    // Extract the filename / filepath from the $args array.
    $filename = array_pop($args); // The last item in the $args array.
    if (!$args) {
        $filepath = '/'; // $args is empty => the path is '/'
    } else {
        $filepath = '/'.implode('/', $args).'/'; // $args contains elements of the filepath
    }
 
    // Retrieve the file from the Files API.
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_groupformation', $filearea, $itemid, $filepath, $filename);
    if (!$file) {
        return false; // The file does not exist.
    }
 
    // We can now send the file back to the browser - in this case with a cache lifetime of 1 day and no filtering. 
    // From Moodle 2.3, use send_stored_file instead.
    send_stored_file($file, 86400, 0, 'true', $options);
}