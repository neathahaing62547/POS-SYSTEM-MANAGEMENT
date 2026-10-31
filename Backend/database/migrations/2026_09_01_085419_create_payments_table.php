<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Obsolete migration. The payments table is created by 082137.
    }

    public function down(): void
    {
        // Intentionally empty to protect the existing table.
    }
};
