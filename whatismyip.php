<?php
// Set the url
$url = 'http://fritz.box:49000/igdupnp/control/WANIPConn1';
// Header
$header = array(
	'Content-Type: text/xml',
	'charset="utf-8"',
	'SoapAction:urn:schemas-upnp-org:service:WANIPConnection:1#GetExternalIPAddress'
);
// POST data
$data = <<<XML
<?xml version="1.0" encoding="utf-8" ?>
<s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
	<s:Body>
		<u:GetStatusInfo xmlns:u="urn:schemas-upnp-org:service:WANIPConnection:1" />
	</s:Body>
</s:Envelope>
XML;
// IP pattern
$pattern = '/([0-9]{1,3}\.){3}[0-9]{1,3}/i';

// cURL object instance
$handle = curl_init();
// Set options
curl_setopt_array($handle, array(
	// Set url
	CURLOPT_URL => $url,
	// Set the result output to be a string.
	CURLOPT_RETURNTRANSFER => true,
	// Set header
	CURLOPT_HTTPHEADER => $header,
	// Set post to true
	CURLOPT_POST => true,
	// Set post data
	CURLOPT_POSTFIELDS => $data
));
// Execute request 
$result = curl_exec($handle);
// Close 
curl_close($handle);
// Find IP
preg_match($pattern, $result, $matches);
// Print output/IP
echo $matches[0];
