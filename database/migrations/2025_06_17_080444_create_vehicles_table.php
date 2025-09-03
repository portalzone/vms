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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('owner_id')->nullable();

            $table->string('plate_number')->unique();
            $table->string('model');
            $table->string('manufacturer');
            $table->integer('year');

            // Main ownership type
            $table->enum('ownership_type', ['organization', 'individual'])->default('organization');

            // Subtype for individuals (nullable for organization type)
            $table->enum('individual_type', ['staff', 'visitor', 'vehicle_owner'])->nullable();

            $table->timestamps();

            // Foreign key constraints
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
