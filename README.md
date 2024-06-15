# Laravel Storage with Database

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rakshitbharat/laravel-storage-with-database.svg?style=flat-square)](https://packagist.org/packages/rakshitbharat/laravel-storage-with-database)
[![Total Downloads](https://img.shields.io/packagist/dt/rakshitbharat/laravel-storage-with-database.svg?style=flat-square)](https://packagist.org/packages/rakshitbharat/laravel-storage-with-database)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Laravel Storage with Database is a powerful package that provides a seamless way to store and retrieve text-based data using a database in Laravel. It offers a simple and intuitive API similar to Laravel's built-in Storage facade, making it easy to switch from file-based storage to database storage for your text-based data.

## Features

- Store and retrieve text-based data using a database
- Seamless integration with Laravel's Storage facade
- Simple and intuitive API for interacting with stored data
- Supports various database drivers (MySQL, PostgreSQL, SQLite, etc.)
- Customizable database table and column names
- Provides mock implementations for file-related functions
- Comprehensive documentation and code examples

## Installation

You can install the package via Composer:

```bash
composer require rakshitbharat/laravel-storage-with-database
```

After installing the package, you need to publish the configuration file:

```bash
php artisan vendor:publish --provider="Rakshitbharat\LaravelStorageWithDatabase\StorageDatabaseServiceProvider" --tag="config"
```

This will create a `storage-database.php` configuration file in your `config` directory. You can modify this file to customize the database connection, table name, and column names.

Next, run the database migration to create the necessary table:

```bash
php artisan migrate
```

## Usage

Once the package is installed and configured, you can start using it to store and retrieve text-based data. The package provides a `StorageDatabase` facade that you can use to interact with the stored data.

### Storing Data

To store data, you can use the `put` method:

```php
use Rakshitbharat\LaravelStorageWithDatabase\Facades\StorageDatabase;

StorageDatabase::put('key', 'value');
```

### Retrieving Data

To retrieve data, you can use the `get` method:

```php
use Rakshitbharat\LaravelStorageWithDatabase\Facades\StorageDatabase;

$value = StorageDatabase::get('key');
```

### Checking Data Existence

To check if a key exists, you can use the `exists` method:

```php
use Rakshitbharat\LaravelStorageWithDatabase\Facades\StorageDatabase;

$exists = StorageDatabase::exists('key');
```

### Deleting Data

To delete data, you can use the `delete` method:

```php
use Rakshitbharat\LaravelStorageWithDatabase\Facades\StorageDatabase;

StorageDatabase::delete('key');
```

### Other Methods

The package provides several other methods for interacting with the stored data, such as `append`, `prepend`, `copy`, `move`, `size`, `lastModified`, and more. Please refer to the documentation for a complete list of available methods.

## Configuration

The package comes with a configuration file `storage-database.php` that allows you to customize various settings. You can modify the database connection, table name, and column names to suit your requirements.

## Documentation

For detailed documentation and code examples, please refer to the [official documentation](https://github.com/rakshitbharat/laravel-storage-with-database/wiki).

## Contributing

Contributions are welcome! If you find any issues or have suggestions for improvements, please open an issue or submit a pull request on the [GitHub repository](https://github.com/rakshitbharat/laravel-storage-with-database).

## License

Laravel Storage with Database is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
