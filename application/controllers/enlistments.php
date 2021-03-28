<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Enlistments extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->model('member_model');
        $this->load->model('enlistment_model');
        $this->load->model('assignment_model');
    }
    
    /**
     * PRE-FLIGHT
     */
    public function index_options() { $this->response(array('status' => true)); }
    public function view_options() { $this->response(array('status' => true)); }
    public function process_options() { $this->response(array('status' => true)); }
    
    /**
     * INDEX
     * Paginates
     */
    public function index_get($member_id = FALSE) {
        // Must have permission to view any member's profile
        if( ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Index records
        else {
            $status = $this->input->get('status', TRUE);
            $game = $this->input->get('game', TRUE);
            $timezone = $this->input->get('timezone', TRUE);
            $skip = $this->input->get('skip') ? $this->input->get('skip', TRUE) : 0;
            $model = $this->enlistment_model;
            if($status) {
                $model->by_status($status);
            }
            if($game) {
                $model->by_game($game);
            }
            if($timezone) {
                $model->by_timezone($timezone);
            }
            if($member_id) {
                $model->where('enlistments.member_id', $member_id);
            }
            $enlistments = nest($model->paginate('', $skip)->result_array());
            $count = $this->enlistment_model->total_rows;
            $this->response(array('status' => true, 'count' => $count, 'skip' => $skip, 'enlistments' => $enlistments));
        }
    }
    
    /**
     * VIEW
     */
    public function view_get($enlistment_id) {
        $enlistment = nest($this->enlistment_model->get_by_id($enlistment_id));
        $enlistment['previous_units'] = json_decode( $enlistment['units'] );
        unset( $enlistment['units'] );
        // Must have permission to view this member's profile or any member's profile
        if( ! $this->user->permission('profile_view', array('member' => $enlistment['member']['id'])) && ! $this->user->permission('profile_view_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // View record
        else {
            if ( $enlistment['member']['forum_member_id']
                && ( $this->user->permission('enlistment_edit', array('member' => $enlistment['member']['id'])) || $this->user->permission('enlistment_edit_any') ) )
            {
                $temp = $this->forums->get_steam_id($enlistment['member_id']);
                $enlistment['forum_steam_id'] = str_replace( 'https://steamcommunity.com/openid/id/', '', ( $temp ? $temp['Value'] : '' ) );
                
                $enlistment['is_restricted'] = $this->is_it_a_restricted_name( $enlistment['last_name'], $enlistment['member_id'] );

                $ips =  $this->forums->get_user_ip($enlistment['member_id']);
                $enlistment['ips'] = $ips; 

                $bday =  $this->forums->get_user_bday($enlistment['member_id']);
                $enlistment['bday'] = $bday; 
                $enlistment['forums_age'] = $bday && floor((strtotime(date("Y-m-d")) - strtotime($bday))/(365.25*24*60*60));

                $email =  $this->forums->get_user_email($enlistment['member_id']);
                $enlistment['email'] = ( $email ? $email : '' );
                
                //Adding banlogs entries
                $this->load->model('banlog_model');
                $bm = $this->banlog_model;
                $bm->search_roid( ( $enlistment['member']['roid'] ? $enlistment['member']['roid'] : $enlistment['steam_id'] ) );
                $banlog = $bm->select_member()->get()->result_array();
                $enlistment['banlogs'] = $banlog;

                //Adding previous members
                $mem_list = $this->member_model->distinct_members()->search_member_name_or_roid($enlistment['last_name'],( $enlistment['member']['roid'] ? $enlistment['member']['roid'] : $enlistment['steam_id'] ))->get()->result_array();
                if ($mem_list)
                {
                  foreach ( $mem_list as $key => $member )
                  {
                    $res  = $this->db->query('SELECT * FROM discharges WHERE member_id = ' . $member['id'] . ' ORDER BY date DESC LIMIT 1;' )->result_array();
                    if ( $res ) 
                    {
                        $mem_list[$key]['dis|type'] = $res[0]['type'];
                        $mem_list[$key]['dis|date'] = $res[0]['date'];
                        $mem_list[$key]['dis|id']   = $res[0]['id'];
                    }

                    $res = $this->db->query('SELECT enlistments.*, units.abbr FROM enlistments LEFT JOIN units ON units.id = enlistments.unit_id WHERE member_id = ' . $member['id'] . ' ORDER BY date DESC LIMIT 1;' )->result_array();;
                    if ( $res ) 
                    {
                        $mem_list[$key]['enlist|status']  = $res[0]['status'];
                        $mem_list[$key]['enlist|date']    = $res[0]['date'];
                        $mem_list[$key]['enlist|id']      = $res[0]['id'];
                        $mem_list[$key]['enlist|unit_id'] = $res[0]['unit_id'];
                        $mem_list[$key]['enlist|tp']      = $res[0]['abbr'];
                    }

                  }
                    
                }
                $enlistment['other_members'] = nest($mem_list);
            }
            else
                $enlistment['forum_steam_id'] = '';
            $this->response(array('status' => true, 'enlistment' => $enlistment));
        }
    }
    
    /**
     * CREATE
     * TODO: Prevent enlistment of current members
     */
    public function index_post() {
        $this->load->model('restricted_names_model');
        // Must be logged in
        if( ! ($forum_member_id = $this->user->logged_in())) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Form validation for restricted names
//        else if($this->restricted_names_model->run_validation('validation_rules_add') === FALSE) {
//            $this->response(array('status' => false, 'error' => $this->restricted_names_model->validation_errors), 400);
//        }
        // Form validation for both models
        else if($this->enlistment_model->run_validation('validation_rules_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->enlistment_model->validation_errors), 400);
        }
        else if($this->member_model->run_validation('validation_rules_add') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->member_model->validation_errors), 400);
        }
        // Create record
        else {
            //$this->usertracking->track_this(); (no member id yet)
            $member_id = $this->user->member('id');
            // If no member record
            if( ! $member_id) {
                $member_data = whitelist($this->post(), array('last_name', 'first_name', 'middle_name', 'country_id')); // steam_id?
                $member_data['forum_member_id'] = $forum_member_id;
                $member_data['email'] = $this->user->member('forum_email');
        
                // Only use first letter of middle_name
                if(isset($member_data['middle_name']) && $member_data['middle_name']) $member_data['middle_name'] = substr($member_data['middle_name'], 0, 1);
                
                // Create member record
                $member_id = $this->member_model->save(NULL, $member_data);
            }
            // Create enlistment record using member_id
            $enlistment_data = whitelist($this->post(), array('first_name', 'middle_name', 'last_name', 'age', 'country_id', 'timezone', 'game', 'ingame_name', 'steam_name', 'steam_id', 'experience', 'recruiter', 'comments'));
            $enlistment_data['member_id'] = $member_id;
            $enlistment_data['status'] = 'Pending';
            $enlistment_data['date'] = format_date('now', 'mysqldate');
            
            $enlistment_data['units'] = json_encode($this->post('units'));
        
			// Only use first letter of middle_name
			if(isset($enlistment_data['middle_name']) && $enlistment_data['middle_name']) $enlistment_data['middle_name'] = substr($enlistment_data['middle_name'], 0, 1);
            
            $insert_id = $this->enlistment_model->save(NULL, $enlistment_data);
            $new_record = $insert_id ? nest($this->enlistment_model->get_by_id($insert_id)) : null;
            $this->response(array('status' => $insert_id ? true : false, 'enlistment' => $new_record));
        }
    }
    
    /**
     * UPDATE
     */
    public function view_post($enlistment_id) {
        // Must have permission to edit this type of record for any member
        if( ! $this->user->permission('enlistment_edit_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Form validation
        else if($this->enlistment_model->run_validation('validation_rules_edit') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->enlistment_model->validation_errors), 400);
        }
        // Update record
        else {
            $this->usertracking->track_this();
            $data = whitelist($this->post(), array('first_name', 'middle_name', 'last_name', 'age', 'country_id', 'timezone', 'game', 'ingame_name', 'steam_name', 'steam_id', 'experience', 'recruiter', 'comments'));
        
			$data['units'] = json_encode($this->post('units'));
			
			// Only use first letter of middle_name
			if(isset($data['middle_name']) && $data['middle_name']) $data['middle_name'] = substr($data['middle_name'], 0, 1);
		
            $result = $this->enlistment_model->save($enlistment_id, $data);
            $this->response(array('status' => $result ? true : false, 'enlistment' => nest($this->enlistment_model->get_by_id($enlistment_id))));
        }
    }
    
    /**
     * PROCESS
     */
    public function process_post($enlistment_id) {
        // Must have permission to process an enlistment
        if( ! $this->user->permission('enlistment_process_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Form validation
        else if($this->enlistment_model->run_validation('validation_rules_process') === FALSE) {
            $this->response(array('status' => false, 'error' => $this->enlistment_model->validation_errors), 400);
        }
        // Process enlistment
        else {
            $this->usertracking->track_this();

            // First, update enlistment record
            $whitelist = array('status', 'unit_id', 'recruiter_member_id');

            // Allow liaison if has permission
            if($this->user->permission('enlistment_assign_any')) {
                array_push($whitelist, 'liaison_member_id');
            }
            $data = whitelist($this->post(), $whitelist);
            
			if(isset($data['unit_id'])) $data['unit_id'] = $data['unit_id'] ? $data['unit_id'] : NULL; // Should be null if empty
			if(isset($data['recruiter_member_id'])) $data['recruiter_member_id'] = $data['recruiter_member_id'] ? $data['recruiter_member_id'] : NULL; // Should be null if empty
			if(isset($data['liaison_member_id'])) $data['liaison_member_id'] = $data['liaison_member_id'] ? $data['liaison_member_id'] : NULL; // Should be null if empty
            //if(isset($data['unit_id']) && ! $data['unit_id']) $data['unit_id'] = NULL; // Done in model
            
            $this->enlistment_model->save($enlistment_id, $data);
            $enlistment = nest($this->enlistment_model->get_by_id($enlistment_id));
            
            // Second, deal with assignment if unit_id specified
            if(isset($data['unit_id']) && $data['unit_id']) {
//                $assignment = $this->assignment_model->where('assignments.member_id', $enlistment['member_id'])->where('assignments.unit_id', $data['unit_id'])->where('assignments.end_date IS NULL', NULL, FALSE)->get()->row_array();
                $assignment = $this->assignment_model->where('assignments.member_id', $enlistment['member_id'])->where('assignments.start_date >= ', $enlistment['date'] )->where('assignments.end_date IS NULL', NULL, FALSE)->get()->row_array();
                // If accepted and already has an assignment we remove it to add later
                if($data['status'] == 'Accepted' && $assignment && ($assignment['unit_id'] != $enlistment['unit_id'])) {
                    $this->assignment_model->delete($assignment['id']);
                    $assignment = $this->assignment_model->where('assignments.member_id', $enlistment['member_id'])->where('assignments.unit_id', $data['unit_id'])->where('assignments.end_date IS NULL', NULL, FALSE)->get()->row_array();
                }
                // If accepted and no assignment exists, create one
                if($data['status'] == 'Accepted' && ! $assignment) {
                    $this->assignment_model->save(NULL, array(
                        'member_id' => $enlistment['member_id'],
                        'unit_id' => $data['unit_id'],
                        'start_date' => format_date('now', 'mysqldate')
                    ));
                }
                // If changing to AWOL status
                else if($data['status'] == 'AWOL' && $assignment) {
                    $assignment['end_date'] = format_date('now', 'mysqldate');
                    $this->assignment_model->save($assignment['id'], array('end_date' => format_date('now', 'mysqldate')));
                }
                // Otherwise, if not accepted and an assignment exists, delete it
                else if($data['status'] != 'Accepted' && $assignment) {
                    $this->assignment_model->delete($assignment['id']);
                }
            }
            // Update username
            $this->forums->update_display_name($enlistment['member_id']);

            $this->response(array('status' => false, 'enlistment' => $enlistment));
        }
    }
    
    /**
     * DELETE
     */
    public function view_delete($enlistment_id) {
        // Fetch record
        if( ! ($enlistment = nest($this->enlistment_model->select_member()->get_by_id($enlistment_id)))) {
            $this->response(array('status' => false, 'error' => 'Record not found'), 404);
        }
        // Must have permission to delete this type of record for any member
        else if( ! $this->user->permission('enlistment_delete_any')) {
            $this->response(array('status' => false, 'error' => 'Permission denied'), 403);
        }
        // Delete record
        else {
            $this->usertracking->track_this();
            $this->enlistment_model->delete($enlistment_id);
            $this->response(array('status' => true));
        }
    }
    
    public function check_restricted_names($cName)
    {
        if ( $this->is_it_a_restricted_name($cName) )
        {
            $this->form_validation->set_message('check_restricted_names', 'Last name ' . $cName . ' is restricted. Please choose different one.');
            return false;
        }
        else
        {
            return true;
        }
    }

    public function is_it_a_restricted_name( $cName, $nMemberId = 0 )
    {
        //if called from validate $nMemberId is 0 so it finds the correct one
        $cSql = "SELECT COUNT(1) AS cnt FROM `restricted_names` WHERE `name` ='$cName' AND member_id <> $nMemberId ";
        $res = $this->db->query( $cSql)->result_array();
        return ( $res[0]['cnt'] <> '0' );
    }

}