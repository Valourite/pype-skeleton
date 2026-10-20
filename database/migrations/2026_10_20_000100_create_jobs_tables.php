<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // Queued jobs for QUEUE_DRIVER=database, and the ones that gave up.
        $schema->create('jobs', function (Table $t) {
            $t->id();
            $t->string('queue', 64);
            $t->string('name');
            $t->longText('payload');
            $t->integer('attempts')->default(0);
            $t->bigInteger('available_at');
            $t->bigInteger('reserved_at')->nullable();
            $t->bigInteger('created_at');
            $t->index(['queue', 'available_at']);
        });

        $schema->create('failed_jobs', function (Table $t) {
            $t->id();
            $t->string('queue', 64);
            $t->string('name');
            $t->longText('payload');
            $t->longText('exception');
            $t->bigInteger('failed_at');
        });
    }
};
