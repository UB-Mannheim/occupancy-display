<?php

namespace OccupancyDisplay;

class Config
{
  public const CONFIG_FILE = (__DIR__ . '/config.json');

  private string $dataFile;
  /** @var array<string,mixed> */
  private array $areas;
  /** @var array<string,mixed> */
  private array $limits;
  /** @var array<string,mixed> */
  private array $texts;

  public function __construct()
  {
    $this->dataFile = "";
    $this->areas  = array();
    $this->limits = array();
    $this->texts = array();
  }

  public function load(string $configFile = self::CONFIG_FILE): void
  {
    if (!file_exists($configFile)) {
      error_log("Configuration file $configFile not found");
      return;
    }

    $json_data = json_decode(file_get_contents($configFile), true);
    if (is_null($json_data) || !count($json_data)) {
      error_log('Could not read configuration file, syntax error?');
      return;
    }

    if (array_key_exists('datafile', $json_data)) {
      $this->dataFile = realpath(__DIR__ . '/' . $json_data['datafile']);
      if (!file_exists($this->dataFile)) {
        error_log("Input file " . $this->dataFile . " not found");
      }
    }

    if (array_key_exists('areas', $json_data)) {
      foreach ($json_data['areas'] as $id => $area) {
        $this->areas[$id] = self::parseAreaConfig($area);
      }
    }

    if (array_key_exists('limits', $json_data)) {
      foreach ($json_data['limits'] as $id => $limit) {
        $this->limits[$id] = self::parseLimitConfig($limit);
      }
    }
    uasort($this->limits, function ($a, $b) {
      if ($a["threshold"] == $b["threshold"]) {
        return 0;
      }
      return ($a["threshold"] < $b["threshold"]) ? -1 : 1;
    });

    if (array_key_exists('texts', $json_data)) {
      foreach ($json_data['texts'] as $lang => $texts) {
        $this->texts[$lang] = $texts;
      }
    }
  }

  /**
   * @param array<string,mixed> $area
   * @return array<string,mixed>
   */
  public function parseAreaConfig(array $area): array
  {
    return array(
      "name"     => $area["name"],
      "capacity" => $area["capacity"],
      "factor"   => $area["factor"],
      "offset"   => 0,
      "inputs"   => $area["inputs"],
    );
  }

  /**
   * @param array<string,mixed> $limit
   * @return array<string,mixed>
   */
  public function parseLimitConfig(array $limit): array
  {
    return array(
      "threshold" => $limit["threshold"],
      "image"     => $limit["image"],
    );
  }

  /**
   * @return array<string,mixed>
   */
  public function areas(): array
  {
    return $this->areas;
  }

  /**
   * @return array<string,mixed> | null
   */
  public function area(string $name): array | null
  {
    return $this->areas[$name];
  }

  /**
   * @return array<string,mixed>
   */
  public function limits(): array
  {
    return $this->limits;
  }

  /**
   * @return array<string,mixed> | null
   */
  public function limit(string $name): array | null
  {
    return $this->limits[$name];
  }

  public function currentState(int $value): string
  {
    $state = "";
    foreach ($this->limits as $limitId => $limitData) {
      if ($value >= $limitData["threshold"]) {
        $state = $limitId;
      }
    }
    return $state;
  }

  public function texts(string $lang):array
  {
    if (!array_key_exists($lang, $this->texts)) {
      error_log("No texts for language $lang defined in configuration");
      return array();
    }
    return $this->texts[$lang];
  }

  public function dataFile(): string
  {
    return $this->dataFile;
  }
}
