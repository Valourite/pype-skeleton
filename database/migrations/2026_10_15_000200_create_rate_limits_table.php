<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // Counters for the Throttle stage (RATE_LIMIT_DRIVER=database).
        $schema->create('rate_limits', function (Table $t) {
            $t->string('key', 64)->primary();
            $t->integer('hits');
            $t->bigInteger('reset_at');
        });
    }
};
