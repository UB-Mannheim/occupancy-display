<?php

namespace OccupancyDisplay;

class Config {
    const CONFIG_FILE = (__DIR__ . '/config.json');

    /** @var array<string,mixed> */
    private array $_areas;
    /** @var array<string,mixed> */
    private array $_limits;
    private string $_dataFile;
    
    public function __construct() 
    {
        $this->_areas  = array();
        $this->_limits = array();
        $this->_dataFile = "";
    }

  public function load(string $configFile = self::CONFIG_FILE) : void {
        if (!file_exists($configFile)) {
            error_log("Configuration file $configFile not found");
            return;
        }
        
        $json_data = json_decode(file_get_contents($configFile), true);
        if (is_null($json_data) || !count($json_data)) {
            error_log('Could not read configuration file, syntax error?');
            return;
        }

        if (array_key_exists('areas', $json_data)) {
            foreach($json_data['areas'] as $id => $area) {
                $this->_areas[$id] = self::parseAreaConfig($area);
            }
        }
        
        if (array_key_exists('limits', $json_data)) {
            foreach($json_data['limits'] as $id => $limit) {
                $this->_limits[$id] = self::parseLimitConfig($limit);
            }
        }
        uasort($this->_limits, function($a, $b) {
            if ($a["threshold"] == $b["threshold"]) {
                return 0;
            }
            return ($a["threshold"] < $b["threshold"]) ? -1 : 1;
        });

        if (array_key_exists('datafile', $json_data)) {
            $this->_dataFile = realpath(__DIR__ . '/' . $json_data['datafile']);
            if (!file_exists($this->_dataFile)) {
                error_log("Input file " . $this->_dataFile . " not found");
            }
        }
    }

    /**
     * @param array<string,mixed> $area
     * @return array<string,mixed>
     */
    public function parseAreaConfig(array $area) : array {
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
    public function parseLimitConfig(array $limit) : array {
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
        return $this->_areas;
    }

    /**
     * @return array<string,mixed> | null
     */
    public function area(string $name) : array | null
    {
        return $this->_areas[$name];
    }

    /**
     * @return array<string,mixed>
     */
    public function limits(): array
    {
        return $this->_limits;
    }

    /**
     * @return array<string,mixed> | null
     */
    public function limit(string $name) : array | null
    {
        return $this->_limits[$name];
    }

    public function currentState(int $value) : string
    {
        $state = "";
        foreach ($this->_limits as $limitId => $limitData) {
            if ($value >= $limitData["threshold"]) {
                $state = $limitId;
            }
        }
        return $state;
    }

    public function dataFile() : string
    {
        return $this->_dataFile;
    }
}
?>
