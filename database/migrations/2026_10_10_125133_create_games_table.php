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
        Schema::create('games', function (Blueprint $table) {
    $table->unsignedInteger('appid')->primary();
    $table->string('name')->index();
    $table->date('release_date')->nullable();
    $table->unsignedSmallInteger('release_year')->nullable()->index();
    $table->string('developer')->default('Unknown');
    $table->string('publisher')->default('Unknown');
    $table->string('genres');
    $table->string('platforms', 100);
    $table->decimal('price', 8, 2)->index();
    $table->boolean('is_free');
    $table->unsignedInteger('positive_ratings');
    $table->unsignedInteger('negative_ratings');
    $table->decimal('positive_pct', 4, 1)->nullable();
    $table->unsignedBigInteger('owners_min');
    $table->unsignedBigInteger('owners_max');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
