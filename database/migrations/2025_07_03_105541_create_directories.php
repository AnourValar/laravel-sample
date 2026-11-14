<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use \AnourValar\LaravelAtom\Traits\PostgresTrait;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('directories', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->jsonb('data');
            $table->string('search')->nullable(); // index below
            $table->timestamps();
        });
        $this->addGinIndex('directories', 'search');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directories');
    }
};
