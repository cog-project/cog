<?php
renderElement('head');

if(!empty($_POST)) {
	if(isset($_POST['create_party'])) {
		$client->createParty();
	}
	if(isset($_POST['initialize_network'])) {
		$client->register(
			$_POST['initialize_network']['database'],
			$_POST['initialize_network']['address'],
			$_POST['initialize_network']['public_key']
		);
	}

	if(isset($_POST['add_node'])) {
		$client->addNode($_POST['add_node']);
	}
	if(isset($_POST['ping'])) {
		$client->ping($_POST['ping']);
	}
	if(isset($_POST['sync'])) {
		$client->sync($_POST['sync']);
	}
	if(isset($_POST['remove_node'])) {
		$client->removeNode($_POST['remove_node']);
	}
}

if($client->hasParty()) {
	$isRegistered = $client->getIsRegistered();
	$summary = $client->getSummary(
		$client->getEnvironment(),
		$client->getAddress()
	) ? : [];
	renderElement('home',[
		'client' => $client,
		'isRegistered' => $isRegistered,
		'summary' => $summary,
	]);
	
} else {
	renderElement('login',[
		'client' => $client
	]);
}

renderElement('foot');
?>
