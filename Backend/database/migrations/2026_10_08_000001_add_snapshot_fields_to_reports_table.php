<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();
            $table->foreignId('generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->json('summary');
            $table->json('details');
            $table->timestamp('generated_at')->useCurrent();
            $table->index(['store_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'period_start', 'period_end']);
            $table->dropConstrainedForeignId('store_id');
            $table->dropConstrainedForeignId('generated_by');
            $table->dropColumn([
                'period_start',
                'period_end',
                'summary',
                'details',
                'generated_at',
            ]);
        });
    }
};
