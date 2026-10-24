<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('board_card_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_card_id')->constrained('board_cards')->onDelete('cascade');
            $table->foreignId('depends_on_card_id')->constrained('board_cards')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['board_card_id', 'depends_on_card_id'], 'card_dependency_unique');
        });
    }

    public function down()
    {
        Schema::table('board_card_dependencies', function (Blueprint $table) {
            $table->dropForeign(['board_card_id']);
            $table->dropForeign(['depends_on_card_id']);
        });

        Schema::dropIfExists('board_card_dependencies');
    }
};