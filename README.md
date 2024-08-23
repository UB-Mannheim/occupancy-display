# Occupancy Display

Processes and displays occupancy numbers / visitor counts.

## Configuration

`config.template` can be used as a starting point for creating the
configuration file `config.json`.

### Configuration file


```json
{
  "areas": {
    "area1": {
      "name": "area1",
      "capacity": 360,
      "factor": 0.68,
      "inputs": ["Area1"]
    }
  },
  "limits": {
    "green": {
      "threshold": 0,
      "image": "img/green_lights.png"
    },
    "yellow": {
      "threshold": 91,
      "image": "img/yellow_lights.png"
    },
    "red": {
      "threshold": 96,
      "image": "img/red_lights.png"
    }
  },
  "datafile": "input_file",
  "texts": {
    "en": {
      "tooltip": "percent % of capacity seats in total are occupied",
      "nodata": "Temporarily no data available",
      "closed": "No seats available"
    },
    "de": {
      "tooltip": "percent % von capacity Arbeitsplätzen sind belegt",
      "nodata": "Momentan keine Daten verfügbar",
      "closed": "Keine Arbeitsplätze vorhanden"
    }
  }
}
```

| Field | Description |
| ------| ----------- |
| `areas` | Dictionary of areas, i.e. identified by id and display *name* `area1`. Additional parameters are the *capacity* for visitors, a correction *factor* and an array of *inputs*. *state* can be specified in order to set the display fixed to `closed` or `nodata`. |
| `limits` | Dictionary of states, i.e. **green**, **yellow** and **red**. The |
| `datafile` | Path to the input data file. Expected content: `input1:n1 input2:n2 ...`. |
| `texts` | Dictionary of display texts for languages **en** and **de**. *tooltip*, *nodata* and *closed*. |

### Webserver configuration

The main script `index.php` is located in the subdirectory `htdocs`.

Example for Apache:

```
Alias "/occupancy" /var/www/occupancy-display/htdocs
```

### Development using docker / podman

For local development and testing, a temporary webserver can be
started using docker or podman:

```sh
# Start temporary webserver, stop with ctrl-c.
docker run --rm -p 8080:80 --name occupancy-display -v "$PWD":/var/www/html php:8.2-apache
```

The display can be accessed at http://localhost:8080/htdocs.

## Usage

When called without parameters, the web interface shows a table of all
configured areas, by default in German.

The output can be configured using the following parameters:

* `area`: valid values are any of the configured area `id`s. If
  omitted, all configured areas are shown.
* `lang`: valid values are *en* and *de*. If omitted, *de* is used.
* `output`: valid values are *json*. If omitted, HTML output is shown.

### Examples

* Print JSON output: http://localhost:8080/htdocs/?output=json
* Show *area1* as english HTML output:
  http://localhost:8080/htdocs/?area=area1&lang=en

### Example output

After copying `config.template` to `config.json` and creating an
`input_file` with contents `Area1:100`, calling
http://localhost:8080/htdocs/?output=json&lang=en produces the
following output:

```json
{
  "lastupdated": "2024-08-20, 5:04 pm",
  "texts": {
    "tooltip": "percent % of capacity seats in total are occupied",
    "nodata": "Temporarily no data available",
    "closed": "No seats available"
  },
  "areas": {
    "area1": {
      "name": "area1",
      "capacity": 360,
      "percent": 41,
      "state": "green"
    }
  }
}
```

## Updating the input file

For real-time monitoring the occupancy, the input file has to be
updated in regular intervals. Depending on the environment, different
approaches are possible:

* a cgi script for updating the input file externally
* a cron job pulling the necessary information

## Real world example

This software is used to feed the [seat occupancy display](https://www.bib.uni-mannheim.de/en/locations/available-seats/) of the [Mannheim University Library](https://www.bib.uni-mannheim.de/en).
