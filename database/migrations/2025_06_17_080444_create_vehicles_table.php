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
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('plate_number')->unique();
    $table->string('model');
    $table->string('manufacturer');
    $table->integer('year');
    $table->enum('ownership_type', ['organization', 'individual'])->default('organization');
    $table->unsignedBigInteger('owner_id')->nullable()->after('ownership_type');
    $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
    $table->timestamps();
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
