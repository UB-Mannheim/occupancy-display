<?php

require_once '../Config.php';
require_once '../Util.php';

use OccupancyDisplay\Config;
use function OccupancyDisplay\{area,lastUpdated};

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

$area = array_key_exists("area", $_GET)
? strtolower(htmlspecialchars($_GET["area"])) : null;
$lang = array_key_exists("lang", $_GET)
? strtolower(htmlspecialchars($_GET["lang"])) : "de";
$jsonout = array_key_exists('output', $_GET)
? htmlspecialchars($_GET['output']) == 'json' : false;

// initialize global output array
$output = array(
  "lastupdated" => lastUpdated($config->dataFile(), $lang),
  "texts" => $config->texts($lang),
);

// add area information to output array
if (!is_null($area)) {
  $output["areas"][$area] = area($config, $area);
} else {
  foreach ($config->areas() as $areaId => $areaConfig) {
    $output["areas"][$areaId] = area($config, $areaId);
  }
}

// print JSON or HTML output
if ($jsonout) {
  header('Content-Type: application/json');
  echo json_encode($output);
} else {
  $HTML_ALL = '
<!DOCTYPE html>
<html lang="de">
<head>
  <title>Bereichsauslastung</title>
  <meta http-equiv="content-type" content="text/html; charset=utf-8">
  <meta http-equiv="refresh" content="300">
  <meta name="author" content="UB Mannheim">
  <meta name="keywords" lang="de" content="Bereichsauslastung, Arbeitsplätze, Universitätsbibliothek UB Mannheim">
  <meta name="description" content="Anzeige der Bereichsauslastung der Bibliotheksbereiche in der UB Mannheim">
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
    $tooltip = strtr($output["texts"]["tooltip"], $areaData);

    $HTML_ALL .= "
    <tr>
      <td class='colArea'>{$areaData['name']}</td>
      <td class='colValue' title='{$tooltip}'>{$value} %</td>
      <td class='colSignal'><img src='{$image}' alt='$state'></td>
    </tr>";
  }

  $HTML_ALL .= "
  </table>
  <div id='time'>
    " . ($lang == "de" ? "Stand" : "Last updated") . ": {$output['lastupdated']}
  </div>";

  $HTML_ALL .= '
</body>
</html>';

  echo trim($HTML_ALL);
}
