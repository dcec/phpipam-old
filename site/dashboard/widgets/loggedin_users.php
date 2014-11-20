<?php

/* required functions */
if(!function_exists('getSubnetStatsDashboard')) {
require_once( dirname(__FILE__) . '/../../../functions/functions.php' );
}

$LoggedinUser = getLoggedInUser();

print "<table class='table table-condensed table-hover table-top'>";

# headers
print "<tr>";
print "	<th>"._('Username')."</th>";
print "	<th>"._('IP')."</th>";
print "	<th>"._('Date')."</th>";
print "</tr>";

# logs
foreach($LoggedinUser as $user) {
	
	print "<tr>";
	print "	<td>$user[username]</td>";
	print "	<td>$user[ipaddr]</td>";
	print "	<td>$user[date]</td>";

	print "</tr>";
}

print "</table>";

# print if none
if(sizeof($LoggedinUser) == 0) {
	print "<blockquote style='margin-top:20px;margin-left:20px;'>";
	print "<p>"._("No users connected")."</p>";
	print "</blockquote>";
}
?>