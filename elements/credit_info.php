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
        <th>Message</th>
        <th>Last Transaction</th>
      </tr>
      <?php
      foreach($creditInfo as $transaction) {
        foreach($transaction['request']['params']['inputs'] as $input) {
	  if($input['to'] != $client->getAddress()) continue;
      ?>
      <tr>
        <td><?=$nodeNames[$input['from']] ? : 'N/A'?></td>
        <td><?=$input['from']?></td>
        <td>Credit</td>
        <td><?=$input['amount']?></td>
        <td><?=htmlentities($input['message'])?></td>
        <td><?=$transaction['request']['headers']['timestamp']?></td>
      </tr>
      <?php
        }
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
        <th>Message</th>
        <th>Last Transaction</th>
      </tr>
      <?php
      foreach($creditInfo as $transaction) {
        foreach($transaction['request']['params']['inputs'] as $input) {
	  if($input['from'] != $client->getAddress()) continue;
      ?>
      <tr>
        <td><?=$nodeNames[trim($input['to'])] ? : 'N/A'?></td>
        <td><?=$input['to']?></td>
        <td>Credit</td>
        <td><?=$input['amount']?></td>
        <td><?=htmlentities($input['message'])?></td>
        <td><?=$transaction['request']['headers']['timestamp']?></td>
      </tr>
      <?php
        }
      }
      ?>
    </table>
  </details>
