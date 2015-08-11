<?php

	include '../xml.php';
	
	$xml = new xml(__DIR__ . '/1.xml');
	var_dump($xml->data);

?>