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
        Schema::table('products', function (Blueprint $table) {
            $table->string('customization_type', 20)->default('standard');
            $table->unsignedTinyInteger('name_max_length')->default(8);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('customization')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('customization');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['customization_type', 'name_max_length']);
        });
    }
};
