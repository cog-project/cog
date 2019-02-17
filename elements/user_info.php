  <details>
    <summary>
      <h2 style='display:inline;'>User Info</h2>
    </summary>
    
    <h2>Address</h2>
    <div class='txt'><b><?=$client->getAddress()?></b></div>

    <h2>Public Key</h2>
    <div class='txt'><?=$client->getPublicKey()?></div>

    <h2>Software Version</h2>
    <p><b>v<?=cog::$version?></b></p>
<?php
$changed = strlen(trim(shell_exec("git diff github/master")));
if($changed) {
?>
   <p class='warning' id='update_message'>
     Updates have been detected.  <a href='#' id='install_update'>Install</a>
   </p>
<?php } ?>

  </details>

