<?php declare(strict_types=1);

namespace MikeWeb\CakeOdbc\Database\Driver;

use Cake\Database\Driver\Sqlite as SqliteDriver;
use Cake\Database\Exception\MissingDriverException;

class Sqlite extends SqliteDriver {

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
