<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Attendance_model extends MY_Model {
    public $table = 'attendance';
    public $primary_key = 'attendance.id';
    
    public function default_select() {
        $this->db->select('SQL_CALC_FOUND_ROWS attendance.attended, attendance.excused', FALSE); // SQL_CALC_FOUND_ROWS allows a COUNT after the query
    }
    
    public function by_event($event_id) {
        $this->filter_select('attendance.member_id AS `member|id`, ' . $this->virtual_fields['short_name'] . ' AS `member|short_name`', FALSE);
        $this->filter_join('members', 'members.id = attendance.member_id');
        $this->filter_join('ranks', 'ranks.id = members.rank_id');
        $this->filter_where('attendance.event_id', $event_id);
        $this->filter_where('(attendance.attended IS NOT NULL OR attendance.excused = 1)');
        $this->filter_order_by('ranks.order DESC');
        return $this;
    }
    
    public function by_member($member_id) {
        $this->filter_select('events.id AS `event|id`, events.datetime AS `event|datetime`, events.type AS `event|type`, events.mandatory AS `event|mandatory`');
        $this->filter_select('events.unit_id AS `unit|id`, units.abbr AS `unit|abbr`, ' . $this->virtual_fields['unit_key'] . ' AS `unit|key`', FALSE);
        $this->filter_join('events', 'events.id = attendance.event_id');
        $this->filter_join('units', 'units.id = events.unit_id');
        $this->filter_where('attendance.member_id', $member_id);
        $this->filter_where('events.reporter_member_id IS NOT NULL');
        $this->filter_order_by('events.datetime DESC');
        return $this;
    }
    
    // I could do select * from assignments where member_id IN (list of member IDs) group by event_id....but what if they've since been transferred...nevermind
    public function by_unit($unit_id) {
        $this->filter_select('events.id AS `event|id`, events.datetime AS `event|datetime`, events.type AS `event|type`, events.mandatory AS `event|mandatory`');
        $this->filter_select('events.unit_id AS `unit|id`, units.abbr AS `unit|abbr`, ' . $this->virtual_fields['unit_key'] . ' AS `unit|key`', FALSE);
        $this->filter_select('SUM(attendance.attended) AS attended, COUNT(attendance.attended) - SUM(attendance.attended) AS absent, SUM(IF(attendance.attended = 0, IF(attendance.excused = 1, 1, 0), 0)) AS excused', FALSE);
        $this->filter_join('events', 'events.id = attendance.event_id');
        $this->filter_join('units', 'units.id = events.unit_id');
        $this->filter_where('(units.id = ' . $unit_id . ' OR units.path LIKE "%/' . $unit_id . '/%")');
        $this->filter_where('events.reporter_member_id IS NOT NULL');
        $this->filter_group_by('attendance.event_id');
        $this->filter_order_by('events.datetime DESC');
        return $this;
    }
    
    public function by_date($start = FALSE, $end = FALSE) {        
        if($start !== FALSE && $end !== FALSE) {
            $start = format_date($start, 'mysqldate');
            $end = format_date($end, 'mysqldate');
            $this->filter_where('events.datetime >=', $start)
                ->filter_where('events.datetime <=', $end);
        } else {
            $this->filter_where('MONTH(events.datetime) = MONTH(NOW())')
                ->filter_where('YEAR(events.datetime) = YEAR(NOW())');
        }
        return $this;
    }
    
    public function awols($member_id, $days = 30) {
        $this->filter_select('attendance.member_id AS `member|id`, ' . $this->virtual_fields['short_name'] . ' AS `member|short_name`', FALSE);
        $this->filter_select('events.id AS `event|id`, events.datetime AS `event|datetime`, DATE(events.datetime) AS `event|date`, events.type AS `event|type`');
        $this->filter_join('events', 'events.id = attendance.event_id');
        $this->filter_join('members', 'members.id = attendance.member_id');
        $this->filter_join('ranks', 'ranks.id = members.rank_id');
        if(is_array($member_id)) {
            $this->filter_where_in('attendance.member_id', $member_id);
        } else {
            $this->filter_where('attendance.member_id', $member_id);
        }
        $this->filter_where('attendance.attended', 0);
        $this->filter_where('attendance.excused', 0);
        $this->filter_where('events.datetime >= DATE_SUB(NOW(), INTERVAL ' . (int) $days . ' DAY)');
        $this->filter_where('events.datetime < DATE_SUB(NOW(), INTERVAL 24 HOUR)'); // Not considered AWOL until 24 hours after the event
        $this->filter_where('events.mandatory', 1);
        $this->filter_order_by('events.datetime');
        //$this->filter_group_by('`member|id`, `event|date`'); // Add this to limit AWOLs to one per day
        return $this;
    }
    
    public function unit_awols($unit_id, $days = 30) {
        $this->filter_select('attendance.member_id AS `member|id`, ' . $this->virtual_fields['short_name'] . ' AS `member|short_name`', FALSE);
        $this->filter_select('events.id AS `event|id`, events.datetime AS `event|datetime`, DATE(events.datetime) AS `event|date`, events.type AS `event|type`');
        $this->filter_join('events', 'events.id = attendance.event_id');
        $this->filter_join('members', 'members.id = attendance.member_id');
        $this->filter_join('ranks', 'ranks.id = members.rank_id');
        $this->filter_join('units', 'units.id = events.unit_id');
        $this->filter_join('assignments', 'assignments.member_id = attendance.member_id');
        $this->filter_join('units AS assignmentUnits', 'assignmentUnits.id = assignments.unit_id');
        $this->filter_where('attendance.attended', 0);
        $this->filter_where('attendance.excused', 0);
        $this->filter_where('events.datetime >= DATE_SUB(NOW(), INTERVAL ' . (int) $days . ' DAY)');
        $this->filter_where('events.datetime < DATE_SUB(NOW(), INTERVAL 24 HOUR)'); // Not considered AWOL until 24 hours after the event
        $this->filter_where('events.mandatory', 1);
        $this->filter_where('(units.id = ' . $unit_id . ' OR units.path LIKE "%/' . $unit_id . '/%")');
        $this->filter_where('(assignmentUnits.id = ' . $unit_id . ' OR assignmentUnits.path LIKE "%/' . $unit_id . '/%")');
        $this->filter_where('assignments.end_date IS NULL'); // Only include current members
        $this->filter_order_by('events.datetime');
        //$this->filter_group_by('`member|id`, `event|date`'); // Add this to limit AWOLs to one per day
        return $this;
    }
    
    public function member_awols($member_id, $days = 30) {
        $this->filter_select('events.id, events.datetime, DATE(events.datetime) AS date, events.type');
        $this->filter_join('events', 'events.id = attendance.event_id');
        $this->filter_where('attendance.member_id', $member_id);
        $this->filter_where('attendance.attended', 0);
        $this->filter_where('attendance.excused', 0);
        $this->filter_where('events.datetime >= DATE_SUB(NOW(), INTERVAL ' . (int) $days . ' DAY)');
        $this->filter_where('events.datetime < DATE_SUB(NOW(), INTERVAL 24 HOUR)'); // Not considered AWOL until 24 hours after the event
        $this->filter_where('events.mandatory', 1);
        $this->filter_order_by('events.datetime');
        //$this->filter_group_by('date'); // Add this to limit AWOLs to one per day
        return $this;
    }
    
    public function set_attendance($event_id, $data, $attended = TRUE) {
        $inserts = array();
        foreach($data as $member_id) {
            array_push($inserts, '(' . $this->db->escape($event_id) . ', ' . $this->db->escape($member_id) . ', ' . ($attended ? 1 : 0) . ')');
        }
        return $this->db->query('INSERT INTO attendance (event_id, member_id, attended) VALUES ' . implode(',', $inserts) . ' ON DUPLICATE KEY UPDATE attended = ' . ($attended ? 1 : 0));
    }
    
    public function set_excused($event_id, $data, $excused = TRUE) {
        $inserts = array();
        foreach($data as $member_id) {
            array_push($inserts, '(' . $this->db->escape($event_id) . ', ' . $this->db->escape($member_id) . ', ' . ($excused ? 1 : 0) . ')');
        }
        return $this->db->query('INSERT INTO attendance (event_id, member_id, excused) VALUES ' . implode(',', $inserts) . ' ON DUPLICATE KEY UPDATE excused = ' . ($excused ? 1 : 0));
    }
    
    public function total_rows() {
        return $this->db->count_all_results();
    }
}