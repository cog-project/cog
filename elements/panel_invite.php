  <h2>Options</h2>
  <form action='client.php' method='post'>
    <?php if (!$client->getNumBlocks()) { ?>
    <h3>Initialize Network</h3>
    There are currently no transactions in this network.
    <div class='table subform'>
      <div>
        <div class='table-div'>
	  <input type='submit' value='Initialize'>
	  <input type='hidden' name='initialize_network[database]' value='<?=$client->getEnvironment()?>'>
	  <input type='hidden' name='initialize_network[address]' value='<?=$client->getAddress()?>'>
	  <input type='hidden' name='initialize_network[public_key]' value='<?=$client->getPublicKey()?>'>
	</div>
      </div>
    </div>
    <?php } ?>
    
    <h3>Create New Network</h3>
    <div class='table subform'>
      <div class='table-row'>
	<div class='table-div'>Database Name</div>
	<div class='table-div'>
	 <input type='text' name='new_database[database]' value='cog'>
	</div>
      </div>
      <div>
        <div class='table-div'><input type='submit'></div>
      </div>
    </div>
  </form>

