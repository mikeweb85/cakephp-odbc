<?php declare(strict_types=1);

namespace MikeWeb\CakeOdbc\Utility;

use Cake\Cache\Cache;
use Cake\Database\Exception\MissingDriverException;
use Cake\Utility\Hash;
use InvalidArgumentException;
use SplFileInfo;

class Odbc {

    /** @var string */
    protected static string $driver_pattern = '/(!\n)?(\[(?P<name>.*)\])\r?\n(Description=)(?P<description>.*)/im';

    /** @var string */
    protected static string $sqlsrv_pattern = '/(SQL\sNative\sClient|ODBC\sDriver\s(?P<version>[\.0-9]+)\sfor\sSQL\sServer)/i';

    /**
     * @return string
     * @throws MissingDriverException
     */
    public static function getDefaultSqlserverDriver(): string {
        return static::getDefaultDriverForProtocol('sqlserver');
    }

    /**
     * @return string
     * @throws MissingDriverException
     */
    public static function getDefaultSqliteDriver(): string {
        return static::getDefaultDriverForProtocol('sqlite');
    }

    /**
     * @return string
     * @throws MissingDriverException
     */
    public static function getDefaultMysqlDriver(): string {
        return static::getDefaultDriverForProtocol('mysql');
    }

    /**
     * @return string
     * @throws MissingDriverException
     */
    public static function getDefaultPostgresDriver(): string {
        return static::getDefaultDriverForProtocol('postgres');
    }

    /**
     * @param string $protocol
     * @return string|null
     * @throws MissingDriverException
     */
    public static function getDefaultDriverForProtocol(string $protocol): ?string {
        $drivers = static::getDriversForProtocol($protocol);

        if (empty($drivers)) {
            throw new MissingDriverException(
                __('No drivers found for %s protocol,', [
                    $protocol
                ])
            );
        }

        return $drivers[0];
    }

    /**
     * @return string[]
     */
    public static function getSqlserverDrivers(): array {
        return static::getDriversForProtocol('sqlserver');
    }

    /**
     * @return string[]
     */
    public static function getMysqlDrivers(): array {
        return static::getDriversForProtocol('mysql');
    }

    /**
     * @return string[]
     */
    public static function getPostgresDrivers(): array {
        return static::getDriversForProtocol('postgres');
    }

    /**
     * @return string[]
     */
    public static function getSqliteDrivers(): array {
        return static::getDriversForProtocol('sqlite');
    }

    /**
     * @param array|string $driver
     * @param bool $reset
     * @return bool
     */
    public static function driverExists(array|string $driver, bool $reset=false): bool {
        if (is_array($driver)) {
            list($protocol, $driver) = $driver;
        }

        $drivers = isset($protocol) ?
            static::getDriversForProtocol($protocol, $reset) :
            static::getDrivers($reset);

        return in_array($driver, $drivers, true);
    }

    /**
     * @param string $protocol
     * @param bool $reset
     * @return string[]
     */
    public static function getDriversForProtocol(string $protocol, bool $reset=false): array {
        if ($reset) {
            Cache::delete("odbc:drivers:{$protocol}", '_cake_core_');
        }

        return Cache::remember(
            sprintf('odbc:drivers:%s', $protocol),
            function () use ($protocol, $reset) {
                $drivers = static::getDriverMap($reset)[$protocol] ?? [];
                return Hash::extract($drivers, '{n}.name');
            },
            '_cake_core_'
        );
    }

    /**
     * @param bool $reset
     * @return array
     */
    public static function getDriverMap(bool $reset=false): array {
        if ($reset) {
            Cache::delete('odbc:drivers:map', '_cake_core_');
        }

        return Cache::remember(
            'odbc:drivers:map',
            function () use ($reset) {;
                return Hash::combine(
                    static::parseDrivers($reset),
                    '{n}.name',
                    '{n}',
                    '{n}.protocol'
                );
            },
            '_cake_core_'
        );
    }

    /**
     * @param bool $reset
     * @return string[]
     */
    public static function getDrivers(bool $reset=false): array {
        if ($reset) {
            Cache::delete('odbc:drivers:list', '_cake_core_');
        }

        return Cache::remember(
            'odbc:drivers:list',
            function () use ($reset) {
                return Hash::extract(static::parseDrivers($reset), '{n}.name');
            },
            '_cake_core_'
        );
    }

    /**
     * @param bool $reset
     * @return array
     */
    public static function parseDrivers(bool $reset=false): array
    {
        if ($reset) {
            Cache::delete('odbc:drivers:all', '_cake_core_');
        }

        return Cache::remember(
            'odbc:drivers:all',
            function () {
                $drivers = $matches = $driverMatch = [];
                $filename = env('ODBCSYSINSTINI', '/etc/odbcinst.ini');

                if (empty($filename)) {
                    throw new InvalidArgumentException("No ODBCINST file defined.");
                }

                $odbcInst = new SplFileInfo($filename);

                if (!($odbcInst->isFile() && $odbcInst->isReadable())) {
                    throw new InvalidArgumentException(
                        __('ODBCINST file [%s] does not exist or is not readable.', [
                            $filename
                        ])
                    );
                }

                if (false === ($contents = @file_get_contents($filename, false))) {
                    throw new InvalidArgumentException(
                        __('ODBCINST file [%s] could not be loaded.', [
                            $filename
                        ])
                    );
                }

                if (
                    preg_match_all(
                        static::$driver_pattern,
                        $contents,
                        $matches
                    )
                ) {
                    foreach ($matches['name'] as $i => $match) {
                        $driver = [
                            'name' => $matches['name'][$i],
                            'description' => $matches['description'][$i],
                        ];

                        switch (true) {
                            case (
                                0 < preg_match(
                                    static::$sqlsrv_pattern,
                                    $driver['name'], $driverMatch)):
                                if (!empty($driverMatch['version'])) {
                                    $driver['version'] = $driverMatch['version'];
                                }

                                $driver['protocol'] = 'sqlserver';
                                break;

                            case (0 < preg_match('/MySQL/i', $driver['name'])):
                                $driver['protocol'] = 'mysql';
                                break;

                            case (0 < preg_match('/PostgreSQL/i', $driver['name'])):
                                $driver['protocol'] = 'postgres';
                                break;

                            default:
                                continue 2;
                        }

                        $drivers[] = $driver;
                    }
                }

                return $drivers;
            },
            '_cake_core_'
        );
    }
}