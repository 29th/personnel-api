<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Vanilla {
    
    private $forums_db;
    
    public function __construct() {
        $this->forums_db = $this->load->database('forums', TRUE);
    }
    
    /**
     * Enables the use of CI super-global without having to define an extra variable
     */
    public function __get($var) {
        return get_instance()->$var;
    }

    /**
     * Update Member Roles
     * For each active assignment, fetches the forum roles and sets them in the forum, erasing any other roles
     */
    public function update_roles($member_id) {
        $this->load->model('member_model');
        $this->load->model('assignment_model');
        $this->load->model('unit_role_model');
        $this->load->model('class_role_model');
        $this->load->model('discharge_model');

        $roles = array();
        
        // Get member info
        $member = nest($this->member_model->get_by_id($member_id));
        
        // If no forum_member_id, there's nothing to do
        if( ! $member['forum_member_id']) {
            //$this->response(array('status' => false, 'error' => 'Member does not have a corresponding forum user id'), 400);
            return FALSE;
        }
        
        // Get all of the member's assignments
        $assignments = nest($this->assignment_model->where('assignments.member_id', $member_id)->order_by('priority')->by_date()->get()->result_array());
        
        $classes = array_unique(array_map(function($row) {
            return $row['unit']['class'];
        }, $assignments));
        
        // For each assignment, get the corresponding forum roles for the assignment's access level
        foreach($assignments as $assignment) {
            $assignment_roles = $this->unit_role_model->by_unit($assignment['unit']['id'], $assignment['position']['access_level'])->get()->result_array();
            if( ! empty($assignment_roles)) {
                $roles = array_merge($roles, pluck('role_id', $assignment_roles));
            }
        }
        
        // Get forum roles for classes that member is a part of
        $class_roles = $this->class_role_model->by_classes($classes)->get()->result_array();
        if( ! empty($class_roles)) {
            $roles = array_merge($roles, pluck('role_id', $class_roles));
        }
        
        //If not assigned anywhere let's check if member had been HDed
        if (empty($roles) || $roles[0] == '8')
        {
            $this->discharge_model->where('discharges.member_id',$member_id);
            $discharge = $this->discharge_model->get()->result_array();
            if ( $discharge && $discharge[0]['type'] == "Honorable")
                $roles[] = '80';
        }
        
        //Adding for officers
        $rank = $member['rank']['abbr'];
        if( $rank == '2Lt.' || $rank == '1Lt.' || $rank == 'Cpt.' || $rank == 'Maj.' || $rank == 'Lt. Col.' || $rank == 'Col.' )
        {
            $roles[] = '73';//$this->get_commisioned_officer_role_id();
        }

        // Eliminate duplicates
        $roles = array_values(array_unique($roles));
        
        // Delete all of the user's roles from forums database ** by forum_member_id NOT member_id
        if( ! $this->forums_db->query('DELETE FROM `GDN_UserRole` WHERE `UserID` = ?', $member['forum_member_id'])) 
        {
            //$this->response(array('status' => false, 'error' => 'There was an issue deleting the user\'s old roles'));
            return FALSE;
        } 
        else 
        {
            
            // Insert new roles if there are any (there wouldn't be if member was discharged)
            if( ! empty($roles)) {
                $values = '(' . $member['forum_member_id'] . ', ' . implode('), (' . $member['forum_member_id'] . ', ', $roles) . ')';
                //die($values);
                if( ! $this->forums_db->query('INSERT INTO `GDN_UserRole` (`UserID`, `RoleID`) VALUES ' . $values)) {
                    //$this->response(array('status' => false, 'error' => 'There was an issue adding the user\'s roles'));
                    return FALSE;
                }
            }
            //$this->response(array('status' => true, 'roles' => $roles));
            return $roles; // Won't arrive here if insert failed. Should also arrive here if no roles to add (ie. discharged)
        }
    }
    
    /**
     * Find the steam id associated with the forum member account if it exists
     */
    public function update_username($member_id) {
        $this->load->model('member_model');
        
        // Get member info
        $member = nest($this->member_model->get_by_id($member_id));

        // If no forum_member_id, there's nothing to do
        if( ! $member['forum_member_id']) {
            //$this->response(array('status' => false, 'error' => 'Member does not have a corresponding forum user id'), 400);
            return FALSE;
        }
        
        if ( $member["unit"]["id"]) 
            $newMemberName = str_replace("/","",$member['short_name']);
        else 
        {
            $this->load->model('discharge_model');
            $this->discharge_model->where('discharges.member_id',$member_id);
                
            $disc = $this->discharge_model->get()->result_array();
            if ( $disc && $disc[0]['type'] == "Honorable")
                $newMemberName = str_replace("/","",$member['short_name']) . " [Ret.]";
            else
                $newMemberName = str_replace("/","",$member['rank']['name'] . " " . $member['full_name']);
            
            
        }
        
        return $this->forums_db->query('UPDATE GDN_User SET `Name` = ? WHERE UserID = ?', array($newMemberName, $member['forum_member_id']));
    }
    
    public function get_steam_id($user_id) {
        return $this->forums_db->query('SELECT `Value` FROM `GDN_UserMeta` WHERE `Name` = \'Plugin.steamprofile.SteamID64\' AND `UserID` = ' . (int) $user_id)->row_array();
    }
    
    public function get_role_list() {
        return $this->forums_db->query('SELECT `RoleID`, `Name` FROM GDN_Role ORDER BY `Sort`')->result_array();
    }

    public function get_commisioned_officer_role_id() {
        return $this->forums_db->query('SELECT `RoleID` FROM GDN_Role WHERE `name` = \'Commissioned Officer\'')->row_array()[0];
    }

    public function get_user_ip($member_id) {
        $res = $this->forums_db->query('SELECT `AllIPAddresses` FROM GDN_User WHERE `UserID` = ' . (int) $member_id)->row_array();
        
        $arr = ( isset( $res['AllIPAddresses'] ) ? explode( ',', $res['AllIPAddresses'] ) : [] );
        $arr2 = [];
        foreach( $arr as $ip )
        {
            if ( strpos( $ip, '0.0.0') === false && substr_count( $ip, '.')==3 )
            {
                $res2 = $this->forums_db->query('SELECT `UserID`,`Name` FROM GDN_User WHERE `AllIPAddresses` LIKE \'%' . $ip . '%\' AND `UserID` <> ' . (int) $member_id)->result_array();
                $arr2[] = array('ip' => $ip,'users' => $res2);
            }
        }
        return $arr2;
    }

    public function get_user_email($member_id) {
        $res = $this->forums_db->query('SELECT `Email` FROM GDN_User WHERE `UserID` = ' . (int) $member_id)->row_array();
        return ( $res ? $res['Email'] : '' );
    }

    public function get_user_bday($member_id) {
        $res = $this->forums_db->query('SELECT `DateOfBirth` FROM GDN_User WHERE `UserID` = ' . (int) $member_id)->row_array();
        return ( $res ? $res['DateOfBirth'] : '' );
    }

    public function get_ban_disputes( $roid ) {
        $res = $this->forums_db->query("
            SELECT 
                `DiscussionID` AS `id`, 
                `Name` AS `name`, 
                `DateInserted` AS `start`, 
                `DateLastComment` AS 'last' 
            FROM `GDN_Discussion` 
            WHERE `CategoryID`=92 AND `DiscussionID` IN (
                SELECT `DiscussionID` 
                FROM GDN_Discussion 
                WHERE `Body` LIKE '%$roid%' 
                UNION 
                SELECT `DiscussionID` 
                FROM GDN_Comment 
                WHERE `Body` LIKE '%$roid%'
            )
            ORDER BY `DateLastComment` DESC
            " )->result_array();
        return nest($res);//( $res ? $res['DiscussionID'] : '' );
    }

}