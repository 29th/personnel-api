<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title><?php echo $method; ?></title>
	<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.0.3/css/bootstrap.min.css">
<?php 
foreach($css_files as $file): ?>
	<link type="text/css" rel="stylesheet" href="<?php echo $file; ?>" />
<?php endforeach; ?>
<?php foreach($js_files as $file): ?>
	<script src="<?php echo $file; ?>"></script>
<?php endforeach; ?>
<style type='text/css'>
body
{
	font-family: Arial;
	font-size: 14px;
}
a {
    color: blue;
    text-decoration: none;
}
a:hover
{
	text-decoration: underline;
}
.nav { margin-bottom: 20px; }
</style>
</head>
<body>
    <div class="container">
    	<div class="row">
    	    <ul class="nav nav-pills">
    	        <li<?php if($method == 'abilities') echo ' class="active"'; ?>><a href="/admin/abilities">Abilities</a></li>
    	        <li<?php if($method == 'assignments') echo ' class="active"'; ?>><a href="/admin/assignments">Assignments</a></li>
    	        <li<?php if($method == 'attendance') echo ' class="active"'; ?>><a href="/admin/attendance">Attendance</a></li>
    	        <li<?php if($method == 'awardings') echo ' class="active"'; ?>><a href="/admin/awardings">Awardings</a></li>
    	        <li<?php if($method == 'awards') echo ' class="active"'; ?>><a href="/admin/awards">Awards</a></li>
    	        <li<?php if($method == 'class_permissions') echo ' class="active"'; ?>><a href="/admin/class_permissions">Class Permissions</a></li>
    	        <li<?php if($method == 'countries') echo ' class="active"'; ?>><a href="/admin/countries">Countries</a></li>
    	        <li<?php if($method == 'demerits') echo ' class="active"'; ?>><a href="/admin/demerits">Demerits</a></li>
    	        <li<?php if($method == 'discharges') echo ' class="active"'; ?>><a href="/admin/discharges">Discharges</a></li>
    	        <li<?php if($method == 'enlistments') echo ' class="active"'; ?>><a href="/admin/enlistments">Enlistments</a></li>
    	        <li<?php if($method == 'events') echo ' class="active"'; ?>><a href="/admin/events">Events</a></li>
    	        <li<?php if($method == 'finances') echo ' class="active"'; ?>><a href="/admin/finances">Finances</a></li>
    	        <li<?php if($method == 'loa') echo ' class="active"'; ?>><a href="/admin/loa">LOA</a></li>
    	        <li<?php if($method == 'members') echo ' class="active"'; ?>><a href="/admin/members">Members</a></li>
    	        <li<?php if($method == 'notes') echo ' class="active"'; ?>><a href="/admin/notes">Notes</a></li>
    	        <li<?php if($method == 'positions') echo ' class="active"'; ?>><a href="/admin/positions">Positions</a></li>
    	        <li<?php if($method == 'promotions') echo ' class="active"'; ?>><a href="/admin/promotions">Promotions</a></li>
    	        <li<?php if($method == 'qualifications') echo ' class="active"'; ?>><a href="/admin/qualifications">Qualifications</a></li>
    	        <li<?php if($method == 'ranks') echo ' class="active"'; ?>><a href="/admin/ranks">Ranks</a></li>
    	        <li<?php if($method == 'schedules') echo ' class="active"'; ?>><a href="/admin/schedules">Schedules</a></li>
    	        <li<?php if($method == 'servers') echo ' class="active"'; ?>><a href="/admin/servers">Servers</a></li>
    	        <li<?php if($method == 'standards') echo ' class="active"'; ?>><a href="/admin/standards">Standards</a></li>
    	        <li<?php if($method == 'units') echo ' class="active"'; ?>><a href="/admin/units">Units</a></li>
    	        <li<?php if($method == 'unit_permissions') echo ' class="active"'; ?>><a href="/admin/unit_permissions">Unit Permissions</a></li>
    	    </ul>
    	</div>
        <div class="row">
    		<?php echo $output; ?>
        </div>
    </div>
</body>
</html>