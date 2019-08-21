<?php
cog::emit($creditInfo);
# TODO this REALLY should be in mongo...
uasort($creditInfo,function($a,$b) {
  if (strtotime($a['request']['headers']['timestamp']) == strtotime($b['request']['headers']['timestamp'])) {
    return 0;
  } elseif (strtotime($a['request']['headers']['timestamp']) > strtotime($b['request']['headers']['timestamp'])) {
    return 1;
  } else {
    return -1;
  }
});
?>
<h2>Mutual Transaction History</h2>
<h3>User Details</h3>

<table class='view small'>
  <tbody>
<!--
    <tr>
      <th>Nickname</th>
      <td>-</td>
    </tr>
-->
    <tr>
      <th>Address</th>
      <td><?=$_GET['transaction_history']?></td>
    </tr>
<!--
    <tr>
      <th>Public Key</th>
      <td>-</td>
    </tr>
-->
  </tbody>
</table>
<h3>Transactions</h3>
<table class='view small'>
  <thead>
    <tr>
      <th>Hash</th>
      <th>Amount</th>
      <th>Message</th>
      <th>Balance</th>
      <th>Timestamp</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $render = '';
  $balance = 0;
  foreach($creditInfo as $t) {
    foreach($t['request']['params']['inputs'] as $i) {
      $amt = ($i['from'] == $client->getAddress() ? '-1' : '1') * (float)$i['amount'];
      $balance += $amt;
      $render = "
    <tr>
      <td>
        <a href='?view_contract={$t['hash']}'>
          {$t['hash']}
	</a>
      </td>
      <td>{$amt}</td>
      <td>{$i['message']}</td>
      <td>{$balance}</td>
      <td>{$t['request']['headers']['timestamp']}</td>
    </tr>
  " . $render;
    }
  }
  echo $render;
  ?>
  </tbody>
</table>
