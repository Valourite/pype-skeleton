<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // Pending password reset links: one per user, only a sha256 of the token (Pype\Auth\PasswordResets).
        $schema->create('password_resets', function (Table $t) {
            $t->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
            $t->string('token_hash', 64)->unique();
            $t->bigInteger('created_at');
        });
    }
};
