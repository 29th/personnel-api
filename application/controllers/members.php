<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Members extends MY_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('member_model');
        $this->load->model('promotion_model');
        $this->load->model('awarding_model');
        $this->load->model('qualification_model');
        $this->load->model('standard_model');
        $this->load->model('assignment_model');
        $this->load->model('attendance_model');
        $this->load->model('enlistment_model');
        $this->load->library('form_validation');
    }
    
    /**
     * Works for index and view
     */
    public function basic_get($member_id = FALSE) {
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else if($member_id !== FALSE) {
            $member = nest($this->member_model->get_by_id($member_id));
            $member['classes'] = $this->assignment_model->get_classes($member_id);
            $this->response(array('status' => true, 'member' => $member));
        }
        else {
            $this->response(array(), 404);
        }
    }
    
    /**
     * Works for insert and update
     * TODO: What if they include `id` in the post body? Shouldn't I have white listed fields?
     */
    public function basic_post($member_id = FALSE) {
        //$this->form_validation->set_group_rules('profile_edit');
        //if($member_id === FALSE) $this->form_validation->set_group_rules('profile_add');
        
        if( ! $this->user->permission('profile_edit', $member_id) && ! $this->user->permission('profile_edit_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        /*else if($this->form_validation->run('profile_edit') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->form_validation->get_error_array()), 400);
        }*/ else {
            $insert_id = $this->member_model->save($member_id ? $member_id : NULL, $this->post()); // Can FALSE suffice for NULL?
            $this->response(array('status' => true, 'member' => $insert_id ? nest($this->member_model->get_by_id($insert_id)) : null));
        }
    }
    
    // Necessary to support OPTIONS method
    public function basic_options($event_id) {
        $this->response(array('status' => true));
    }
    
    /**
     * Promotions
     */
    
    public function promotions_get($member_id) {
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $promotions = nest($this->promotion_model->where('promotions.member_id', $member_id)->get()->result_array());
            $this->response(array('status' => true, 'promotions' => $promotions));
        }
    }
    
    public function promotions_post($member_id) {
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
    }
    
    public function promotions_delete($member_id, $promotion_id) {
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
    }
    
    /**
     * Awards
     */
    
    public function awardings_get($member_id) {
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $awardings = nest($this->awarding_model->where('awardings.member_id', $member_id)->get()->result_array());
            $this->response(array('status' => true, 'awardings' => $awardings));
        }
    }
    
    public function awardings_post($member_id) {
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
    }
    
    public function awardings_delete($member_id, $awarding_id) {
        if( ! $this->user->permission('awarding_delete', $member_id) && ! $this->user->permission('awarding_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->awarding_model->where('awardings.member_id', $member_id)->delete($awarding_id);
            $this->response(array('status' => true));
        }
    }
    
    /**
     * Attendance
     */
    /*public function attendance_get($member_id, $year = FALSE, $month = FALSE) {
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            // If no year/month provided, use this month
            if($year === FALSE || $month === FALSE) {
                $year = date('Y');
                $month = date('m');
            }
            $start = $year . '-' . $month . '-1';
            $end = $start . ' next month - 12 hours';
            $attendance = nest($this->attendance_model->by_member($member_id)->by_date($start, $end)->get()->result_array());
            $this->response(array('status' => true, 'attendance' => $attendance));
        }
    }*/
    
    public function attendance_get($member_id) {
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
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            //$qualifications = nest($this->qualification_model->where('qualifications.member_id', $member_id)->get()->result_array());
            $qualifications = nest($this->standard_model->for_member($member_id)->get()->result_array());
            $this->response(array('status' => true, 'qualifications' => $qualifications));
        }
    }
    
    public function qualifications_post($member_id) {
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
    }
    
    public function qualifications_delete($member_id, $qualification_id) {
        if( ! $this->user->permission('qualification_delete', $member_id) && ! $this->user->permission('qualification_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->qualification_model->where('qualifications.member_id', $member_id)->delete($qualification_id);
            $this->response(array('status' => true));
        }
    }
    
    /**
     * Assignments
     */
    
    public function assignments_get($member_id) {
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $model = $this->assignment_model->where('assignments.member_id', $member_id);
            if($this->input->get('current')) $model->by_date();
            $assignments = nest($model->get()->result_array());
            $this->response(array('status' => true, 'assignments' => $assignments));
        }
    }
    
    public function assignments_post($member_id, $assignment_id = FALSE) {
        if( ! $this->user->permission('assignment_add', $member_id) && ! $this->user->permission('assignment_add_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else if($this->form_validation->run('assignment_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->form_validation->get_error_array()), 400);
        } else {
            $data = $this->post();
            $data['member_id'] = $member_id;
            $primary_assignment = isset($data['primary_assignment']) ? $data['primary_assignment'] : false;
            if(isset($data['primary_assignment'])) unset($data['primary_assignment']);
            $insert_id = $this->assignment_model->save($assignment_id ? $assignment_id : NULL, $data); // Will FALSE work in place of NULL?
            $new_record = $insert_id ? $this->assignment_model->get_by_id($assignment_id ? $assignment_id : $insert_id) : null;
            
            // If primary assignment, update member record with new id
            if($primary_assignment) {
                $this->member_model->save($member_id, array('primary_assignment_id' => $new_record['id']));
            }
            
            $this->response(array('status' => $status, 'assignment' => $new_record));
        }
    }
    
    // TODO: What if this is a primary assignment? Should we change the primary_assignment_id in the members table?
    public function assignments_delete($member_id, $assignment_id) {
        if( ! $this->user->permission('assignment_delete', $member_id) && ! $this->user->permission('assignment_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->assignment_model->where('assignments.member_id', $member_id)->delete($assignment_id);
            $this->response(array('status' => true));
        }
    }
    
    /**
     * Enlistments
     */
    
    public function enlistments_get($member_id) {
        if( ! $this->user->permission('profile_view', $member_id) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        else {
            $enlistments = nest($this->enlistment_model->where('enlistments.member_id', $member_id)->get()->result_array());
            $this->response(array('status' => true, 'enlistments' => $enlistments));
        }
    }
    
    /**
     * Discharge
     */
    public function discharge_post($member_id) {
        if( ! $this->user->permission('discharge', $member_id) && ! $this->user->permission('discharge_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        } else {
            $this->assignment_model->discharge($member_id);
            $this->response(array('status' => true));
        }
    }
    
    /**
     * Service Coat
     */
    public function coat_get($member_id) {
        if( ! $member_id) $this->response(array('status' => false), 404);
        $this->load->library('servicecoat');
        $member = nest($this->member_model->get_by_id($member_id));
        $rank = str_replace( '/', '', str_replace('.', '', $member['rank']['abbr']) );
        $unit = '29th';
        //$awards = array('acamp', 'gcon', 'french', 'lom', 'aocc', 's:rifle:dod', 'e:mg:dod', 'dsc', 'aocc', 'aocc', 'adef', 'dod', 'aocc', 'cib1', 'aocc', 'm:armor:dh', 'aocc', 'ww1v', 'cab1', 'aocc', 'aocc', 'aocc', 'aocc', 'aocc', 'aocc', 'ww1v');
        $awardings = $this->awarding_model->where('awardings.member_id', $member_id)->get()->result_array();
        $awardings_abbr = pluck('award|abbr', $awardings);
        $this->servicecoat->update_servicecoatC($member['last_name'], $member['steam_id'], $rank, $unit, $awardings_abbr);
        $this->response(array('status' => true, 'coat' => array(
            'name' => $member['last_name']
            ,'id' => $member['steam_id']
            ,'rank' => $rank
            ,'unit' => $unit
            ,'awardings' => $awardings_abbr
        )));
    }
}