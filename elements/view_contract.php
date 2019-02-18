<h2>Contract Information</h2>
<?php if(!empty($data)) { ?>
<h3>Headers</h3>
<table class='view'>
 <tbody>
  <tr>
   <td>Hash</td>
   <td><?=$data['hash']?></td>
  </tr>
  <tr>
   <td>Version</td>
   <td><?=$data['request']['headers']['version']?></td>
  </tr>
  <tr>
   <td>PrevHash</td>
   <td>
     <?=($data['request']['headers']['prevHash'] == cog::generate_zero_hash()) ? "{$data['request']['headers']['prevHash']} <span class='gray'>(N/A)</span>" : "<a href='?view_contract={$data['request']['headers']['prevHash']}'>{$data['request']['headers']['prevHash']}</a>"?>
   </td>
  </tr>
  <tr>
   <td>TimeStamp</td>
   <td><?=$data['request']['headers']['timestamp']?></td>
  </tr>
  <tr>
   <td>Nonce</td>
   <td><?=$data['request']['headers']['counter']?></td>
  </tr>
  <tr>
   <td>Created By</td>
   <td><?=$data['request']['headers']['address'] . ($data['request']['headers']['address'] == $client->getAddress() ? " <span class='gray'>(You)</span>" : "")?></td>
  </tr>
 </tbody>
</table>
<h3>Data</h3>
<table class='view'>
 <tbody>
  <tr>
   <td>Action</td>
   <td><b><?=$data['request']['action']?></b></td>
  </tr>
  <tr>
   <td>Database</td>
   <td><?=$data['request']['params']['database']?></td>
  </tr>
  <tr>
   <td>Address</td>
   <td><?=$data['request']['params']['address'] . ($data['request']['headers']['address'] == $client->getAddress() ? " <span class='gray'>(You)</span>" : "")?></td>
  </tr>
  <tr>
   <td>Public Key</td>
   <td class='txt'><?=$data['request']['params']['public_key']?></td>
  </tr>
  <tr>
   <td>Signature</td>
   <td class='txt'><?=$data['request']['signature']?></td>
  </tr>
 </tbody>
</table>
<?php } else { ?>
<div class='warning'>
  Failed to find transaction with hash:<br/><?=$_GET['view_contract']?>
</div>
<?php } ?>