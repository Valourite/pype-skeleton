<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // "Remember me": one row per remembered device (Pype\Auth\RememberTokens). Only a hash of
        // the secret part of the cookie is stored. Name set in config/auth.php (remember_table).
        $schema->create('remember_tokens', function (Table $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $t->string('selector', 24)->unique();
            $t->string('validator_hash', 64);
            $t->string('previous_hash', 64)->nullable();
            $t->bigInteger('rotated_at')->nullable();
            $t->bigInteger('expires_at');
            $t->bigInteger('last_used_at');
            $t->string('user_agent')->nullable();
        });
    }
};
