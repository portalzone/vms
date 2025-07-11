<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_create_drivers_table.php
public function up()
{
    Schema::create('drivers', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('license_number')->unique();
        $table->string('phone_number');
        $table->string('home_address');
        $table->enum('sex', ['male', 'female']);
        $table->foreignId('vehicle_id')->constrained()->onDelete('cascade'); // 👈 vehicle assigned
        $table->timestamps();
    });
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
