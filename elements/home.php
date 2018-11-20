<h1>Control Panel</h1>
<div class='main'>
  <h2>Address</h2>
  <b><?=$client->getAddress()?></b>

  <h2>Network</h2>
  <?php
  $env = $client->getEnvironment();
  echo "<b>$env</b>".($env != 'cog' ? " <span class='gray'>(Testnet)</span>":" <span class='gray'>(Live)</span>");
  ?>
  
  <h2>Options</h2>
  <?php if(!$isRegistered) {
    renderElement('panel_invite',['client'=>$client]);
  } else {
    renderElement('panel_main',['summary' => $summary]);
  } ?>
</div>
