<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Member_model extends MY_Model {
    public $table = 'members';
    public $primary_key = 'members.id';
    
	// Done by enlistment controller
	// array('last_name', 'first_name', 'middle_name', 'country_id')
    public function validation_rules_add() {
        return array(
            array(
                'field' => 'last_name'
                ,'rules' => 'required|max_length[32]'
            )
            ,array(
                'field' => 'first_name'
                ,'rules' => 'required|max_length[32]'
            )
            ,array(
                'field' => 'name_prefix'
                ,'rules' => 'max_length[8]'
            )
            ,array(
                'field' => 'country_id'
                ,'rules' => 'numeric'
            )
            ,array(
                'field' => 'rank_id'
                ,'rules' => 'numeric'
            )
            ,array(
                'field' => 'steam_id'
                ,'rules' => 'numeric_or_empty'
            )
        );
    }
    
	//array('last_name', 'first_name', 'middle_name', 'name_prefix', 'country_id', 'rank_id', 'steam_id', 'email')
    public function validation_rules_edit() {
        return array(
            array(
                'field' => 'last_name'
                ,'rules' => 'min_length[1]|max_length[32]'
            )
            ,array(
                'field' => 'first_name'
                ,'rules' => 'min_length[1]||max_length[32]'
            )
            ,array(
                'field' => 'name_prefix'
                ,'rules' => 'max_length[8]'
            )
            ,array(
                'field' => 'country_id'
                ,'rules' => 'numeric'
            )
            ,array(
                'field' => 'rank_id'
                ,'rules' => 'numeric'
            )
            ,array(
                'field' => 'steam_id'
                ,'rules' => 'numeric_or_empty'
            )
        );
    }
    
    public function default_select() {
        $this->db->select('members.id, members.last_name, members.first_name, members.middle_name, members.name_prefix, members.steam_id, members.city, members.forum_member_id, members.vanilla_forum_member_id')
            ->select($this->virtual_fields['short_name'] . ' AS short_name, ' . $this->virtual_fields['full_name'] . ' AS full_name', FALSE)
            ->select('members.rank_id AS `rank|id`, ranks.abbr AS `rank|abbr`, ranks.name AS `rank|name`, ranks.filename AS `rank|filename`')
            ->select('units.id AS `unit|id`, units.abbr AS `unit|abbr`, ' . $this->virtual_fields['unit_key'] . ' AS `unit|key`, units.name AS `unit|name`, ' . $this->virtual_fields['depth'] . ' AS `unit|depth`, units.path AS `unit|path` ', FALSE)
            ->select('positions.name AS `position|name`')
            ->select('(SELECT id FROM `enlistments` WHERE `enlistments`.`member_id` = `members`.`id` AND `status` = \'Pending\' ORDER BY id DESC LIMIT 1 ) AS currently_enlisting')
            ->select('countries.id AS `country|id`, countries.abbr AS `country|abbr`, countries.name AS `country|name`')
            ->select('(SELECT id FROM `eloas` WHERE `eloas`.`member_id` = `members`.`id` AND NOW() BETWEEN eloas.start_date AND eloas.end_date LIMIT 1 ) as eloa')
            ->select('(SELECT COUNT(1)>0 FROM `passes` WHERE `passes`.`member_id` = `members`.`id` AND NOW() BETWEEN passes.start_date AND passes.end_date  ) as pass');
    }
    
    public function default_join() {
        $this->db->join('ranks', 'ranks.id = members.rank_id')
            //->join('assignments', 'assignments.id = members.primary_assignment_id', 'left')
            ->join('assignments', 'assignments.member_id = members.id AND (assignments.start_date <= CURDATE()) AND (assignments.end_date > CURDATE() OR assignments.end_date IS NULL)', 'left')
            ->join('positions', 'positions.id = assignments.position_id', 'left')
            ->join('(SELECT * FROM units ORDER BY classification ASC, path) AS units', 'units.id = assignments.unit_id','left')
            ->join('countries', 'countries.id = members.country_id', 'left');
    }
    
    public function default_order_by() {
        $this->db//->order_by('ranks.order DESC')
            ->order_by('units.classification ASC, `unit|depth`, positions.order DESC, ranks.id DESC');
    }

    public function active() {
        $this->filter_where('assignments.id IS NOT NULL');
        return $this;
    }
    
    public function search_name( $pattern ) {
        $esc_str = $this->db->escape_like_str($pattern);
        $this->filter_where("(members.last_name LIKE '%$esc_str%' OR members.first_name LIKE '%$esc_str%' OR members.steam_id LIKE '%$esc_str%')");
        $this->order_by('units.active DESC, members.rank_id DESC, `unit|depth`, units.abbr, members.id DESC');
        return $this;
    }
    
    public function search_member_name_or_roid( $pattern, $roid ) {
        $esc_str = $this->db->escape_like_str($pattern);
        $this->filter_where("(members.last_name LIKE '%$esc_str%' OR members.steam_id = '$roid')");
        $this->order_by('units.active DESC, members.rank_id DESC, `unit|depth`, units.abbr, members.id DESC');
        return $this;
    }
    
    public function distinct_members() {
        $this->filter_group_by('members.id');
        return $this;
    }
}
