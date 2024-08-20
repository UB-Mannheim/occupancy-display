<?php

namespace OccupancyDisplay;

use DateTime;
use DateTimeZone;

class Output
{
  private function __construct()
  {
  }

  public static function lastUpdated(string $file, string $lang="de"): string
  {
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
   * @return array<string,mixed>
   */
  public static function area(Config $config, string $name): array
  {
    $data = Output::parseDataFile($config->dataFile());

    $area = $config->area($name);
    if (!is_null($area)) {
      $capacity = $area["capacity"];
      $value = 0;
      foreach ($area["inputs"] as $input) {
        $value += $data[$input];
      }
      $value = 100 * $value / ($area["factor"] * $capacity);
      $value = $value < 0 ? 0 : $value;
      $value = $value > 100 ? 100 : $value;
      $value = (int) floor($value + 0.5);

      return array(
        "name"     => $area["name"],
        "capacity" => $capacity,
        "percent"  => $value,
        "state"    => $config->currentState($value),
      );
    }

    return array();
  }

  /**
   * @return array<string,mixed>
   */
  private static function parseDataFile(string $dataFile): array
  {
    $ret = array();
    if (!file_exists($dataFile)) {
      error_log('Data file ' . $dataFile . ' not found');
      return $ret;
    }

    foreach (explode(" ", file_get_contents($dataFile)) as $area) {
      $areaData = explode(":", $area);
      $ret[$areaData[0]] = (int) $areaData[1];
    }
    return $ret;
  }
}
