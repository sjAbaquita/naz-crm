<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('board_cards', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('description');
            $table->date('due_date')->nullable()->after('start_date');
            $table->enum('priority', ['Low', 'Medium', 'High', 'Urgent'])->default('Medium')->after('due_date');
        });
    }

    public function down()
    {
        Schema::table('board_cards', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'due_date', 'priority']);
        });
    }
};