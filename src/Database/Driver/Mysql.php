<?php declare(strict_types=1);

namespace MikeWeb\CakeOdbc\Database\Driver;

use Cake\Database\Driver\Mysql as MySQLDriver;
use Cake\Database\Exception\MissingDriverException;

class Mysql extends MySQLDriver {

    use OdbcDriverTrait;

    /** @inheritDoc */
    public function connect(): bool {
        throw new MissingDriverException();
    }

    /** @inheritDoc */
    public function enabled(): bool {
        return false;
    }
}
