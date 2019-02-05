<?php
if(class_exists('session')) {
	session::$vars['time_start'] = microtime(true);
}
?>

<head>
<meta name="viewport" content="width=device-width, initial-scale=1" />
<link rel='stylesheet' href='master.css?t=<?=microtime(true)?>' media="all">
</head>
<body>
<div class='head'>
  <a href='client.php'>
    <img src='cog.png' style='height:75px'>
  </a>
</div>
