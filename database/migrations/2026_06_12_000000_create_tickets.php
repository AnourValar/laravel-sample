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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id()->startingValue(10000);
            $table->unsignedBigInteger('author_id')->nullable()->index();
            $table->unsignedBigInteger('moderator_id')->nullable()->index();
            $table->string('type')->index();
            $table->jsonb('data_request')->nullable();
            $table->jsonb('data_response')->nullable();
            $table->string('status')->index();
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
