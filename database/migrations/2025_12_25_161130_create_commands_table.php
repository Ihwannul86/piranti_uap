<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->string('command'); // buzzer_on, buzzer_off, set_threshold
            $table->json('params')->nullable(); // {temp: 30, tds: 200}
            $table->boolean('executed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
