<?php

	require '../xml.php';
	
	function convertSimpleXmlObjToArr($obj, &$arr)
	{
		foreach($obj->children() as $elementName => $node)
		{
			$nextIdx = count($arr);
			$arr[$nextIdx] = [
				'@name'			=> strtolower((string)$elementName),
				'@attributes'	=> [],
				'@children'		=> [],
				'@text'			=> trim((string)$node)
			];
			foreach($node->attributes() as $attributeName => $attributeValue)
			{
				$arr[$nextIdx]['@attributes'][strtolower(
					trim((string)$attributeName)
				)] = trim((string)$attributeValue);
			}
			convertSimpleXmlObjToArr($node, $arr[$nextIdx]['@children']);
		}
	}
	
	$path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
	foreach(
		[
			'simplexml'	=> [
				'100'	=> "{$path}sample_100.xml",
				'10000'	=> "{$path}sample_10000.xml"
			],
			'xml'		=> [
				'100'	=> "{$path}sample_100.xml",
				'10000'	=> "{$path}sample_10000.xml"
			]
		]
		as
		$type => $files
	)
	{
		foreach($files as $lines => $file)
		{
			$samples = [];
			for($i = 0; $i < 32; $i++)
			{
				$start = microtime(true);
				switch($type)
				{
					case 'simplexml':
						$sxe = new SimpleXMLElement($file, null, true);
						$arr = [];
						convertSimpleXmlObjToArr($sxe, $arr);
						unset($sxe, $arr);
						break;
					
					case 'xml':
						$xml = new xml($file);
						$arr = $xml->data;
						unset($xml, $arr);
						break;
				}
				$samples[] = microtime(true) - $start;
			}
			echo "{$type}: Parsed {$lines} line xml file in " .
				number_format((array_sum($samples) / 32), 4) .
				" seconds.\n";
		}
	}
	
?>