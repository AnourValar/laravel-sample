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
        Schema::create('users', function (Blueprint $table) {
            $table->id()->startingValue($this->shiftId());
            $table->unsignedBigInteger('pid')->nullable()->index();
            $table->string('role')->index();
            $table->jsonb('role_data')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password')->nullable();
            $table->string('totp_secret')->nullable();
            $table->jsonb('additional_abilities')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_chatid')->nullable();
            $table->jsonb('tag_ids')->nullable(); // index below
            $table->text('comments')->nullable();
            $table->string('search')->nullable(); // index below
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('tag_ids', null, 'GIN');
            $table->index('created_at');
            $table->index('deleted_at');
        });
        $this->addGinIndex('users', 'search');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }

    /**
     * @return int
     */
    protected function shiftId(): int
    {
        if (config('app.env') == 'dev') {
            return now()->format('ymdHi') * 1000;
        }

        return 10000;
    }
};
