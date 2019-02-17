<h2>Options</h2>

<h2>Contracts Summary</h2>
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

foreach($cats as $k => $v) {
  if(empty($summary[$k])) {
    continue;
  } else {
  }
?>
<h3><?=$v?></h3>
<table class='view'>
  <tr>
   <th>Hash</th>
   <th>Address</th>
   <th>Type</th>
   <th>Date</th>
  </tr>
  <?php
  $its = 0;
#  echo '<pre>'.print_r($summary,1).'</pre>';
  foreach($summary[$k] as $h => $t) {
  ?>
    <tr>
     <td>
      <a href='?view_contract=<?=$h?>'>
       <b><?=$h?></b>
      </a>
     </td>
     <td><?=$t['request']['headers']['address'] . ($t['request']['headers']['address'] == $client->getAddress() ? " <span class='gray'>(You)</span>" : "") ?></td>
     <td>
      <?=$t['request']['action']?>
     </td>
     <td><?=$t['request']['headers']['timestamp']?></td>
    </tr>
  <?php
    $its++;
    if($its == 3) break;
  }
  if(count($summary[$k]) > 3) { ?>
    <li style='list-style-type:none;'><a href=#>View More</a> (<?=count($summary[$k])?>)</li>
  <?php } ?>
</table>
<?php } ?>


