<?php

use App\Models\Board;
use App\Models\Team;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('id')->constrained()->nullOnDelete()->unique();
        });

        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->foreignId('team_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
        });

        DB::table('calendar_events')->update(['created_by' => DB::raw('owner_id')]);

        Team::query()->each(function (Team $team): void {
            Board::query()->firstOrCreate(
                ['team_id' => $team->id],
                ['name' => 'Board'],
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('team_id');
        });

        Schema::table('boards', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('team_id');
        });
    }
};
