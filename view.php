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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Main group self selection interface
 *
 * @package mod
 * @subpackage groupselect
 * @copyright 2008-2011 Petr Skoda (http://skodak.org)
 * @copyright 2014 Tampere University of Technology, P. Pyykkönen (pirkka.pyykkonen ÄT tut.fi)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require ('../../config.php');
require_once ($CFG->dirroot . '/lib/password_compat/lib/password.php');
require_once ('locallib.php');
require_once ('select_form.php');
require_once ('create_form.php');

$id = optional_param ( 'id', 0, PARAM_INT ); // Course Module ID, or
$g = optional_param ( 'g', 0, PARAM_INT ); // Page instance ID
$select = optional_param ( 'select', 0, PARAM_INT );
$unselect = optional_param ( 'unselect', 0, PARAM_INT );
$confirm = optional_param ( 'confirm', 0, PARAM_BOOL );
$create = optional_param ( 'create', 0, PARAM_BOOL );
$password = optional_param ( 'group_password', 0, PARAM_BOOL );
$export = optional_param ( 'export', 0, PARAM_BOOL );
$assign = optional_param ( 'assign', 0, PARAM_BOOL );

if ($g) {
	$groupselect = $DB->get_record ( 'groupselect', array (
			'id' => $g 
	), '*', MUST_EXIST );
	$cm = get_coursemodule_from_instance ( 'groupselect', $groupselect->id, $groupselect->course, false, MUST_EXIST );
} else {
	$cm = get_coursemodule_from_id ( 'groupselect', $id, 0, false, MUST_EXIST );
	$groupselect = $DB->get_record ( 'groupselect', array (
			'id' => $cm->instance 
	), '*', MUST_EXIST );
}

$course = $DB->get_record ( 'course', array (
		'id' => $cm->course 
), '*', MUST_EXIST );

require_login ( $course, true, $cm );
$context = context_module::instance ( $cm->id );

add_to_log ( $course->id, 'groupselect', 'view', 'view.php?id=' . $cm->id, $groupselect->id, $cm->id );

$PAGE->set_url ( '/mod/groupselect/view.php', array (
		'id' => $cm->id 
) );
$PAGE->add_body_class ( 'mod_groupselect' );
$PAGE->set_title ( $course->shortname . ': ' . $groupselect->name );
$PAGE->set_heading ( $course->fullname );
$PAGE->set_activity_record ( $groupselect );

$mygroups = groups_get_all_groups ( $course->id, $USER->id, $groupselect->targetgrouping, 'g.*' );
$isopen = groupselect_is_open ( $groupselect );
$groupmode = groups_get_activity_groupmode ( $cm, $course );
$counts = groupselect_group_member_counts ( $cm, $groupselect->targetgrouping );
$groups = groups_get_all_groups ( $course->id, 0, $groupselect->targetgrouping );
$passwordgroups = groupselect_get_password_protected_groups ( $groupselect );
$hidefullgroups = $groupselect->hidefullgroups;
$exporturl = '';

$accessall = has_capability ( 'moodle/site:accessallgroups', $context );
$viewfullnames = has_capability ( 'moodle/site:viewfullnames', $context );
$canselect = (has_capability ( 'mod/groupselect:select', $context ) and is_enrolled ( $context ) and empty ( $mygroups ));
$canunselect = (has_capability ( 'mod/groupselect:unselect', $context ) and is_enrolled ( $context ) and ! empty ( $mygroups ));
$cancreate = ($groupselect->studentcancreate and has_capability ( 'mod/groupselect:create', $context ) and is_enrolled ( $context ) and empty ( $mygroups ));
$canexport = (has_capability ( 'mod/groupselect:export', $context ) and count ( $groups ) > 0);
$canassign = (has_capability ( 'mod/groupselect:assign', $context ) and 
        count($groupselect->assignteachers and groupselect_get_context_members_by_role ( $course_context, 4 ) > 0));

if ($course->id == SITEID) {
	$viewothers = has_capability ( 'moodle/site:viewparticipants', $context );
} else {
	$viewothers = has_capability ( 'moodle/course:viewparticipants', $context );
}

$strgroup = get_string ( 'group' );
$strgroupdesc = get_string ( 'groupdescription', 'group' );
$strmembers = get_string ( 'memberslist', 'mod_groupselect' );
$straction = get_string ( 'action', 'mod_groupselect' );
$strcount = get_string ( 'membercount', 'mod_groupselect' );

// problem notification
$problems = array ();

if (! is_enrolled ( $context )) {
	$problems [] = get_string ( 'cannotselectnoenrol', 'mod_groupselect' );
} else {
	if (! has_capability ( 'mod/groupselect:select', $context )) {
		$problems [] = get_string ( 'cannotselectnocap', 'mod_groupselect' );
	} else if ($groupselect->timedue != 0 and $groupselect->timedue < time ()) {
		$problems [] = get_string ( 'notavailableanymore', 'mod_groupselect', userdate ( $groupselect->timedue ) );
	}
}

// Student group self-creation
if ($cancreate and $isopen) {
	$data = array (
			'id' => $id,
			'description' => '' 
	);
	$mform = new create_form ( null, array (
			$data,
			$groupselect 
	) );
	if ($mform->is_cancelled ()) {
		redirect ( $PAGE->url );
	}
	if ($formdata = $mform->get_data ()) {
		// Create a new group and add the creator as a member of it
		$params = array (
				$course->id 
		);
		$names = $DB->get_records_sql ( "SELECT g.name
                   FROM {groups} g
                  WHERE g.courseid = ?", $params );
		
		$max = 0;
		foreach ( $names as $n ) {
			if (intval ( $n->name ) >= $max) {
				$max = intval ( $n->name );
			}
		}
		
		$data = ( object ) array (
				'name' => strval ( $max + 1 ),
				'description' => $formdata->description,
				'courseid' => $course->id 
		);
		$id = groups_create_group ( $data, false );
		if ($groupselect->targetgrouping != 0) {
			groups_assign_grouping ( $groupselect->targetgrouping, $id );
		}
		
		groups_add_member ( $id, $USER->id );
		add_to_log ( $course->id, 'groupselect', 'select', 'view.php?id=' . $cm->id, $groupselect->id, $cm->id );
		
		if ($formdata->password !== '') {
			$passworddata = ( object ) array (
					'groupid' => $id,
					'password' => password_hash ( $formdata->password, PASSWORD_DEFAULT ),
					'instance_id' => $groupselect->id 
			);
			$DB->insert_record ( 'groupselect_passwords', $passworddata, false );
		}
		redirect ( $PAGE->url );
	} else if ($create) {
		// If create button was clicked, show the form
		echo $OUTPUT->header ();
		echo $OUTPUT->heading ( get_string ( 'creategroup', 'mod_groupselect' ) );
		$mform->display ();
		echo $OUTPUT->footer ();
		die ();
	}
}

// Student group self-selection
if ($select and $canselect and isset ( $groups [$select] ) and $isopen) {
	
	$grpname = format_string ( $groups [$select]->name, true, array (
			'context' => $context 
	) );
	$usercount = isset ( $counts [$select] ) ? $counts [$select]->usercount : 0;
	
	$data = array (
			'id' => $id,
			'select' => $select,
			'group_password' => $password 
	);
	$mform = new select_form ( null, array (
			$data,
			$groupselect,
			$grpname 
	) );
	
	if ($mform->is_cancelled ()) {
		redirect ( $PAGE->url );
	}
	
	if (! $isopen) {
		$problems [] = get_string ( 'cannotselectclosed', 'mod_groupselect' );
	} else if ($groupselect->maxmembers and $groupselect->maxmembers <= $usercount) {
		$problems [] = get_string ( 'cannotselectmaxed', 'mod_groupselect', $grpname );
	} else if ($return = $mform->get_data ()) {
		groups_add_member ( $select, $USER->id );
		add_to_log ( $course->id, 'groupselect', 'select', 'view.php?id=' . $cm->id, $groupselect->id, $cm->id );
		redirect ( $PAGE->url );
	} else {
		echo $OUTPUT->header ();
		echo $OUTPUT->heading ( get_string ( 'select', 'mod_groupselect', $grpname ) );
		echo $OUTPUT->box_start ( 'generalbox', 'notice' );
		echo '<p>' . get_string ( 'selectconfirm', 'mod_groupselect', $grpname ) . '</p>';
		$mform->display ();
		echo $OUTPUT->box_end ();
		echo $OUTPUT->footer ();
		die ();
	}
} else if ($unselect and $canunselect and isset ( $mygroups [$unselect] )) {
	// user unselected group
	
	if (! $isopen) {
		$problems [] = get_string ( 'cannotunselectclosed', 'mod_groupselect' );
	} else if ($confirm and data_submitted () and confirm_sesskey ()) {
		groups_remove_member ( $unselect, $USER->id );
		if ($groupselect->deleteemptygroups and ! groups_get_members ( $unselect, $USER->id )) {
			groups_delete_group ( $unselect );
			$DB->delete_records ( 'groupselect_passwords', array (
					'groupid' => $unselect 
			) );
		}
		add_to_log ( $course->id, 'groupselect', 'unselect', 'view.php?id=' . $cm->id, $groupselect->id, $cm->id );
		redirect ( $PAGE->url );
	} else {
		$grpname = format_string ( $mygroups [$unselect]->name, true, array (
				'context' => $context 
		) );
		echo $OUTPUT->header ();
		echo $OUTPUT->heading ( get_string ( 'unselect', 'mod_groupselect', $grpname ) );
		$yesurl = new moodle_url ( '/mod/groupselect/view.php', array (
				'id' => $cm->id,
				'unselect' => $unselect,
				'confirm' => 1,
				'sesskey' => sesskey () 
		) );
		$message = get_string ( 'unselectconfirm', 'mod_groupselect', $grpname );
		echo $OUTPUT->confirm ( $message, $yesurl, $PAGE->url );
		echo $OUTPUT->footer ();
		die ();
	}
}

// Group user data export
if ($export and $canexport) {
	
	// Fetch groups & assigned teachers
	$sql = 'SELECT g.id AS groupid, g.name, g.description, u.username, u.firstname, u.lastname, u.email
			  FROM {groups} g
		 LEFT JOIN {groupselect_groups_teachers} gt
			    ON g.id = gt.groupid
		 LEFT JOIN {user} u 
			    ON u.id = gt.teacherid
			 WHERE g.courseid = ?
		  ORDER BY g.id ASC;';
	
	$group_list = $DB->get_records_sql ( $sql, array (
			$course->id 
	) );
	
	// Fetch students & groups
	$sql = 'SELECT u.username, u.idnumber, u.firstname, u.lastname, u.email, g.id AS groupid 
            FROM   {user} u, {groups} g, {groups_members} m
            WHERE  g.courseid = ?
            AND    g.id = m.groupid
            AND    u.id = m.userid
            ORDER BY groupid ASC;';
	
	$students = $DB->get_records_sql ( $sql, array (
			$course->id 
	) );
	
	// Fetch max number of students in a group (may differ from setting, because teacher may add members w/o limits)
	$sql = 'SELECT MAX(m.members) AS max
			  FROM (SELECT s.groupid, COUNT(s.groupid) AS members 
			          FROM (SELECT g.id AS groupid
            				  FROM {user} u, {groups} g, {groups_members} m
                             WHERE g.courseid = ?
                               AND g.id = m.groupid
                               AND u.id = m.userid
                          ORDER BY groupid ASC) s
			      GROUP BY s.groupid) m;';		
			
	$max_group_size = $DB->get_records_sql ( $sql, array (
			$course->id 
	) );
	$max_group_size = array_pop($max_group_size)->max;
	
	 foreach ($students as $student) {
	 	$gid = $student->groupid;
	 	foreach ($group_list as $group) {
	 		if($gid === $group->groupid) {
	 			for($i=1; $i < intval($max_group_size) + 1; $i++) {
	 				if(!isset($group->$i)) {
	 					$group->$i = $student;
	 					break;
	 				}
	 			}
	 		}
	 	}
	 }
	
	// Format data to csv
	$assigned_teacher = 'Assigned teacher ';
        $group_member = 'Member ';
         $header = array(
//			get_string ( 'groupid', 'mod_groupselect' ),
//			get_string ( 'groupname', 'group' ),
//			get_string ( 'groupdescription', 'group' ),
//			get_string ( 'assignedteacher', 'mod_groupselect' ) . ' ' . get_string ( 'username' ),
//			get_string ( 'assignedteacher', 'mod_groupselect' ) . ' ' . get_string ( 'firstname' ),
//			get_string ( 'assignedteacher', 'mod_groupselect' ) . ' ' . get_string ( 'lastname' ),
//			get_string ( 'assignedteacher', 'mod_groupselect' ) . ' ' . get_string ( 'email' ) 

        'Group ID',
        'Group Name',
        'Group Description',
        $assigned_teacher . 'Username',
        $assigned_teacher . 'Firstname',
        $assigned_teacher . 'Lastname',
        $assigned_teacher . 'Email',
            )
    ;
    for($i=0; $i < $max_group_size; $i++) {
//		$header[] = get_string('member', 'mod_groupselect').' '.strval($i+1).' '. get_string ( 'username' );
//		$header[] = get_string('member', 'mod_groupselect').' '.strval($i+1).' '. get_string ( 'idnumber' );
//		$header[] = get_string('member', 'mod_groupselect').' '.strval($i+1).' '. get_string ( 'firstname' );
//		$header[] = get_string('member', 'mod_groupselect').' '.strval($i+1).' '. get_string ( 'lastname' );
//		$header[] = get_string('member', 'mod_groupselect').' '.strval($i+1).' '. get_string ( 'email' );
            
            $header[] = $group_member.strval($i+1).' '.'Username';
            $header[] = $group_member.strval($i+1).' '.'ID Number';
            $header[] = $group_member.strval($i+1).' '.'Firstname';
            $header[] = $group_member.strval($i+1).' '.'Lastname';
            $header[] = $group_member.strval($i+1).' '.'Email';
	}
	$content = implode ( (','), $header ) . "\n";
        
        // Workaround for Excel
        $content = 'sep=,' . "\n" . $content;
	
        foreach ( $group_list as $r ) {
		$row = array (
				$r->groupid,
				$r->name,
				$r->description,
				$r->username,
				$r->firstname,
				$r->lastname,
				$r->email, 
				
		);
		for($i=1; $i < $max_group_size +1; $i++) {
			if(isset($r->$i)) {
				foreach ($r->$i as $member_field) {
					$row[] = $member_field;
				}
				array_pop($row);
			}
		}
		$content = $content . implode ( (','), $row ) . "\n";
	}
	
	// // TODO: groupings
	// // Get the wanted student & group data
	// $sql = 'SELECT u.username, u.idnumber, u.firstname, u.lastname, u.email, g.name, g.id AS groupid
	// FROM {user} u, {groups} g, {groups_members} m
	// WHERE g.courseid = ?
	// AND g.id = m.groupid
	// AND u.id = m.userid
	// ORDER BY u.lastname, u.firstname ASC;';
	// $result = $DB->get_records_sql($sql, array(
	// $course->id
	// ));
	
	// // Format data to csv
	// $header = array(
	// get_string('username'),
	// get_string('idnumber'),
	// get_string('firstname'),
	// get_string('lastname'),
	// get_string('email'),
	// get_string('groupname', 'group'),
	// get_string('groupid', 'mod_groupselect')
	// );
	// $content = implode((', '), $header) . "\n";
	// foreach ($result as $r) {
	// $row = array(
	// $r->username,
	// $r->idnumber,
	// $r->firstname,
	// $r->lastname,
	// $r->email,
	// $r->name,
	// $r->groupid,
	// );
	// $content = $content . implode((', '), $row) . "\n";
	// }
	
	// File info
	$separator = '_';
	$filename = get_string ( 'modulename', 'mod_groupselect' ) . $separator . $course->shortname . $separator . date ( 'Y-m-d' ) . '.csv';
	$filename = str_replace ( ' ', '', $filename );
	$fs = get_file_storage ();
	$fileinfo = array (
			'contextid' => $context->id, // ID of context
			'component' => 'mod_groupselect', // usually = table name
			'filearea' => 'export', // usually = table name
			'itemid' => $groupselect->id, // usually = ID of row in table
			'filepath' => '/', // any path beginning and ending in /
			'filename' => $filename 
	); // any filename
	   
	// See if same file exists
	$file = $fs->get_file ( $fileinfo ['contextid'], $fileinfo ['component'], $fileinfo ['filearea'], $fileinfo ['itemid'], $fileinfo ['filepath'], $fileinfo ['filename'] );
	
	// Delete already existing file
	if ($file) {
		$file->delete ();
	}
	
	$file = $fs->create_file_from_string ( $fileinfo, $content );
	// Store file url to show later
	$exporturl = moodle_url::make_pluginfile_url ( $file->get_contextid (), $file->get_component (), $file->get_filearea (), $file->get_itemid (), $file->get_filepath (), $file->get_filename () );
}

if ($assign and $canassign) {
	
	$already_assigned = count ( $DB->get_records ( 'groupselect_groups_teachers', array (
			'instance_id' => $id 
	) ) ) > 0 ? true : false;
	if ($already_assigned) {
		$DB->delete_records ( 'groupselect_groups_teachers', array (
				'instance_id' => $id 
		) );
	}
	
	$course_context = context_course::instance ( $course->id )->id;
	$teachers = groupselect_get_context_members_by_role ( $course_context, 4 );
        shuffle( $teachers );
	
	$group_teacher_relations = array ();
	$agroups = $groups;
	foreach ( $teachers as $teacher ) {
		$i = 0;
		$iterations = ceil ( count( $agroups ) / count ( $teachers ));
		while ( $i < $iterations ) {
			$group = array_rand ( $agroups );
			unset ( $agroups [$group] );
			array_push ( $group_teacher_relations, ( object ) array (
					'groupid' => $group,
					'teacherid' => $teacher->userid,
					'instance_id' => $id 
			) );
			$i ++;
		}
	}
	$DB->insert_records ( 'groupselect_groups_teachers', $group_teacher_relations );
	// foreach($group_teacher_relations as $g) {
	// echo $g->groupid . ' ';
	// echo $g->teacherid . '; ';
	// }
	// die;
}

// Page output
echo $OUTPUT->header ();
echo $OUTPUT->heading ( format_string ( $groupselect->name, true, array (
		'context' => $context 
) ) );

if (trim ( strip_tags ( $groupselect->intro ) )) {
	echo $OUTPUT->box_start ( 'mod_introbox', 'groupselectintro' );
	echo format_module_intro ( 'page', $groupselect, $cm->id );
	echo $OUTPUT->box_end ();
}

// Too few members in my group -notification
if ($groupselect->minmembers > 0 and ! empty ( $mygroups )) {
	$mygroup = array_keys ( $mygroups );
	$mygroup = $mygroup [0];
	$usercount = isset ( $counts [$mygroup] ) ? $counts [$mygroup]->usercount : 0;
	if ($groupselect->minmembers > $usercount) {
		echo $OUTPUT->notification ( get_string ( 'minmembers_notification', 'mod_groupselect', $groupselect->minmembers ) );
	}
}

// Activity opening/closing related notificatinos
if ($groupselect->timeavailable !== 0 and $groupselect->timeavailable > time ()) {
	echo $OUTPUT->notification ( get_string ( 'timeavailable', 'mod_groupselect' ) . ' ' . strval ( userdate ( $groupselect->timeavailable ) ) );
}
if ($groupselect->timedue !== 0 and $groupselect->timedue > time ()) {
	echo $OUTPUT->notification ( get_string ( 'timedue', 'mod_groupselect' ) . ' ' . strval ( userdate ( $groupselect->timedue ) ) );
}

if ($cancreate and $isopen and ! $create) {
	echo $OUTPUT->single_button ( new moodle_url ( '/mod/groupselect/view.php', array (
			'id' => $cm->id,
			'create' => true 
	) ), get_string ( 'creategroup', 'mod_groupselect' ) );
}
if ($canexport) {
    if( $exporturl === '' ) {
    echo $OUTPUT->single_button ( new moodle_url ( '/mod/groupselect/view.php', array (
			'id' => $cm->id,
			'export' => true 
	) ), get_string ( 'export', 'mod_groupselect' ) );
    }
    else{
		echo $OUTPUT->action_link ( $exporturl, get_string ( 'export_download', 'mod_groupselect' ) );
	}
}
if ($canassign) {
	echo $OUTPUT->single_button ( new moodle_url ( '/mod/groupselect/view.php', array (
			'id' => $cm->id,
			'assign' => true 
	) ), get_string ( 'assigngroup', 'mod_groupselect' ) );
}

if (empty ( $groups )) {
	echo $OUTPUT->notification ( get_string ( 'nogroups', 'mod_groupselect' ) );
} else {
	if ($problems) {
		foreach ( $problems as $problem ) {
			echo $OUTPUT->notification ( $problem, 'notifyproblem' );
		}
	}
	
	$data = array ();
	$actionpresent = false;
	
	$assigned_relation = $DB->get_records_sql ( 'SELECT g.id AS rid, g.teacherid AS id, g.groupid
    											FROM  {groupselect_groups_teachers} g
    									     	WHERE g.instance_id = ?;', array (
			'instance_id' => $id 
	) );
	$assigned_teacher_ids = array ();
	foreach ( $assigned_relation as $r ) {
		array_push ( $assigned_teacher_ids, $r->id );
	}
	$assigned_teacher_ids = array_unique ( $assigned_teacher_ids );
	// echo 'count ' . count($assigned_teacher_ids) . '<br>';
	// foreach ($assigned_teacher_ids as $i) {
	// echo strval($i) . ', ';
	// }
	if (count ( $assigned_teacher_ids ) > 0) {
		$sql = 'SELECT   *
    		      FROM   {user} u
    		     WHERE ';
		foreach ( $assigned_teacher_ids as $i ) {
			$sql = $sql . 'u.id = ? OR ';
		}
		$sql = substr ( $sql, 0, - 3 );
		$sql = $sql . ';';
		$assigned_teachers = $DB->get_records_sql ( $sql, $assigned_teacher_ids );
	}
	
	// echo count($assigned_teachers);
	// foreach ($assigned_teachers as $t) {
	// echo $t->firstname;
	// }
	
	// Group list
	foreach ( $groups as $group ) {
		
		$ismember = isset ( $mygroups [$group->id] );
		$usercount = isset ( $counts [$group->id] ) ? $counts [$group->id]->usercount : 0;
		$grpname = format_string ( $group->name, true, array (
				'context' => $context 
		) );
		
		// Skips listing full groups if set
		if (! $ismember and $hidefullgroups and $groupselect->maxmembers === $usercount) {
			continue;
		}
		
		if (in_array ( $group->id, $passwordgroups )) {
			$group->password = true;
		} else {
			$group->password = false;
		}
		
		// Groupname
		$line = array ();
		if ($ismember) {
			$line [0] = '<div class="mygroup">' . $grpname . '</div>';
		} else {
			$line [0] = $grpname;
		}
		
		// Group description
		$line [1] = groupselect_get_group_info ( $group );
		
		// Member count
		if ($groupselect->maxmembers) {
			$line [2] = $usercount . '/' . $groupselect->maxmembers;
		} else {
			$line [2] = $usercount;
		}
		
		if ($accessall) {
			$canseemembers = true;
		} else {
			if ($groupmode == SEPARATEGROUPS and ! $ismember) {
				$canseemembers = false;
			} else {
				$canseemembers = $viewothers;
			}
		}
		
		// Group members
		if ($canseemembers) {
			if ($members = groups_get_members ( $group->id )) {
				$membernames = array ();
				foreach ( $members as $member ) {
					$pic = $OUTPUT->user_picture ( $member, array (
							'courseid' => $course->id 
					) );
					if ($member->id == $USER->id) {
						$membernames [] = '<span class="me">' . $pic . '&nbsp;' . fullname ( $member, $viewfullnames ) . '</span>';
					} else {
						$membernames [] = $pic . '&nbsp;<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $member->id . '&amp;course=' . $course->id . '">' . fullname ( $member, $viewfullnames ) . '</a>';
					}
				}
				$teacherid = null;
				foreach ( $assigned_relation as $r ) {
					// echo $r->id . ' ' . $r->groupid . ' ' . $r->teacherid . '<br>';
					if ($r->groupid === $group->id) {
						$teacherid = $r->id;
						break;
					}
				}
				if ($teacherid) {
					$teacher = null;
					foreach ( $assigned_teachers as $a ) {
						if ($a->id === $teacherid) {
							$teacher = $a;
							$break;
						}
					}
					$pic = $OUTPUT->user_picture ( $teacher, array (
							'courseid' => $course->id 
					) );
					if ($teacher->id == $USER->id) {
						$membernames [] = '<span class="me">' . $pic . '&nbsp;' . fullname ( $teacher, $viewfullnames ) . '</span>';
					} else {
						$membernames [] = $pic . '&nbsp;<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $teacher->id . '&amp;course=' . $course->id . '">' . fullname ( $teacher, $viewfullnames ) . ' (' . get_string ( 'assignedteacher', 'mod_groupselect' ) . ')</a>';
					}
				}
				$line [3] = implode ( ', ', $membernames );
			} else {
				$line [3] = '';
			}
		} else {
			$line [3] = '<div class="membershidden">' . get_string ( 'membershidden', 'mod_groupselect' ) . '</div>';
		}
		
		// Icons
		$line [4] = '<div class="icons">';
		if ($groupselect->minmembers > $usercount) {
			$line [4] = $line [4] . $OUTPUT->pix_icon ( 'i/risk_xss', get_string ( 'minmembers_icon', 'mod_groupselect' ), null, array (
					'align' => 'left' 
			) );
		}
		if ($group->password) {
			$line [4] = $line [4] . $OUTPUT->pix_icon ( 't/locked', get_string ( 'password', 'mod_groupselect' ), null, array (
					'align' => 'right' 
			) );
		}
		$line [4] = $line [4] . '</div>';
		
		// Action buttons
		if ($isopen and ! $accessall) {
			if (! $ismember and $canselect and $groupselect->maxmembers and $groupselect->maxmembers <= $usercount) {
				$line [5] = '<div class="maxlimitreached">' . get_string ( 'maxlimitreached', 'mod_groupselect' ) . '</div>'; // full - no more members
				$actionpresent = true;
			} else if ($ismember and $canunselect) {
				$line [5] = $OUTPUT->single_button ( new moodle_url ( '/mod/groupselect/view.php', array (
						'id' => $cm->id,
						'unselect' => $group->id 
				) ), get_string ( 'unselect', 'mod_groupselect', $grpname ) );
				$actionpresent = true;
			} else if (! $ismember and $canselect) {
				$line [5] = $OUTPUT->single_button ( new moodle_url ( '/mod/groupselect/view.php', array (
						'id' => $cm->id,
						'select' => $group->id,
						'group_password' => $group->password 
				) ), get_string ( 'select', 'mod_groupselect', $grpname ) );
				$actionpresent = true;
			} else {
				$line [5] = '';
			}
		}
		$data [] = $line;
	}
	
	$sortscript = file_get_contents ( './lib/sorttable/sorttable.js' );
	echo html_writer::script ( $sortscript );
	$table = new html_table ();
	$table->attributes = array (
			'class' => 'generaltable sortable' 
	);
	$table->head = array (
			$strgroup,
			$strgroupdesc,
			$strcount,
			$strmembers,
			'' 
	);
	$table->size = array (
			'5%',
			'30%',
			'5%',
			'52%',
			'8%',
			'0%' 
	);
	$table->align = array (
			'left',
			'center',
			'left',
			'left',
			'left',
			'center' 
	);
	if ($actionpresent) {
		$table->head [] = $straction;
		$table->size = array (
				'5%',
				'30%',
				'5%',
				'42%',
				'8%',
				'10%' 
		);
	}
	$table->data = $data;
	echo html_writer::table ( $table );
}

echo $OUTPUT->footer ();

