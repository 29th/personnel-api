<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Recruits_model extends MY_Model 
{
    public $table = 'enlistments';
//    public $primary_key = 'enlistments.recruiter_member_id';
    
    public function default_select() 
    {
        $this->db->select("enlistments.member_id AS `member|id`")
            ->select("(SELECT abbr FROM ranks WHERE id = (SELECT new_rank_id FROM promotions WHERE member_id = enlistments.member_id ORDER BY date DESC LIMIT 1 ) )  AS `member|rank` ")
            ->select("mem1.`first_name`  AS `member|first_name` ")
            ->select("mem1.`middle_name` AS `member|middle_name` ")
            ->select("mem1.`last_name` AS `member|last_name` ")
            ->select("enlistments.id AS `enl|id` ")
            ->select("enlistments.date AS `enl|date` ")
            ->select("enlistments.recruiter_member_id AS `recruiter|recruiter_id` ")
            ->select("(SELECT abbr FROM ranks WHERE id = (SELECT new_rank_id FROM promotions WHERE member_id = enlistments.recruiter_member_id ORDER BY date DESC LIMIT 1 ) )  AS `recruiter|rank` ")
            ->select("mem2.last_name AS `recruiter|last_name` ")
            ->select("enlistments.status AS `enl|status` ")
            ->select("u1.abbr AS `tp|tp` ")
            ->select("u1.id AS `tp|id` ");
    }
    
    public function default_join() 
    {
        $this->db->join('units AS u1', 'u1.id = enlistments.unit_id', 'left')
            ->join('members AS mem1', 'mem1.id = enlistments.member_id', 'left')
            ->join('members AS mem2', 'mem2.id = enlistments.recruiter_member_id', 'left');
    }
    
    public function default_order_by() 
    {
        $this->db->order_by('u1.abbr DESC');
    }

    public function default_from() 
    {
        $this->db->order_by('enlistments');
    }

    public function default_where() 
    {
        $this->db->where('enlistments.status','Accepted');
    }
    
    public function by_member($member_id) 
    {
        $this->db->where('enlistments.recruiter_member_id', $member_id );
    }

    public function by_unit($unit_id) {
        $this->filter_join('assignments', 'assignments.member_id = ' . $this->table . '.recruiter_member_id');
        $this->filter_join('units', 'units.id = assignments.unit_id');

        if(is_numeric($unit_id)) {
            $this->filter_where('(units.id = ' . $unit_id . ' OR units.path LIKE "%/' . $unit_id . '/%")');
        } elseif($lookup = $this->getByUnitKey($unit_id)) {
            $this->filter_where('(units.id = ' . $lookup['id'] . ' OR (units.path LIKE "%/' . $lookup['id'] . '/%"))');
        }
        $this->filter_where('assignments.end_date IS NULL'); // Only include current members
        $this->filter_group_by($this->primary_key);
        return $this;
    }

    public function select_member() {}
}