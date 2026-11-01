<?php

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('attachments_table_exists', function () {
    expect(\Illuminate\Support\Facades\Schema::hasTable('attachments'))->toBeTrue();
});
