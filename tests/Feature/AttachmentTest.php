<?php

test('attachments_table_exists', function () {
    expect(\Illuminate\Support\Facades\Schema::hasTable('attachments'))->toBeTrue();
});
