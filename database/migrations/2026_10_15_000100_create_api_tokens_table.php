<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        $schema->create('api_tokens', function (Table $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete()->index('api_tokens_user_id_index');
            $t->string('name');
            $t->string('token_hash', 64)->unique();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(Schema $schema): void
    {
        $schema->dropIfExists('api_tokens');
    }
};
