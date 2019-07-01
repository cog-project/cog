<?php
$creditTypes = [
  '' => '--- Select ---'
];
$creditTypes[$client->getAddress()] = "(You)";
$nodes = $client->listNodes();
$nodeNames = [];
foreach($nodes as $node) {
  $nodeNames[$node['address']] = $node['nickname'];
}

# TODO modularize credit summary generation
# TODO this really should be in mongo...

$creditSummary = [];
$agg = [];

foreach($creditInfo as $transaction) {
  foreach($transaction['request']['params']['inputs'] as $input) {
    $agg[] = $input + ['timestamp' => $transaction['request']['headers']['timestamp']];
  }
}

uasort($agg,function($a,$b) {
  # -1 first for asc; 1 first for desc
  if(strtotime($a['timestamp'] == strtotime($b['timestamp']))) {
    return 0;
  } elseif (strtotime($a['timestamp'] < strtotime($b['timestamp']))) {
    return 1;
  } else {
    return -1;
  }
});

foreach($agg as $input) {
  $other = null;
  if ($input['to'] == $client->getAddress()) {
    $other = $input['from'];
    $amt = $input['amount'];
  } elseif ($input['from'] == $client->getAddress()) {
    $other = $input['to'];
    $amt = -$input['amount'];
  } elseif ($input['from'] == $input['to']) {
    continue; // why
  }
  if(!isset($creditSummary[$other])) {
    $creditSummary[$other] = $input;
    $creditSummary[$other]['amount'] = $amt;
    unset($creditSummary['to']);
    unset($creditSummary['from']);
  } else {
    $creditSummary[$other]['amount'] += $amt;
    if(!empty($input['message'])) {
      $creditSummary[$other]['message'] = $input['message'];
    }
    $creditSummary[$other]['timestamp'] = $input['timestamp'];
  }
}

# TODO the send form is going to get too wide, please break it into rows
?>
  <details <?=$_GET['expand'] == 'credit' ? 'open' : ''?>>
    <summary>
      <h2 style='display:inline;'>Credit Summary</h2>
    </summary>
    <h2>Send Credits</h2>
    <form action='client.php?expand=credit' method='POST'>
    <table class='view small'>
      <tr>
        <th>Sender</th>
        <th>Recipient</th>
      </tr>
      <tr>
	<td><input type='hidden' name='send[from]' value='<?=$client->getAddress()?>'>(You)</td>
	<td><input type='text' name='send[to]'></td>
      </tr>
      <tr>
        <th>Type</th>
        <th>Amount</th>
      </tr>
      <tr>
        <td>
	  <select>
          <?php foreach($creditTypes as $addr => $t) { ?>
	    <option value=<?=$addr?>><?=$t?></option>
          <?php } ?>
	  </select>
	</td>
	<td><input type='text' name='send[amount]'></td>
      </tr>
      <tr>
        <th>Message</th>
        <td>
	  <textarea type='text' name='send[message]' rows=2 cols=25></textarea>
	</td>
      </tr>
      <tr>
	<th colspan=2>
	  <input type='submit' value='Send'>
	</th>
      </tr>
    </table>
    </form>
    <h2>Available Credits</h2>
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
      foreach($creditSummary as $addr => $data) {
	if($data['amount'] <= 0) continue;
      ?>
      <tr>
        <td>
	    <b><?=$nodeNames[$addr] ? : 'N/A'?></b>
	</td>
        <td>
	  <a href='?transaction_history=<?=$addr?>'>
          <?=$addr?>
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
    <h2>Outstanding Debts</h2>
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
      foreach($creditSummary as $addr => $data) {
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
  </details>
