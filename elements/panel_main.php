<b>Invites:</b> 0
<?php
$cats = [
	'messages' => 'Messages',
	'disputed' => 'Disputed Contracts',
	'outstanding' => 'Outstanding Contracts',
	'requests' => 'Contract Requests',
	'pending' => 'Pending Contracts',
	'active' => 'Active Contracts',
	'completed' => 'Completed Contracts',
];

foreach($cats as $k => $v) { ?>
<h3><?=$v?></h3>
<ul>
  <li>Blah</li>
  <li>Blah</li>
  <li>Blah</li>
  <li style='list-style-type:none;'><a href=#>View More</a> (0)</li>
</ul>
<?php } ?>


