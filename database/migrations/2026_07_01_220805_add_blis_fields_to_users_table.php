<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('member');
            $table->string('niche_field')->nullable();
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->decimal('pending_balance', 10, 2)->default(0);
            $table->decimal('cleared_balance', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn(['role', 'niche_field', 'group_id', 'pending_balance', 'cleared_balance']);
        });
    }
};
