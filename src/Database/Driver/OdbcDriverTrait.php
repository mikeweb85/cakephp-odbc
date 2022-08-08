<?php

namespace MikeWeb\CakeOdbc\Database\Driver;

use Cake\Cache\Cache;
use Cake\Utility\Hash;
use InvalidArgumentException;
use SplFileInfo;

trait OdbcDriverTrait {

    protected function postConnectionExecution(array $config): void {
        $config += [
            'init'          => null,
            'settings'      => null,
            'attributes'    => null,
        ];

        if (!empty($config['init'])) {
            foreach ((array)$config['init'] as $command) {
                $this
                    ->getConnection()
                    ->exec($command);
            }
        }

        if (!empty($config['settings']) && is_array($config['settings'])) {
            foreach ($config['settings'] as $key => $value) {
                $this
                    ->getConnection()
                    ->exec("SET {$key} {$value}");
            }
        }

        if (!empty($config['attributes']) && is_array($config['attributes'])) {
            foreach ($config['attributes'] as $key => $value) {
                $this
                    ->getConnection()
                    ->setAttribute($key, $value);
            }
        }
    }
}