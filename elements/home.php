<h1>Control Panel</h1>
<div class='main'>
<!--
  <div class='headlinks'>
    <a href='client.php'>Home</a>
  </div>
-->
  <details>
    <summary>
      <h2 style='display:inline;'>User Info</h2>
    </summary>
    
    <h2>Address</h2>
    <div class='txt'><b><?=$client->getAddress()?></b></div>

    <h2>Public Key</h2>
    <div class='txt'><?=$client->getPublicKey()?></div>
  </details>

  <details>
    <summary>
      <h2 style='display:inline;'>Network Info</h2>
    </summary>

    <h2>Network</h2>
  <?php
  $env = $client->getEnvironment();
  echo "<b>$env</b>".($env != 'cog' ? " <span class='gray'>(Testnet)</span>":" <span class='gray'>(Live)</span>");
  ?>

    <h2>Nodes</h2>
    <!-- TODO responsive -->
    <table class='view small'>
      <tr>
        <th>Nickname</th>
        <th>IP</th>
        <th>Address</th>
        <th>Last Request</th>
        <th>Local Time</th>
        <th>Options</th>
      </tr>
      <!-- Localhost -->
      <tr>
        <td><span class='gray'>(You)</span></td>
        <td>localhost:80</td>
        <td><?=$client->getAddress()?></td>
        <td><?=cog::get_timestamp()?></td>
        <td><?=cog::get_timestamp()?></td>
        <td>
          <form action='client.php' method='POST'>
            <span class='gray'>N/A</span>
          </form>
        </td>
      </tr>
      <?php foreach($client->listNodes() as $node) { ?>
      <tr>
        <td><?=$node['nickname'] ? : "<span class='gray'>N/A</span>"?></td>
        <td><?=$node['ip_address'] . ":" . ($node['ip_port'] ? : 80)?></td>
        <td><?=$node['address'] ? : "<span class='gray'>N/A</span>"?></td>
        <td><?=$node['ping_datetime'] ? : "<span class='gray'>N/A</span>"?></td>
        <td><?=$node['local_datetime'] ? : "<span class='gray'>N/A</span>"?></td>
        <td>
          <form action='client.php' method='POST'>
            <input type='hidden' name='ping[ip_address]' value='<?=$node['ip_address']?>'>
            <input type='hidden' name='ping[ip_port]' value='<?=$node['ip_port']?>'>
            <input type='submit' value='Ping'>
          </form>
        </td>
      </tr>
      <?php } ?>
      <tr>
        <td colspan=6>
          <h3>Add or Update Node</h3>
          <form action='client.php' method='post'>
            <input type='text' name='add_node[ip_address]' placeholder='IP Address'>
            <input type='text' name='add_node[ip_port]' placeholder='Port'>
            <input type='text' name='add_node[nickname]' placeholder='Nickname'>
            <input type='submit' value='Add'>
          </form>
        </td>
      </tr>
    </table>

  </details>

  <div class='details'>
  <?php if(!$isRegistered) {
    renderElement('panel_invite',['client'=>$client]);
  } else {
    if(isset($_GET['view_contract'])) {
      renderElement(
        'view_contract',
	[
	  'hash' => $_GET['view_contract'],
	  'data' => $client->getTransaction($env, $_GET['view_contract']),
	  'client' => $client
	]
      );
    } else {
      renderElement('panel_main',['summary' => $summary,'client'=>$client]);
    }
  } ?>
  </div>
</div>
