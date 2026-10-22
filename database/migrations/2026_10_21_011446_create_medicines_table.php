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
        Schema::create('medicines', function (Blueprint $table) {
            $table->id();
            $table->string('nom_commercial')->index();
            $table->string('dci')->index();
            $table->string('forme')->nullable();
            $table->string('dosage')->nullable();
            $table->string('categorie')->nullable();
            $table->unique(['nom_commercial', 'dosage', 'forme']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicines');
    }
};
