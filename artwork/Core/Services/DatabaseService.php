<?php

namespace Artwork\Core\Services;

use Illuminate\Support\Facades\DB;

class DatabaseService
{
    final public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    final public function inTransaction(): bool
    {
        return DB::transactionLevel() === 1;
    }

    final public function commitTransaction(): void
    {
        DB::commit();
    }

    final public function rollbackTransaction(): void
    {
        DB::rollBack();
    }
}
