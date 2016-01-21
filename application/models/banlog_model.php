<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Banlog_model extends MY_Model {
    public $table = 'banlog';
    public $primary_key = 'banlog.id';
    
    public function default_select() {
        $this->db->select('SQL_CALC_FOUND_ROWS banlog.*', FALSE);
    }
    
    public function default_join() {
    }
    
    public function default_order_by() {
        $this->db->order_by('banlog.id DESC');
    }

    public function select_member() {
        $this->filter_select('banlog.id_admin AS `admin|id`');
        $this->filter_select($this->virtual_fields['short_name'] . ' AS `admin|short_name`', FALSE);
        $this->filter_select('banlog.id_poster AS `poster|id`');
        $this->filter_select('CONCAT(p_ranks.abbr," ",p_members.last_name) AS `poster|short_name`', FALSE);
        $this->filter_join('members', 'members.id = banlog.id_admin','left');
        $this->filter_join('ranks', 'ranks.id = members.rank_id','left');
        $this->filter_join('members AS p_members', 'p_members.id = banlog.id_poster','left');
        $this->filter_join('ranks AS p_ranks', 'p_ranks.id = members.rank_id','left');
        return $this;
    }
}