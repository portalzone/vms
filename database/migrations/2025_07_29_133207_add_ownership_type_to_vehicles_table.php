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
    Schema::table('vehicles', function (Blueprint $table) {
        $table->enum('ownership_type', ['organization', 'individual'])->default('organization');
        $table->unsignedBigInteger('owner_id')->nullable()->after('ownership_type');

        $table->foreign('owner_id')->references('id')->on('users')->onDelete('set null');
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            //
        });
    }
};
