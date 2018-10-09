<h1>Control Panel</h1>
<div class='main'>
  <h2>Address</h2>
  <b><?=$client->getAddress()?></b>
  
  <h2>Options</h2>
  <?php if(!$isRegistered) {
    renderElement('panel_invite');
  } else {
    renderElement('panel_main');
  } ?>
</div>
