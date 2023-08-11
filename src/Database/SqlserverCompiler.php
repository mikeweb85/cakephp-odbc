<?php declare(strict_types=1);

namespace MikeWeb\CakeOdbc\Database;

use Cake\Database\Query;
use Cake\Database\SqlserverCompiler as BaseCompiler;
use Cake\Database\ValueBinder;

class SqlserverCompiler extends BaseCompiler
{
    /**
     * Remove "OUTPUT INSERTED.*" from default generated query
     * @param array $parts
     * @param Query $query
     * @param ValueBinder $binder
     * @return string
     */
    protected function _buildInsertPart(array $parts, Query $query, ValueBinder $binder): string
    {
        return substr(
            parent::_buildInsertPart($parts, $query, $binder),
            0,
            -18
        );
    }
}
