<?php declare(strict_types=1);

namespace MikeWeb\CakeOdbc\Database\Driver;

use Cake\Database\Driver\Postgres as PostgresDriver;
use Cake\Database\Exception\MissingDriverException;

class Postgres extends PostgresDriver {

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