<?php declare(strict_types=1);

namespace MikeWeb\CakeOdbc\Database\Driver;

use Cake\Database\Driver\Sqlserver as CakeSqlserver;
use Cake\Database\Exception\MissingDriverException;
use Cake\Database\QueryCompiler;
use Cake\Database\SqlserverCompiler;
use InvalidArgumentException;
use MikeWeb\CakeOdbc\Utility\Odbc;
use MikeWeb\CakeOdbc\Database\SqlserverCompiler as OverrideCompiler;
use PDO;

class Sqlserver extends CakeSqlserver {

    use OdbcDriverTrait;

    const QUOTEID_SQL92 = 1;
    const QUOTEID_LEGACY = 0;

    const AUTH_SQL_PASSWORD = 'SqlPassword';
    const AUTH_AD_PASSWORD = 'ActiveDirectoryPassword';
    const AUTH_AD_MSI = 'ActiveDirectoryMsi';
    const AUTH_AD_SERVICE = 'ActiveDirectoryServicePrincipal';

    const KEYSTORE_AUTH_PASSWORD = 'KeyVaultPassword';
    const KEYSTORE_AUTH_SECRET = 'KeyVaultClientSecret';

    const CURSOR_FORWARD = 'SQLSRV_CURSOR_FORWARD';
    const CURSOR_STATIC = 'SQLSRV_CURSOR_STATIC';
    const CURSOR_DYNAMIC = 'SQLSRV_CURSOR_DYNAMIC';
    const CURSOR_KEYSET = 'SQLSRV_CURSOR_KEYSET';
    const CURSOR_BUFFER = 'SQLSRV_CURSOR_CLIENT_BUFFERED';

    protected array $dsnOptions = [
        'app'                       => null,
        'port'                      => 1433,
        'instance'                  => null,
        'readOnly'                  => false,
        'database'                  => 'master',
        'encrypt'                   => false,
        'multipleActiveResultSets'  => false,
        'accessToken'               => null,
        'columnEncryption'          => false,
        'authentication'            => self::AUTH_SQL_PASSWORD,
        'connectionPooling'         => true,
        'connectRetryCount'         => 1,
        'connectRetryInterval'      => 10,
        'driver'                    => null,
        'failoverPartner'           => null,
        'keyStoreAuthentication'    => null,
        'keyStorePrincipalId'       => null,
        'keyStoreSecret'            => null,
        'language'                  => null,
        'loginTimeout'              => null,
        'multiSubnetFailover'       => false,
        'quotedId'                  => self::QUOTEID_SQL92,
        'trustServerCertificate'    => false,
        'wsid'                      => null,
    ];

    /**
     * @param array $config
     * @return string
     */
    protected function generateDsn(array $config): string {
        $config = $config +
            $this->dsnOptions + [
                'host'      => 'localhost',
            ];

        $parts = [];
        $host = $config['host'];

        if (isset($config['instance'])) {
            $host .= '\\' . $config['instance'];
            unset($config['instance']);
        }

        if (isset($config['port'])) {
            $host .= ','. (string)$config['port'];
            unset($config['port']);
        }

        foreach (array_keys($this->dsnOptions) as $key) {
            $option = ucfirst($key);
            $value = $config[$key];

            if (!isset($value)) {
                continue;
            }

            switch ($key) {
                case 'app':
                case 'wsid':
                    $option = mb_strtolower($option);
                    break;

                case 'failoverPartner':
                    $option = 'Failover_Partner';
                    break;

                case 'readOnly':
                    $option = 'ApplicationIntent';
                    $value = $value ? 'ReadOnly' : 'ReadWrite';
                    break;

                case 'multiSubnetFailover':
                    $value = $value ? 'yes' : 'no';
                    break;

                case 'encrypt':
                case 'trustServerCertificate':
                case 'multipleActiveResultSets':
                case 'connectionPooling':
                case 'columnEncryption':
                    $value = (int)$value;
                    break;
            }

            $parts[] = vsprintf(
                '%s=%s',
                [$option, (string)$value]
            );
        }

        return "Server={$host};" . join(';', $parts);
    }

    /** @inheritDoc */
    public function connect(): bool {
        if ($this->_connection) {
            return true;
        }

        $config = $this->_config + [
                'host'      => 'localhost',
                'driver'    => null,
            ];

        if (!isset($config['driver'])) {
            $config['driver'] = Odbc::getDefaultSqlserverDriver();

        } elseif (!Odbc::driverExists($config['driver'])) {
            throw new MissingDriverException(
                __('Unable to find ODBC driver [%s] for connection.', [
                    $config['driver']
                ])
            );
        }

        $dsn = 'odbc:' . $this->generateDsn($config);

        $connected = $this->_connect($dsn, $config);

        if ($connected) {
            $this->postConnectionExecution($config);
        }

        return true;
    }

    /** @inheritDoc */
    public function version(): string {
        if ($this->_version === null) {
            $this->connect();
            $this->_version = (string)$this
                ->_connection
                ->query("SELECT SERVERPROPERTY('ProductVersion') as VERSION")
                ->fetchColumn();
        }

        return $this->_version;
    }

    /** @inheritDoc */
    public function enabled(): bool {
        return (
            in_array('odbc', PDO::getAvailableDrivers(), true) &&
            !empty(Odbc::getDriversForProtocol('sqlserver'))
        );
    }

    /**
     * {@inheritDoc}
     * @return \Cake\Database\SqlserverCompiler
     */
    public function newCompiler(): QueryCompiler
    {
        $output = $this->_config['useInsertOutput'] ?? true;

        if ($output === false)
            return new OverrideCompiler();

        return new SqlserverCompiler();
    }
}
