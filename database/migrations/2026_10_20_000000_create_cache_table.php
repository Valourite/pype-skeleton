<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // Cached values for CACHE_DRIVER=database.
        $schema->create('cache', function (Table $t) {
            $t->string('key')->primary();
            $t->longText('value');
            $t->bigInteger('expires_at')->nullable()->index();
        });
    }
};
