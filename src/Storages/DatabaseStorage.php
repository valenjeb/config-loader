<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace Devly\ConfigLoader\Storages;

use Devly\ConfigLoader\Contracts\IStorage;
use Devly\ConfigLoader\Exceptions\StorageError;
use Devly\ConfigLoader\ParserFactory;
use mysqli;
use Throwable;

use function function_exists;
use function mysqli_connect;
use function mysqli_connect_error;
use function pathinfo;
use function serialize;
use function sprintf;
use function unserialize;

use const PATHINFO_EXTENSION;

class DatabaseStorage implements IStorage
{
    protected mysqli $db;
    public const TABLE_NAME = 'devly_config_loader_cache';

    /**
     * Create new instance of DatabaseStorage
     *
     * Parses configuration, creates MySQL connection and a DB table to store configurations.
     *
     * @uses pathinfo to determine whether '$dbname' is a file path.
     *
     * @param string      $dbname   The database name or a full path to configuration file.
     * @param string|null $username The database username. keep empty if configurations provided in a config file.
     * @param string|null $password The database password. keep empty if configurations provided in a config file.
     * @param string|null $hostname Can be either a host name or an IP address. default is 'localhost'
     * @param int|null    $port     The port number to attempt to connect to the MySQL server
     *
     * @throws StorageError if fails to create DB connection.
     */
    public function __construct(
        string $dbname,
        ?string $username = null,
        ?string $password = null,
        ?string $hostname = null,
        ?int $port = null
    ) {
        if (! function_exists('mysqli_connect')) {
            throw new StorageError('The PHP \'ext-mysqli\' extension is not available.');
        }

        if (! empty(pathinfo($dbname, PATHINFO_EXTENSION))) {
            $this->db = $this->createConnectionWithConfig($dbname);

            return;
        }

        if (empty($dbname)) {
            throw new StorageError('The #1 parameter must be a database name or a full path to config file.');
        }

        if (empty($username)) {
            throw new StorageError('The #2 parameter "$username" is required.');
        }

        if (empty($password)) {
            throw new StorageError('The #3 parameter "$password" is required.');
        }

        $hostname = empty($hostname) ? 'localhost' : $hostname;

        $this->db = $this->createConnection($hostname, $username, $password, $dbname, $port);

        $this->createTable($dbname);
    }

    /**
     * Create DB connection with configuration file
     *
     * @throws StorageError
     */
    protected function createConnectionWithConfig(string $path): mysqli
    {
        $parser = new ParserFactory();
        try {
            $config = $parser->parse($path);
        } catch (Throwable $e) {
            throw new StorageError('Failed parsing configuration file for database connection', 0, $e);
        }

        if (! isset($config['dbname'])) {
            throw new StorageError('The \'dbname\' filed is missing in config file.');
        }

        $dbname = $config['dbname'];

        if (! isset($config['username'])) {
            throw new StorageError('The \'username\' filed is missing in config file.');
        }

        $username = $config['username'];

        if (! isset($config['password'])) {
            throw new StorageError('The \'password\' filed is missing in config file.');
        }

        $password = $config['password'];

        $hostname = $config['host'] ?: 'localhost';
        $port     = $config['port'] ?: null;

        return $this->createConnection($hostname, $username, $password, $dbname, $port);
    }

    /**
     * Connect to a database
     *
     * @throws StorageError
     */
    protected function createConnection(
        string $hostname,
        string $username,
        string $password,
        string $dbname,
        ?int $port = null
    ): mysqli {
        $connection = mysqli_connect($hostname, $username, $password, $dbname, $port);

        if ($connection === false) {
            throw new StorageError(mysqli_connect_error());
        }

        return $connection;
    }

    public function has(string $key): bool
    {
        try {
            $this->read($key);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** @inheritDoc */
    public function read(string $key): array
    {
        $stmt = $this->db->prepare(sprintf('SELECT `config_value` FROM `%s` WHERE `config_key`=?', self::TABLE_NAME));
        $stmt->bind_param('s', $key);
        if (! $stmt->execute()) {
            throw new StorageError(sprintf('Failed read from database because of an error: %s', $stmt->error));
        }

        $result = $stmt->get_result();
        $stmt->close();

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if ($result->num_rows === 0) {
            $result->free_result();

            throw new StorageError(sprintf('No cache found for key "%s"', $key));
        }

        $value = $result->fetch_assoc()['config_value'];
        $result->free_result();

        return unserialize($value);
    }

    /** @inheritDoc */
    public function write(string $key, array $content): bool
    {
        $stmt = $this->db->prepare(sprintf('SELECT `id` FROM `%s` WHERE `config_key` = ? LIMIT 1', self::TABLE_NAME));
        $stmt->bind_param('s', $key);
        $stmt->execute();

        $result = $stmt->get_result();

        $stmt->close();

        $content = serialize($content);

        // phpcs:ignore Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
        if ($result->num_rows === 0) {
            $result->free_result();

            return $this->insert($key, $content);
        }

        $id = (int) $result->fetch_assoc()['id'];
        $result->free_result();

        return $this->update($id, $key, $content);
    }

    /** @throws StorageError */
    protected function update(int $id, string $key, string $content): bool
    {
        $query = sprintf('UPDATE `%s` SET `config_key` = ?, `config_value` = ? WHERE `id` = ?', self::TABLE_NAME);
        $stmt  = $this->db->prepare($query);
        $stmt->bind_param('ssi', $key, $content, $id);

        $result = $stmt->execute();
        $error  = $stmt->error;

        $stmt->close();

        if ($result) {
            return true;
        }

        throw new StorageError($error);
    }

    /** @throws StorageError */
    protected function insert(string $key, string $content): bool
    {
        $query = sprintf('INSERT INTO `%s` (`config_key`, `config_value`) VALUES (?, ?)', self::TABLE_NAME);
        $stmt  = $this->db->prepare($query);

        $stmt->bind_param('ss', $key, $content);

        $result = $stmt->execute();
        $error  = $stmt->error;

        $stmt->close();

        if ($result) {
            return true;
        }

        throw new StorageError($error);
    }

    public function drop(string $key): bool
    {
        $query = sprintf('DELETE FROM `%s` WHERE `config_key` = ?', self::TABLE_NAME);
        $stmt  = $this->db->prepare($query);

        $stmt->bind_param('s', $key);
        $result = $stmt->execute();
        $error  = $stmt->error;

        $stmt->close();

        if ($result) {
            return true;
        }

        throw new StorageError($error);
    }

    public function __destruct()
    {
        $this->db->close();
    }

    protected function createTable(string $dbname): void
    {
        $this->db->query(sprintf(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            'CREATE TABLE `%s`.`%s` ( `id` BIGINT(20) NOT NULL AUTO_INCREMENT , `config_key` CHAR(32) NOT NULL , `config_value` LONGTEXT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;',
            $dbname,
            self::TABLE_NAME
        ));
    }
}
