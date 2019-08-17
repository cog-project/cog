<?php
$creditTypes = [
  '' => '--- Select ---'
];
$creditTypes[$client->getAddress()] = "(You)";
?>

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
