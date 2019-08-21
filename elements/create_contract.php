<?php
$creditTypes = [
  '' => '--- Select ---'
];
$creditTypes[$client->getAddress()] = "(You)";
?>

    <h2>Create Contract</h2>

    <!-- Party-to-Party Agreement Clause -->
    <form action='client.php?expand=credit' method=POST>
    <table class='view small'>
      <tr>
        <th colspan=2>Party Agreement</th>
      </tr>
      <tr>
        <th>Sender</th>
	<td>
	  <input type='text' name='contract[party][from]' value='<?=$client->getAddress()?>'>
	</td>
      </tr>
      <tr>
        <th>Credit Type</th>
	<td>
	  <select name='contract[party][type]'>
          <?php foreach($creditTypes as $addr => $t) { ?>
	    <option value=<?=$addr?>><?=$t?></option>
          <?php } ?>
	  </select>
	</td>
      </tr>
      <tr>
        <th>Credit Amount</th>
	<td>
	  <input type='text' name='contract[party][amount]'>
	</td>
      </tr>
      <tr>
        <th>Receiver</th>
	<td>
	  <input type='text' name='contract[party][to]'>
	</td>
      </tr>
      <tr>
        <th>Terms</th>
        <td>
	  <textarea type='text' name='contract[party][message]' rows=5 cols=35></textarea>
	</td>
      </tr>
    <!-- Guarantor Agreement Clause -->
      <tr>
        <th colspan=2>Guarantor Agreement</th>
      </tr>
      <tr>
        <th>Sender</th>
	<td>
	  <input type='text' name='contract[guarantor][from]' value='<?=$client->getAddress()?>'>
	</td>
      </tr>
      <tr>
        <th>Credit Type</th>
	<td>
	  <select name='contract[guarantor][type]'>
          <?php foreach($creditTypes as $addr => $t) { ?>
	    <option value=<?=$addr?>><?=$t?></option>
          <?php } ?>
	  </select>
	</td>
      </tr>
      <tr>
        <th>Credit Amount</th>
	<td>
	  <input type='text' name='contract[guarantor][amount]'>
	</td>
      </tr>
      <tr>
        <th>Guarantor</th>
	<td>
	  <input type='text' name='contract[guarantor][to]'>
	</td>
      </tr>
      <tr>
        <th>Terms</th>
        <td>
	  <textarea type='text' name='contract[guarantor][message]' rows=5 cols=35></textarea>
	</td>
      </tr>
    <!-- Arbitrator Agreement Clause -->
      <tr>
        <th colspan=2>Arbitrator Agreement</th>
      </tr>
      <tr>
        <th>Sender</th>
	<td>
	  <input type='text' name='contract[arbitrator][from]' value='<?=$client->getAddress()?>'>
	</td>
      </tr>
      <tr>
        <th>Credit Type</th>
	<td>
	  <select name='contract[arbitrator][type]'>
          <?php foreach($creditTypes as $addr => $t) { ?>
	    <option value=<?=$addr?>><?=$t?></option>
          <?php } ?>
	  </select>
	</td>
      </tr>
      <tr>
        <th>Credit Amount</th>
	<td>
	  <input type='text' name='contract[arbitrator][amount]'>
	</td>
      </tr>
      <tr>
        <th>Arbitrator</th>
	<td>
	  <input type='text' name='contract[arbitrator][to]'>
	</td>
      </tr>
      <tr>
        <th>Terms</th>
        <td>
	  <textarea type='text' name='contract[arbitrator][message]' rows=5 cols=35></textarea>
	</td>
      </tr>
      <tr>
	<th colspan=2>
	  <input type='submit' value='Send'>
	</th>
      </tr>
    </table>
    </form>
