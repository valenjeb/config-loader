# Configuration Loader

Load configurations from multiple sources into a [Repository](https://github.com/valenjeb/repository) object. Supported file formats are: PHP, JSON, INI, XML, Yaml & Neon.

# Install

Install using `composer`:

```sh
$ composer require devly/config-loader
```

## Usage

### Create Loader object

```php
$safeMode = false // Set 'true' to skip exceptions

$loader = new \Devly\ConfigLoader\Loader($safeMode);
```

### Load configurations from file

```php
// Load config from a single file
$config = $loader->load('path/to/config.php');

// Load config from a list of files
$segment = true; // Segment configuration by file name

$files = [
    'path/to/config/app.php',
    'path/to/config/database.php',
];

$config = $loader->load($files, $segment);

var_dump($config->all());

// array(1) {
//  'app' => ...,
//  'database' => ...
// }
```

### Load configuration files from a directory

```php
// Load config from a single file
$formats = ['php', 'json']; // load only php and json files. keep null to load all the files in the directory
$segment = true; // Segment configuration by file names
$config = $loader->load('path/to/config', $segment, $formats);
```

### Cache configurations

Configurations can be cached to a file, a MySQL database or a custom storage provider.

#### Cache to file

The only thing needed to cache configurations to file is to set a full path to cache directory:

```php
// When creating new Loader instance
$loader = new Loader($safeMode, 'full/path/to/cache');

// Or using the `setStorage()` method:
$loader->setStorage('full/path/to/cache');
```

#### Cache to MySQL database

```php
$name = 'db_name';
$username = 'db_user';
$password = 'db_password';
$host = '172.0.0.1'; // Default 'localhost'

// Create instance of DatabaseStorage
$storage = new \Devly\ConfigLoader\Storages\DatabaseStorage($name, $username, $password, $host)

// Creating new Loader instance
$loader = new Loader($safeMode, $storage);

// Or using the `setStorage()` method:
$loader->setStorage($storage);
```

#### Custom cache storage

```php
class CustomStorage implements \Devly\ConfigLoader\Contracts\IStorage
{
    ...
}

$storage = new CustomStorage();

// Creating new Loader instance
$loader = new Loader($safeMode, $storage);

// Or using the `setStorage()` method:
$loader->setStorage($storage);
```
