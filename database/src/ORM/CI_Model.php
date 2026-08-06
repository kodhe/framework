<?php

declare(strict_types=1);

namespace Kodhe\Framework\Database\ORM;

/**
 * Thin CI3-compatible base model.
 *
 * Does NOT define insert/update/delete signatures so application models
 * may keep their classic CI3 methods, e.g. insert($table, $data).
 */
class CI_Model extends LegacyModel
{
    public function __construct()
    {
        parent::__construct();
    }
}
