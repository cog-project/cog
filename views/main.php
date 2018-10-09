<?php
renderElement('head');

if(!empty($_POST)) {
	if(isset($_POST['create_party'])) {
		$client->createParty();
	}
}

if($client->hasParty()) {
	$isRegistered = $client->getIsRegistered();
	renderElement('home',[
		'client' => $client,
		'isRegistered' => $isRegistered,
	]);
	
} else {
	renderElement('login',[
		'client' => $client
	]);
}

renderElement('foot');
?>