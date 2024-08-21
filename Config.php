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

  /**
   * Load configuration data from $configFile into memory.
  */
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
        error_log("Input file '{$json_data['datafile']}' not found");
      }
    }

    $this->areas = $json_data['areas'] ?? array();

    $this->limits = $json_data['limits'] ?? array();
    uasort($this->limits, function ($a, $b) {
      if ($a["threshold"] == $b["threshold"]) {
        return 0;
      }
      return ($a["threshold"] < $b["threshold"]) ? -1 : 1;
    });

    $this->texts = $json_data['texts'] ?? array();
  }

  /**
   * Return full areas array.
   *
   * @return array<string,mixed>
   */
  public function areas(): array
  {
    return $this->areas;
  }

  /**
   * Return property array for area $name.
   *
   * @return array<string,mixed> | null
   */
  public function area(string $name): array | null
  {
    return $this->areas[$name] ?? null;
  }

  /**
   * Return full limits array.
   *
   * @return array<string,mixed>
   */
  public function limits(): array
  {
    return $this->limits;
  }

  /**
   * Return property array for limit $name.
   *
   * @return array<string,mixed> | null
   */
  public function limit(string $name): array | null
  {
    return $this->limits[$name] ?? null;
  }

  /**
   * Return limit id correspondiing to counter $value.
   */
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

  /**
   * Return array for texts for $lang.
   *
   * @return array<string,mixed>
   */
  public function texts(string $lang): array
  {
    if (!array_key_exists($lang, $this->texts)) {
      error_log("No texts for language $lang defined in configuration");
      return array();
    }
    return $this->texts[$lang];
  }

  /**
   * Return name of input datafile.
   */
  public function dataFile(): string
  {
    return $this->dataFile;
  }
}
