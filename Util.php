<?php

namespace OccupancyDisplay;

use DateTime;
use DateTimeZone;

/**
 * Return the modification time of $file as a localized string.
 */
function lastUpdated(string $file, string $lang = "de"): string
{
  if (!file_exists($file)) {
    error_log("Update timestamp reference '$file' does not exist");
    return $lang === "en" ? "no time reference" : "keine Zeitreferenz";
  }
  $date = date("d M Y H:i:s T", filemtime($file));
  $time = DateTime::createFromFormat("d M Y G:i:s e", $date);
  $timezone = 'Europe/Berlin';
  $time->setTimezone(new DateTimeZone($timezone));
  if ($lang === "en") {
    return $time->format("Y-m-d, g:i a");
  }
  return $time->format("d.m.y, H:i") . " Uhr";
}

/**
 * Return the current data for area $id.
 *
 * @return array<string,mixed>
 */
function area(Config $config, string $id): array
{
  $data = parseDataFile($config->dataFile());

  $area = $config->area($id);
  if (is_null($area)) {
    return array();
  }

  $name = $area["name"] ?? "Empty Name";
  $capacity = $area["capacity"] ?? -1;
  $factor = $area["factor"] ?? 1;
  $value = 0;
  foreach ($area["inputs"] as $input) {
    if (array_key_exists($input, $data)) {
      $value += $data[$input];
    }
  }
  $value = $factor * 100 * $value / $capacity;
  $value = $value < 0 ? 0 : $value;
  $value = $value > 100 ? 100 : $value;
  $value = (int) floor($value + 0.5);

  return array(
    "name"     => $name,
    "capacity" => $capacity,
    "percent"  => $value,
    "state"    => $config->currentState($value, $id),
  );
}

/**
 * Return the contents of $dataFile as an associative array
 * with pairs input_id => count.
 *
 * The expected input file contents should look like 'area1:n1 area2:n2 ...'.
 *
 * @return array<string,mixed>
 */
function parseDataFile(string $dataFile): array
{
  $ret = array();
  if (!file_exists($dataFile)) {
    error_log("Data file '$dataFile' not found");
    return $ret;
  }

  $data = file_get_contents($dataFile);
  if ($data === false || !strlen($data)) {
    error_log("No data in file '$dataFile'");
    return $ret;
  }

  foreach (explode(" ", $data) as $area) {
    if (strlen(trim($area)) === 0) {
      continue;
    }
    $areaData = explode(":", $area);
    if (count($areaData) != 2) {
      error_log("Format error in file '$dataFile': '$area'");
      continue;
    }
    $ret[$areaData[0]] = (int) $areaData[1];
  }
  return $ret;
}
