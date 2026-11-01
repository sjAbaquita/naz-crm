<?php

use Illuminate\Support\Facades\Schema;

test('board_cards table has new task property columns', function () {
    $columns = Schema::getColumns('board_cards');
    $columnNames = collect($columns)->pluck('name')->all();

    expect($columnNames)->toContain('start_date')
        ->and($columnNames)->toContain('due_date')
        ->and($columnNames)->toContain('priority');
});

test('subtasks table exists with required columns', function () {
    expect(Schema::hasTable('subtasks'))->toBeTrue();

    $columns = Schema::getColumns('subtasks');
    $columnNames = collect($columns)->pluck('name')->all();

    expect($columnNames)->toContain('board_card_id')
        ->and($columnNames)->toContain('title')
        ->and($columnNames)->toContain('is_completed')
        ->and($columnNames)->toContain('created_by')
        ->and($columnNames)->toContain('updated_by');
});

test('board_card_dependencies table exists with required columns', function () {
    expect(Schema::hasTable('board_card_dependencies'))->toBeTrue();

    $columns = Schema::getColumns('board_card_dependencies');
    $columnNames = collect($columns)->pluck('name')->all();

    expect($columnNames)->toContain('board_card_id')
        ->and($columnNames)->toContain('depends_on_card_id');
});