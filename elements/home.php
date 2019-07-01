<h1>Control Panel</h1>
<div class='main'>
<!--
  <div class='headlinks'>
    <a href='client.php'>Home</a>
  </div>
-->
  <?php renderElement('user_info',['client' => $client]); ?>
  <?php renderElement('network_info',['client' => $client]); ?>
  <?php renderElement('credit_info',['client' => $client, 'creditInfo' => $creditInfo]); ?>

  <div class='details'>
  <?php
    if(isset($_GET['view_contract'])) {
      renderElement(
        'view_contract',
	[
	  'hash' => $_GET['view_contract'],
	  'data' => $client->getTransaction($env, $_GET['view_contract']),
	  'client' => $client
	]
      );
    } elseif(isset($_GET['transaction_history'])) {
      renderElement(
        'transaction_history',
	[
	  'addr' => $_GET['transaction_history'],
	  'client' => $client,
	  'creditInfo' => $creditInfo,
	]
      );
    } else {
      renderElement('panel_main',['summary' => $summary,'client'=>$client]);
    }
  ?>
  </div>
</div>
