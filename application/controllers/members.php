<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Members extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('member_model');
        $this->load->model('assignment_model');
        $this->load->library('servicecoat');
    }
    
    /**
     * PRE-FLIGHT
     */
    public function index_options() { $this->response(array('status' => true)); }
    public function view_options() { $this->response(array('status' => true)); }
    
    /**
     * INDEX
     * We don't want to be able to fetch a list of members for all units, no need
     */
    //public function index_get() {}
    
    /**
     * VIEW
     */
    public function view_get($member_id) {
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // View record
        else {
            $member = nest($this->member_model->get_by_id($member_id));
            $member['classes'] = $this->assignment_model->get_classes($member_id);
            $this->response(array('status' => true, 'member' => $member));
        }
    }
    
    /**
     * CREATE
     * We don't want to be able to create members - they must enlist
     */
    //public function index_post() {}
    
    /**
     * UPDATE
     */
    public function view_post($member_id) {
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_edit', $member_id) && ! $this->user->permission('profile_edit_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Form validation
        else if($this->member_model->run_validation('validation_rules_edit') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->member_model->validation_errors), 400);
        }
        // Update record
        else {
            $data = whitelist($this->post(), array('last_name', 'first_name', 'middle_name', 'name_prefix', 'country_id', 'rank_id', 'steam_id', 'email')); // leave forum_member_id out, reserve for DB changes
        
			// Only use first letter of middle_name
			if(isset($data['middle_name']) && $data['middle_name']) $data['middle_name'] = substr($data['middle_name'], 0, 1);
				
            $result = $this->member_model->save($member_id, $data);
            
            // Update service coat
            $this->servicecoat->update($member_id);
            
            $this->response(array('status' => $result ? true : false, 'member' => $this->member_model->get_by_id($member_id)));
        }
    }
    
    /**
     * PROMOTIONS
     */
    public function promotions_get($member_id) {
        $this->load->model('promotion_model');
		
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $promotions = nest($this->promotion_model->where('promotions.member_id', $member_id)->get()->result_array());
            $this->response(array('status' => true, 'promotions' => $promotions));
        }
    }
    
    /*public function promotions_post($member_id) {
        if( ! $this->user->permission('promotion_add', $member_id) && ! $this->user->permission('promotion_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else if($this->form_validation->run('promotion_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->form_validation->get_error_array()), 400);
        } else {
            $data = $this->post();
            $data['member_id'] = $member_id;
            $insert_id = $this->promotion_model->save(NULL, $data);
            $new_record = $insert_id ? $this->promotion_model->get_by_id($insert_id) : null;
            
            // Check if this is the newest promotion; if so, change rank_id in members table
            $newer_promotions = $this->promotion_model->by_newer($member_id, $data['date'])->get()->num_rows();
            if( ! $newer_promotions) {
                $this->member_model->save($member_id, array('rank_id' => $data['new_rank_id']));
            }
            
            $this->response(array('status' => true, 'promotion' => $new_record));
        }
    }*/
    
    /*public function promotions_delete($member_id, $promotion_id) {
        if( ! $this->user->permission('promotion_delete', $member_id) && ! $this->user->permission('promotion_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->promotion_model->where('promotions.member_id', $member_id)->delete($promotion_id);
            
            // Update member's rank to last one
            if($newest = nest($this->promotion_model->where('promotions.member_id', $member_id)->limit(1)->get()->row_array())) {
                if(isset($newest['new_rank']['id'])) { // Make sure the query actually got a valid result
                    $this->member_model->save($member_id, array('rank_id' => $newest['new_rank']['id']));
                }
            }
            $this->response(array('status' => true));
        }
    }*/
    
    /**
     * AWARDS
     */
    public function awardings_get($member_id) {
        $this->load->model('awarding_model');
		
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $awardings = nest($this->awarding_model->where('awardings.member_id', $member_id)->get()->result_array());
            $this->response(array('status' => true, 'awardings' => $awardings));
        }
    }
    
    /*public function awardings_post($member_id) {
        if( ! $this->user->permission('awarding_add', $member_id) && ! $this->user->permission('awarding_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else if($this->form_validation->run('awarding_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->form_validation->get_error_array()), 400);
        } else {
            $data = $this->post();
            $data['member_id'] = $member_id;
            $insert_id = $this->awarding_model->save(NULL, $data);
            $new_record = $insert_id ? $this->awarding_model->get_by_id($insert_id) : null;        
            $this->response(array('status' => true, 'awarding' => $new_record));
        }
    }*/
    
    /*public function awardings_delete($member_id, $awarding_id) {
        if( ! $this->user->permission('awarding_delete', $member_id) && ! $this->user->permission('awarding_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->awarding_model->where('awardings.member_id', $member_id)->delete($awarding_id);
            $this->response(array('status' => true));
        }
    }*/
    
    /**
     * ATTENDANCE
     */
    public function attendance_get($member_id) {
        $this->load->model('attendance_model');
		
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $skip = $this->input->get('skip') ? $this->input->get('skip') : 0;
            $attendance = nest($this->attendance_model->by_member($member_id)->paginate('', $skip)->result_array());
            $count = $this->attendance_model->total_rows;
            $this->response(array('status' => true, 'count' => $count, 'skip' => $skip, 'attendance' => $attendance));
        }
    }
    
    /**
     * Qualifications
     */
    public function qualifications_get($member_id) {
        $this->load->model('qualification_model');
        $this->load->model('standard_model');
		
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            //$qualifications = nest($this->qualification_model->where('qualifications.member_id', $member_id)->get()->result_array());
            $qualifications = nest($this->standard_model->for_member($member_id)->get()->result_array());
            $this->response(array('status' => true, 'qualifications' => $qualifications));
        }
    }
    
    /*public function qualifications_post($member_id) {
        if( ! $this->user->permission('qualification_add', $member_id) && ! $this->user->permission('qualification_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else if($this->form_validation->run('qualification_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->form_validation->get_error_array()), 400);
        } else {
            $data = $this->post();
            $data['member_id'] = $member_id;
            $insert_id = $this->qualification_model->save(NULL, $data);
            $new_record = $insert_id ? $this->qualification_model->get_by_id($insert_id) : null;        
            $this->response(array('status' => $status, 'qualification' => $new_record));
        }
    }*/
    
    /*public function qualifications_delete($member_id, $qualification_id) {
        if( ! $this->user->permission('qualification_delete', $member_id) && ! $this->user->permission('qualification_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->qualification_model->where('qualifications.member_id', $member_id)->delete($qualification_id);
            $this->response(array('status' => true));
        }
    }*/
    
    /**
     * ASSIGNMENTS
     */
    public function assignments_get($member_id) {
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $model = $this->assignment_model->where('assignments.member_id', $member_id)->order_by('priority');
            if($this->input->get('current')) $model->by_date();
            $assignments = nest($model->get()->result_array());
            $this->response(array('status' => true, 'assignments' => $assignments));
        }
    }
    
    /*public function assignments_post($member_id, $assignment_id = FALSE) {
        if( ! $this->user->permission('assignment_add', $member_id) && ! $this->user->permission('assignment_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else if($this->form_validation->run('assignment_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->form_validation->get_error_array()), 400);
        } else {
            $data = $this->post();
            $data['member_id'] = $member_id;
            //$primary_assignment = isset($data['primary_assignment']) ? $data['primary_assignment'] : false;
            //if(isset($data['primary_assignment'])) unset($data['primary_assignment']);
            $insert_id = $this->assignment_model->save($assignment_id ? $assignment_id : NULL, $data); // Will FALSE work in place of NULL?
            $new_record = $insert_id ? $this->assignment_model->get_by_id($assignment_id ? $assignment_id : $insert_id) : null;
            
            // If primary assignment, update member record with new id
            if($primary_assignment) {
                $this->member_model->save($member_id, array('primary_assignment_id' => $new_record['id']));
            }
            
            $this->response(array('status' => $status, 'assignment' => $new_record));
        }
    }*/
    
    // TODO: What if this is a primary assignment? Should we change the primary_assignment_id in the members table?
    /*public function assignments_delete($member_id, $assignment_id) {
        if( ! $this->user->permission('assignment_delete', $member_id) && ! $this->user->permission('assignment_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->assignment_model->where('assignments.member_id', $member_id)->delete($assignment_id);
            $this->response(array('status' => true));
        }
    }*/
    
    /**
     * ENLISTMENTS
     */
    public function enlistments_get($member_id) {
        $this->load->model('enlistment_model');
		
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $enlistments = nest($this->enlistment_model->where('enlistments.member_id', $member_id)->get()->result_array());
            $this->response(array('status' => true, 'enlistments' => $enlistments));
        }
    }
    
    /**
     * DISCHARGES
     */
    public function discharges_get($member_id) {
        $this->load->model('discharge_model');
		
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $discharges = nest($this->discharge_model->where('discharges.member_id', $member_id)->get()->result_array());
            $this->response(array('status' => true, 'discharges' => $discharges));
        }
    }
    
    /**
     * AWOLs
     */
    public function awols_get($member_id) {
        $this->load->model('attendance_model');
        $days = $this->input->get('days') ? (int) $this->input->get('days') : 30;
		
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
			$awols = nest($this->attendance_model->member_awols($member_id, $days)->get()->result_array());
            $this->response(array('status' => true, 'awols' => $awols));
        }
    }
    
    /**
     * EXECUTE DISCHARGE
     */
    public function discharge_post($member_id) {
        // Must have permission to modify assignments for this member or for any member, since discharging is the equivalent of ending all assignments
        if( ! $this->user->permission('assignment_add', $member_id) && ! $this->user->permission('assignment_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Execute
        else {
            $result = $this->assignment_model->discharge($member_id);
            $this->response(array('status' => $result ? true : false));
        }
    }
    
    /**
     * SERVICE COAT
     * TODO: Add permissions and add it to member admin drop-down
     */
    public function coat_get($member_id) {
        // Must have permission to modify profile, add promotion, or add awarding for this member or for any member, as these actions require a service coat updated
        if( ! $this->user->permission('profile_edit', $member_id) && ! $this->user->permission('profile_edit_any')
        &&  ! $this->user->permission('promotion_add', $member_id) && ! $this->user->permission('promotion_add_any')
        &&  ! $this->user->permission('awarding_add', $member_id) && ! $this->user->permission('awarding_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Execute
        else {
            $data = $this->servicecoat->update($member_id);
            $this->response(array('status' => true, 'coat' => $data));
        }
    }
    
    public function roles_get($member_id) {
        // Must have permission to modify assignments for this member or for any member
        if( ! $this->user->permission('assignment_add', $member_id) && ! $this->user->permission('assignment_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Execute
        else {
            $this->load->library('vanilla');
            if($roles = $this->vanilla->update_roles($member_id)) {
                $this->response(array('status' => true, 'roles' => $roles));
            } else {
                $this->response(array('status' => false, 'error' => 'There was an issue updating the user\'s roles'));
            }
        }
    }
    
    /*private function update_roles($member_id) {
        $this->load->model('unit_role_model');
        $this->load->model('class_role_model');
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
            $assignment_roles = $this->unit_role_model->by_unit($assignment['unit']['id'], $assignment['access_level'])->get()->result_array();
            if( ! empty($assignment_roles)) {
                $roles = array_merge($roles, pluck('role_id', $assignment_roles));
            }
        }
        
        // Get forum roles for classes that member is a part of
        $class_roles = $this->class_role_model->by_classes($classes)->get()->result_array();
        if( ! empty($class_roles)) {
            $roles = array_merge($roles, pluck('role_id', $class_roles));
        }
        
        // Eliminate duplicates
        $roles = array_unique($roles);
        
        $forums_db = $this->load->database('forums', TRUE);
        
        // Delete all of the user's roles from forums database ** by forum_member_id NOT member_id
        if( ! $forums_db->query('DELETE FROM `GDN_UserRole` WHERE `UserID` = ?', $member['forum_member_id'])) {
            //$this->response(array('status' => false, 'error' => 'There was an issue deleting the user\'s old roles'));
            return FALSE;
        } else {
            
            // Insert new roles if there are any (there wouldn't be if member was discharged)
            if( ! empty($roles)) {
                $values = '(' . $member['forum_member_id'] . ', ' . implode('), (' . $member['forum_member_id'] . ', ', $roles) . ')';
                //die($values);
                if( ! $forums_db->query('INSERT INTO `GDN_UserRole` (`UserID`, `RoleID`) VALUES ' . $values)) {
                    //$this->response(array('status' => false, 'error' => 'There was an issue adding the user\'s roles'));
                    return FALSE;
                }
            }
            //$this->response(array('status' => true, 'roles' => $roles));
            return $roles; // Won't arrive here if insert failed. Should also arrive here if no roles to add (ie. discharged)
        }
    }*/
}