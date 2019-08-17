<?php
$nodes = $client->listNodes();
$nodeNames = [];
foreach($nodes as $node) {
  if(isset($node['nickname']) && strlen($node['nickname'])) {
    $nodeNames[$node['address']] = $node['nickname'];
  }
}

# TODO modularize credit summary generation
# TODO this really should be in mongo...

$creditSummary = $creditInfo;
# TODO the send form is going to get too wide, please break it into rows
?>
  <details <?=$_GET['expand'] == 'credit' ? 'open' : ''?>>
    <summary>
      <h2 style='display:inline;'>Credit Summary</h2>
    </summary>
        
    <?php renderElement('send_credits',[
      'client' => $client,
    ]); ?>

    <?php renderElement('create_contract',[
      'client' => $client,
    ]); ?>
    
    <?php
    $credits = [];
    $debts = [];
    foreach($creditSummary as $addr => $data) {
      if($data['amount'] > 0) $credits[$addr] = $data;
      elseif($data['amount'] < 0) $debts[$addr] = $data;
    }
    ?>
    <h2>Available Credits</h2>
    <?php if(empty($credits)) { ?>
      <i>There are currently no credits available.</i>
    <?php } else { ?>
    <table class='view small'>
      <tr>
        <th>Nickname</th>
        <th>Address</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Last Message</th>
        <th>Last Transaction</th>
      </tr>
      <?php
      foreach($credits as $addr => $data) {
	if($data['amount'] <= 0) continue;
      ?>
      <tr>
        <td>
	  <?=$nodeNames[$addr] ? : 'N/A'?>
	</td>
        <td>
	  <a href='?transaction_history=<?=$addr?>'>
            <b><?=$addr?></b>
	  </a>
        </td>
        <td>Credit</td>
        <td><?=abs($data['amount'])?></td>
        <td><?=htmlentities($data['message'])?></td>
        <td><?=$data['timestamp']?></td>
      </tr>
      <?php
      }
      ?>
    </table>
    <?php } ?>
    <h2>Outstanding Debts</h2>
    <?php if(empty($debts)) { ?>
      <i>There are currently no outstanding debts on record.</i>
    <?php } else { ?>
    <table class='view small'>
      <tr>
        <th>Nickname</th>
        <th>Address</th>
        <th>Type</th>
        <th>Amount</th>
        <th>Last Message</th>
        <th>Last Transaction</th>
      </tr>
      <?php
      foreach($debts as $addr => $data) {
	if($data['amount'] >= 0) continue;
      ?>
      <tr>
        <td>
	  <?=$nodeNames[$addr] ? : 'N/A'?>
	</td>
        <td>
	  <a href='?transaction_history=<?=$addr?>'>
	    <b><?=$addr?></b>
	  </a>
	</td>
        <td>Credit</td>
        <td><?=abs($data['amount'])?></td>
        <td><?=htmlentities($data['message'])?></td>
        <td><?=$data['timestamp']?></td>
      </tr>
      <?php
      }
      ?>
    </table>
    <?php } ?>
  </details>
