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
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('parent_folder')->nullable();
            $table->string('uri')->nullable();
            $table->string('title')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('hidden')->default(false);
            $table->unsignedBigInteger('position')->default(999999);
            $table->boolean('is_public')->default(false);

            $table->unsignedBigInteger('status_id')->default(1);
            $table->unsignedBigInteger('user_id')->default(0)->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('parent_folder');
            $table->dropColumn('uri');
            $table->dropColumn('title');
            $table->dropColumn('icon');
            $table->dropColumn('hidden');
            $table->dropColumn('position');
            $table->dropColumn('status_id');
            $table->dropColumn('user_id');
        });
    }
};
