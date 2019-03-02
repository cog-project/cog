  <details <?=$_GET['expand'] == 'network' ? 'open' : ''?>>
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
    <h3>Node Configuration</h3>
    <table class='view small'>
      <!-- Localhost -->
      <tr>
        <th>Name</th>
        <th>IP</th>
        <th>Address</th>
        <th>Last Request</th>
        <th>Local Time</th>
        <th>Options</th>
      </tr>
      <?php foreach($client->listNodes() as $node) { ?>
      <tr>
        <td><?=$node['nickname'] ? : "<span class='gray'>N/A</span>"?></td>
        <td><?=$node['ip_address'] . ":" . ($node['ip_port'] ? : 80)?></td>
        <td><?=$node['address'] ? : "<span class='gray'>N/A</span>"?></td>
        <td><?=$node['ping_datetime'] ? : "<span class='gray'>N/A</span>"?></td>
        <td><?=$node['local_datetime'] ? : "<span class='gray'>N/A</span>"?></td>
        <td>

          <form action='client.php?expand=network' method='POST'>
            <input type='hidden' name='ping[ip_address]' value='<?=$node['ip_address']?>'>
            <input type='hidden' name='ping[ip_port]' value='<?=$node['ip_port']?>'>
            <input type='submit' value='Ping'>
          </form>

          <form action='client.php?expand=network' method='POST'>
            <input type='hidden' name='remove_node[ip_address]' value='<?=$node['ip_address']?>'>
            <input type='hidden' name='remove_node[ip_port]' value='<?=$node['ip_port']?>'>
            <input type='submit' value='Remove'>
          </form>

          <form action='client.php?expand=network' method='POST'>
            <input type='hidden' name='sync[ip_address]' value='<?=$node['ip_address']?>'>
            <input type='hidden' name='sync[ip_port]' value='<?=$node['ip_port']?>'>
            <input type='submit' value='Sync'>
          </form>
<!--
            <input type='hidden' name='blacklist_node[ip_address]' value='<?=$node['ip_address']?>'>
            <input type='hidden' name='blacklist_node[ip_port]' value='<?=$node['ip_port']?>'>
            <input type='submit' value='Blacklist'>
-->
          </form>
        </td>
      </tr>
      <?php } ?>
      <tr>
        <td colspan=6>
          <?php $config = $client->getConfig(); ?>
          <h3>
	    Configure Local Node
	    <?php if(!empty($config['ip_address']) && !empty($config['ip_port'])) { ?>
	      <span class='green'>(Active)</span>
            <?php } else { ?>
	      (Inactive)
	    <?php } ?>
	  </h3>
          <form action='client.php?expand=network' method='POST'>
            <input type='text' name='config[ip_address]' placeholder='IP Address' value='<?=$config['ip_address'] ? : ""?>'>
            <input type='text' name='config[ip_port]' placeholder='Port' size=5  value='<?=$config['ip_port'] ? : ""?>'>
            <input type='text' name='config[nickname]' placeholder='Nickname (Optional)' size=15 value='<?=$config['nickname'] ? : ""?>'>

	    <input type='hidden' name='config[public_key]' value='<?=$client->getPublicKey()?>'>
	    <input type='hidden' name='config[address]' value='<?=$client->getAddress()?>'>
            <input type='submit' value='Update'>
          </form>
          <h3>Add or Update Remote Node</h3>
          <form action='client.php?expand=network' method='POST'>
            <input type='text' name='add_node[ip_address]' placeholder='IP Address'>
            <input type='text' name='add_node[ip_port]' placeholder='Port' size=5>
            <input type='text' name='add_node[nickname]' placeholder='Nickname (Optional)' size=15>
            <input type='submit' value='Add'>
          </form>
        </td>
      </tr>
    </table>

  </details>
