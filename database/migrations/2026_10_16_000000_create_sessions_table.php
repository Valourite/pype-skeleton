<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // Browser sessions (used by the 'web' stack). The id column holds a sha256 hash of the cookie value.
        $schema->create('sessions', function (Table $t) {
            $t->string('id', 64)->primary();
            $t->text('payload');
            $t->bigInteger('expires_at')->index('sessions_expires_at');
        });
    }
};
