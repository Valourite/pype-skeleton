<?php

use Pype\Database\Schema\Migration;
use Pype\Database\Schema\Schema;
use Pype\Database\Schema\Table;

return new class extends Migration {
    public function up(Schema $schema): void
    {
        // Set when the user clicks the link in their welcome / verification email.
        $schema->table('users', fn (Table $t) => $t->timestamp('email_verified_at')->nullable());
    }
};
