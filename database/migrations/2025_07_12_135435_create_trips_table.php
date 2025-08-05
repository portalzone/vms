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
    Schema::create('trips', function (Blueprint $table) {
        $table->id();
        $table->foreignId('driver_id')->constrained()->onDelete('cascade');
        $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
        $table->decimal('amount', 10, 2)->nullable()->after('status');
        $table->string('start_location');
        $table->string('end_location');
        $table->timestamp('start_time');
        $table->timestamp('end_time')->nullable();
        $table->text('notes')->nullable();
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
