<?php

require_once '../Config.php';
require_once '../Output.php';

use OccupancyDisplay\Config;
use OccupancyDisplay\Output;

// load configuration file
$config = new Config();
$config->load();

// check accepted GET parameters
$valid_params = array(
  'area', 'lang', 'output',
);

foreach ($_GET as $paramName => $paramValue) {
  if (!in_array($paramName, $valid_params)) {
    error_log("GET parameter $paramName not accepted (value: "
            . htmlspecialchars($paramValue) . ")");
  }
}

$output = array(
  "date" => Output::date($config->dataFile()),
);

$area = array_key_exists("area", $_GET) ? htmlspecialchars($_GET["area"]) : null;
$lang = array_key_exists("lang", $_GET) ? htmlspecialchars($_GET["lang"]) : "DE";
$jsonout = array_key_exists('output', $_GET) ? htmlspecialchars($_GET['output']) == 'json' : false;

if (!is_null($area)) {
  $output["areas"][$area] = Output::area($config, $area);
} else {
  foreach ($config->areas() as $areaId => $areaConfig) {
    $output["areas"][$areaId] = Output::area($config, $areaId);
  }
}

if ($jsonout) {
  header('Content-Type: application/json');
  echo json_encode($output);
} else {
  $HTML_ALL = '
<!DOCTYPE html>
<html lang="de">
<head>
  <title>Bereichsauslastung</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
  <meta http-equiv="refresh" content="300" />
  <meta name="author" content="UB Mannheim">
  <meta name="keywords" lang="de" content="Bereichsauslastung, Arbeitsplätze, Universitätsbibliothek UB Mannheim" />
  <meta name="description" content="Anzeige der Bereichsauslastung der Bibliotheksbereiche in der UB Mannheim" />
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="main.css">
</head>
<body>';

  $HTML_ALL .= "
  <table>
    <tr>
      <th>Bereich</th>
      <th>Auslastung</th>
      <th>&nbsp;</th>
    </tr>";

  foreach ($output["areas"] as $areaId => $areaData) {
    $value = $areaData['percent'];
    $state = $config->currentState($value);
    $image = $config->limit($state)["image"];
    $HTML_ALL .= "
    <tr>
      <td class='colArea'>${areaData['name']}</td>
      <td class='colValue' title=''>${value} %</td>
      <td class='colSignal'><img src='${image}'></td>
    </tr>";
  }

  $HTML_ALL .= "
  </table>
  <div id='time'>
    Stand: ${output['date']} Uhr
  </p>";

  $HTML_ALL .= '
</body>
</html>';

  echo trim($HTML_ALL);
}
