<?php

namespace mod_groupselect;

class groupselect_groups
{
    const TABLE = 'groupselect_groups';

    /**
     * @param int $group_id
     * @return bool
     * @throws \dml_exception
     */
    public static function deleteGroupInfo(int $group_id)
    {
        global $DB;
        return $DB->delete_records(groupselect_groups::TABLE, ['groupid' => $group_id]);
    }

    /**
     * @param int $group_id
     * @return void
     * @throws \dml_exception
     */
    public static function autofillGroupLeader(int $group_id)
    {
        global $DB;
        $leader_id = $DB->get_field_sql(
            "select userid from {groups_members} 
              where groupid = :groupid 
              order by timeadded 
              limit 1",
            ['groupid' => $group_id]
        );
        if (empty($leader_id)) {
            self::removeGroupLeader($group_id);
        } else {
            self::setGroupLeader($group_id, $leader_id);
        }
    }

    /**
     * @param int $user_id
     * @return bool
     * @throws \dml_exception
     */
    public static function autofillLeaderWhenUserDeleted(int $user_id)
    {
       global $DB;
       $records = $DB->get_records(self::TABLE, ['leaderid' => $user_id]);
       foreach ($records as $record) {
           self::autofillGroupLeader($record->groupid);
       }
       return true;
    }

    /**
     * @param int $group_id
     * @param int $user_id
     * @return bool
     * @throws \dml_exception
     */
    public static function setGroupLeader(int $group_id, int $user_id) : bool
    {
        global $DB;
        $data = $DB->get_record(self::TABLE, ['groupid' => $group_id]) ?? (new \stdClass());
        $data->groupid = $group_id;
        $data->leaderid = $user_id;

        if (empty($data->id)) {
            return $DB->insert_record(self::TABLE, $data);
        }
        return $DB->update_record(self::TABLE, $data);
    }

    /**
     * @param int $group_id
     * @return int|null
     * @throws \dml_exception
     */
    public static function getGroupLeader(int $group_id) : ?int
    {
        global $DB;
        return $DB->get_field(self::TABLE, 'leaderid', ['groupid' => $group_id]);
    }

    /**
     * @param int $group_id
     * @return bool
     * @throws \dml_exception
     */
    public static function removeGroupLeader(int $group_id) : bool
    {
        global $DB;
        $id = $DB->get_field(self::TABLE, 'id', ['groupid' => $group_id]);
        if ($id) {
            return $DB->update_record(self::TABLE, (object)['id' => $id, 'leaderid' => null]);
        }
        return true;
    }

    /**
     * @param int $groupid
     * @return int
     * @throws \dml_exception
     */
    public static function getGroupMembersCount(int $groupid) : int
    {
        global $DB;
        return $DB->count_records('groups_members', ['groupid' => $groupid]);
    }

    public static function setGroupModCompleted(int $group_id, int $cm_id)
    {
        global $DB;
        $data = $DB->get_record(self::TABLE, ['groupid' => $group_id]) ?? (new \stdClass());
        $data->groupid = $group_id;
        $data->modcompleted = $cm_id;

        if (empty($data->id)) {
            return $DB->insert_record(self::TABLE, $data);
        }
        return $DB->update_record(self::TABLE, $data);
    }
    public static function setGroupModCompletedByUser(int $user_id, int $cm_id)
    {
        global $DB;
        $sql = "select gg.groupid as id
            from {groupselect} gs
            join {groupings_groups} gg on gs.targetgrouping = gg.groupingid
            join {groups_members} gm on gm.groupid = gg.groupid
            where gs.restrictleavewhenmodcompleted = :cmid and gm.userid = :userid";
        $groupids = $DB->get_fieldset_sql($sql, ['userid' => $user_id, 'cmid' => $cm_id]);
        foreach ($groupids as $groupid) {
            self::setGroupModCompleted($groupid, $cm_id);
        }
        return true;
    }

    public static function groupHasCompletedMod(int $group_id, int $cm_id) {
        global $DB;
        return $DB->record_exists(self::TABLE, ['groupid' => $group_id, 'modcompleted' => $cm_id]);
    }
}