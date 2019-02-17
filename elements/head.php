<?php
if(class_exists('session')) {
	session::$vars['time_start'] = microtime(true);
}
?>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel='stylesheet' href='master.css?t=<?=microtime(true)?>' media="all">
<script type='text/javascript' src='lib/js/jquery-3.3.1.min.js'></script>
<script>
$(document).ready(function() {
  $('#install_update').click(function(e) {
    e.preventDefault();
    $.getJSON('update.php').done(function(data){
      if(data.result) {
        $('#update_message').html('<p class="warning">Successfully installed updates!</p>');
      } else {
        $('#update_message').html('<p class="warning">Error:<div class="txt">'+data.message+'</div></p>');        
      }
    });
  });
});
</script>
</head>
<body>
<div class='head'>
  <a href='client.php'>
    <img src='cog.png' style='height:75px'>
  </a>
</div>
