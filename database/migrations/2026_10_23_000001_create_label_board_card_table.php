<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('label_board_card', function (Blueprint $table) {
            $table->unsignedBigInteger('label_id');
            $table->unsignedBigInteger('board_card_id');
            $table->timestamps();

            $table->foreign('label_id')
                ->references('id')
                ->on('labels')
                ->onDelete('cascade');

            $table->foreign('board_card_id')
                ->references('id')
                ->on('board_cards')
                ->onDelete('cascade');

            $table->unique(['label_id', 'board_card_id'], 'label_card_unique');
        });
    }

    public function down()
    {
        Schema::table('label_board_card', function (Blueprint $table) {
            $table->dropForeign(['label_id']);
            $table->dropForeign(['board_card_id']);
        });

        Schema::dropIfExists('label_board_card');
    }
};