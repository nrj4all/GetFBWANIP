<?php
// FritzBox TR-064 Beispiel: WAN IP Adresse (IPv4/IPv6) abfragen

// --- Parameter-Auswahl ----------------------------------------------------
$version = isset($_GET['ip']) ? $_GET['ip'] : 'v4';   // v4 (Default) oder v6
$version = strtolower($version) === 'v6' ? 'v6' : 'v4';

// --- Basis-Parameter ------------------------------------------------------
$host       = 'http://fritz.box:49000'; // URL der FritzBox TR-064 SOAP Schnittstelle
$charset    = 'utf-8';
$envelope_ns = 'http://schemas.xmlsoap.org/soap/envelope/';
$service_ns = 'urn:schemas-upnp-org:service:WANIPConnection:1';

// Fritzbox Zugangsdaten (falls eingerichtet)
$username = '';       // Benutzername
$password = '';       // Passwort
$logfile  = './fritzbox_log.txt';

// Service-URL (f���r v4 und v6 identisch bei aktuellen Fritz!Boxen)
$serviceUrl = $host . '/igdupnp/control/WANIPConn1';

// --- Aktion abh���ngig von v4/v6 w���hlen ------------------------------------
if ($version === 'v4') {
    $action         = 'GetExternalIPAddress';
    $responseTag    = 'GetExternalIPAddressResponse';
    $valueTag       = 'NewExternalIPAddress';
} else { // v6
    // AVM-spezifische Methode, liefert u.a. NewExternalIPv6Address zur���ck
    $action         = 'X_AVM_DE_GetExternalIPv6Address';
    $responseTag    = 'X_AVM_DE_GetExternalIPv6AddressResponse';
    $valueTag       = 'NewExternalIPv6Address';
}

// --- SOAP Request erzeugen -----------------------------------------------
$body = <<<XML
<?xml version="1.0" encoding="$charset"?>
<s:Envelope xmlns:s="$envelope_ns"
            s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
  <s:Body>
    <u:$action xmlns:u="$service_ns" />
  </s:Body>
</s:Envelope>
XML;

// HTTP Header f���r SOAP Anfrage
$headers = [
    'Content-Type: text/xml; charset="' . $charset . '"',
    'SOAPAction: "' . $service_ns . '#' . $action . '"',
];

// HTTP Basic Auth einrichten
$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", $headers) . "\r\n" .
                    'Authorization: Basic ' . base64_encode("$username:$password") . "\r\n",
        'content' => $body,
        'timeout' => 5,
    ],
]);

// --- SOAP Anfrage senden -------------------------------------------------
$response = @file_get_contents($serviceUrl, false, $context);

if ($response === false) {
    echo "Fehler: Keine Antwort von der Fritz!Box oder Zugriff verweigert.\n";
    exit(1);
}

// --- XML Antwort parsen --------------------------------------------------
libxml_use_internal_errors(true);
$xml = simplexml_load_string($response);

if ($xml === false) {
    foreach (libxml_get_errors() as $error) {
        echo "XML Fehler: ", $error->message, "\n";
    }
    exit(1);
}

// Namespace-Registrierung f���r XPath
$xml->registerXPathNamespace('s', $envelope_ns);
$xml->registerXPathNamespace('u', $service_ns);

// XPath Abfrage zum gew���nschten Element (v4 oder v6)
$xpath = "//s:Body/u:$responseTag/$valueTag";
$result = $xml->xpath($xpath);

if ($result && count($result) > 0) {
    $externalIp = (string)$result[0];
    echo $externalIp;
} else {
    echo "Fehler: WAN IP Adresse ({$version}) konnte nicht ermittelt werden.\n";
    // ggf. gesamte Antwort ins Log schreiben
    file_put_contents($logfile, date("d-m-y H:i:s") . ":\n" . $xml->asXML() . "\n", FILE_APPEND | LOCK_EX);
}
