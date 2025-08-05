<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Link to users
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null')->unique(); // 🚨 Add unique constraint here
            $table->string('license_number')->unique();
            $table->string('phone_number');
            $table->string('home_address');
            $table->enum('sex', ['male', 'female', 'other']);
            $table->enum('driver_type', ['staff', 'visitor', 'organization', 'vehicle_owner'])->default('staff');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

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
