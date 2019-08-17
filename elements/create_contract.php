<?php
$creditTypes = [
  '' => '--- Select ---'
];
$creditTypes[$client->getAddress()] = "(You)";
?>

    <h2>Create Contract</h2>

    <!-- Party-to-Party Agreement Clause -->
    <form>
    <table class='view small'>
      <tr>
        <th colspan=2>Party Agreement</th>
      </tr>
      <tr>
        <th>Sender</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Credit Type</th>
	<td>
	  <select>
          <?php foreach($creditTypes as $addr => $t) { ?>
	    <option value=<?=$addr?>><?=$t?></option>
          <?php } ?>
	  </select>
	</td>
      </tr>
      <tr>
        <th>Credit Amount</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Receiver</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Terms</th>
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

    <!-- Guarantor Agreement Clause -->
    <form>
    <table class='view small'>
      <tr>
        <th colspan=2>Guarantor Agreement</th>
      </tr>
      <tr>
        <th>Sender</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Credit Type</th>
	<td>
	  <select>
          <?php foreach($creditTypes as $addr => $t) { ?>
	    <option value=<?=$addr?>><?=$t?></option>
          <?php } ?>
	  </select>
	</td>
      </tr>
      <tr>
        <th>Credit Amount</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Guarantor</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Terms</th>
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

    <!-- Arbitrator Agreement Clause -->
    <form>
    <table class='view small'>
      <tr>
        <th colspan=2>Arbitrator Agreement</th>
      </tr>
      <tr>
        <th>Sender</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Credit Type</th>
	<td>
	  <select>
          <?php foreach($creditTypes as $addr => $t) { ?>
	    <option value=<?=$addr?>><?=$t?></option>
          <?php } ?>
	  </select>
	</td>
      </tr>
      <tr>
        <th>Credit Amount</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Arbitrator</th>
	<td>
	  <input type='text'>
	</td>
      </tr>
      <tr>
        <th>Terms</th>
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