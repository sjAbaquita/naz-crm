Advanced Task Management & Subtasks Implementation Plan
For agentic workers: REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

Goal: Add subtask checklists to board cards, plus parent task properties (start date, due date, priority, blocking dependencies), with a dedicated task detail page and link from the card modal.

Architecture: Extend the existing BoardCard model with new columns and relationships. Create a new Subtask model (child of BoardCard) and a self-referential BoardCardDependency model for blocking relationships. Add a SubtaskController for CRUD, update BoardCardController to enforce the "parent can't complete until all subtasks done" rule. Build a new Inertia page tasks/Show.vue for the dedicated detail view, and add a SubtaskList component inside the existing card modal.

Tech Stack: Laravel 11, Inertia.js/Vue3, TailwindCSS, SQLite

Spec: This plan implements the brainstorming session decisions: subtask checklist only; parent task gets start_date, due_date, priority (Low/Medium/High/Urgent), and blocking dependencies; parent blocked from completion until all subtasks complete; subtasks appear inside parent task; only creator can edit subtasks but creator and assignee can create subtasks; dedicated task detail page with link from modal.

Global Constraints
Laravel 11 with PHP 8 attributes for model fillable
Routes grouped under {current_team} prefix with auth, verified, EnsureTeamMembership middleware
Inertia.js for shared props and page rendering
Vue3 composition API with TypeScript
TailwindCSS for styling
SQLite for testing
Follow existing naming conventions: BoardCard, BoardColumn, Label, Team, User
Use existing actions pattern (App\Actions\Board\*) for business logic
Use existing factory pattern in database/factories/
Feature tests in tests/Feature/Boards/
TypeScript types in resources/js/types/boards.ts
Commit messages follow feat: <description> convention with Co-Authored-By attribution
Review Focus
Blocking dependency cycle prevention — A reasonable person expects that creating a circular dependency (A blocks B, B blocks A) should be rejected. Test: attempt to create a cycle and assert it returns a validation error.
Parent completion gate — A reasonable person expects that a parent card cannot be marked complete while it has incomplete subtasks. Test: attempt to complete a parent with incomplete subtasks and assert it fails.
Subtask permission boundaries — A reasonable person expects that only the subtask creator can edit/delete it, but both creator and assignee can create new subtasks. Test: attempt edit/delete by non-creator and assert forbidden; attempt create by assignee and assert success.
Team isolation of dependencies — A reasonable person expects that a card cannot depend on a card from another team. Test: attempt to create a cross-team dependency and assert it fails.
Dedicated task page access control — A reasonable person expects that only team members can view a task detail page. Test: attempt to access a task detail page as a non-member and assert forbidden.
Task 1: Database Migrations
Files:

Create: database/migrations/2026_10_24_000000_add_task_properties_to_board_cards_table.php
Create: database/migrations/2026_10_24_000001_create_subtasks_table.php
Create: database/migrations/2026_10_24_000002_create_board_card_dependencies_table.php
Interfaces:

Produces: board_cards table gains start_date, due_date, priority columns; new subtasks table (id, board_card_id, title, is_completed, position, created_by, updated_by, timestamps); new board_card_dependencies table (id, board_card_id, depends_on_card_id, timestamps)

 Step 1: Write the failing test


// tests/Feature/Boards/SubtaskMigrationTest.php
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
 Step 2: Run test to verify it fails
Run: php artisan test --filter=SubtaskMigrationTest
Expected: FAIL with "Table 'board_cards' doesn't have a column" or "Table doesn't exist"

 Step 3: Create the three migration files
Create database/migrations/2026_10_24_000000_add_task_properties_to_board_cards_table.php:


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
Create database/migrations/2026_10_24_000001_create_subtasks_table.php:


<?php

namespace Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_card_id')->constrained('board_cards')->onDelete('cascade');
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->integer('position')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['board_card_id', 'position']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('subtasks');
    }
};
Create database/migrations/2026_10_24_000002_create_board_card_dependencies_table.php:


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
 Step 4: Run migrations and tests to verify they pass
Run: php artisan migrate
Expected: All migrations complete successfully

Run: php artisan test --filter=SubtaskMigrationTest
Expected: PASS

 Step 5: Commit

git add database/migrations/
git commit -m "feat: add subtasks, dependencies, and task properties migrations"
Task 2: Eloquent Models
Files:

Modify: app/Models/BoardCard.php
Create: app/Models/Subtask.php
Create: app/Models/BoardCardDependency.php
Interfaces:

Consumes: Task 1's migrations

Produces: BoardCard gains subtasks(), dependencies(), dependents() relationships and isCompleted() accessor; Subtask model with boardCard(), creator(), updater() relationships; BoardCardDependency model with boardCard(), dependsOnCard() relationships

 Step 1: Write the failing test


// tests/Feature/Boards/SubtaskModelTest.php
test('board card has subtasks relationship', function () {
    $card = BoardCard::factory()->create();
    Subtask::factory()->create(['board_card_id' => $card->id]);

    expect($card->subtasks)->toHaveCount(1);
});

test('board card has blocking dependencies relationship', function () {
    $card = BoardCard::factory()->create();
    $blockingCard = BoardCard::factory()->create();
    $card->dependencies()->create(['depends_on_card_id' => $blockingCard->id]);

    expect($card->dependencies)->toHaveCount(1)
        ->and($card->dependencies->first()->depends_on_card_id)->toBe($blockingCard->id);
});

test('subtask model has board card relationship', function () {
    $card = BoardCard::factory()->create();
    $subtask = Subtask::factory()->create(['board_card_id' => $card->id]);

    expect($subtask->boardCard->id)->toBe($card->id);
});
 Step 2: Run test to verify it fails
Run: php artisan test --filter=SubtaskModelTest
Expected: FAIL with "Class Subtask not found" or "undefined method"

 Step 3: Create Subtask and BoardCardDependency models; update BoardCard
Create app/Models/Subtask.php:


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['board_card_id', 'title', 'is_completed', 'position', 'created_by', 'updated_by'])]
class Subtask extends Model
{
    /** @use HasFactory<SubtaskFactory> */
    use HasFactory;

    public function boardCard(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
Create app/Models/BoardCardDependency.php:


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['board_card_id', 'depends_on_card_id'])]
class BoardCardDependency extends Model
{
    /** @use HasFactory<BoardCardDependencyFactory> */
    use HasFactory;

    public function boardCard(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class);
    }

    public function dependsOnCard(): BelongsTo
    {
        return $this->belongsTo(BoardCard::class, 'depends_on_card_id');
    }
}
Modify app/Models/BoardCard.php — add imports and relationships:


use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

// Add to Fillable:
#[Fillable(['column_id', 'title', 'description', 'position', 'assigned_to', 'start_date', 'due_date', 'priority'])]

// Add methods after labels():
public function subtasks(): HasMany
{
    return $this->hasMany(Subtask::class)->orderBy('position');
}

public function dependencies(): BelongsToMany
{
    return $this->belongsToMany(BoardCardDependency::class, 'board_card_dependencies', 'board_card_id', 'depends_on_card_id');
}

public function dependents(): HasMany
{
    return $this->hasMany(BoardCardDependency::class, 'board_card_id');
}

public function blockingCards()
{
    return $this->belongsToMany(BoardCard::class, 'board_card_dependencies', 'board_card_id', 'depends_on_card_id');
}

/**
 * Determine if the card can be marked complete.
 */
public function getIsCompletableAttribute(): bool
{
    return $this->subtasks()->where('is_completed', false)->doesNotExist();
}
 Step 4: Run test to verify it passes
Run: php artisan test --filter=SubtaskModelTest
Expected: PASS

 Step 5: Commit

git add app/Models/
git commit -m "feat: add Subtask and BoardCardDependency models with relationships"
Task 3: Actions
Files:

Create: app/Actions/Subtask/CreateSubtask.php
Create: app/Actions/Subtask/UpdateSubtask.php
Create: app/Actions/Subtask/DeleteSubtask.php
Create: app/Actions/Board/CompleteBoardCard.php
Interfaces:

Consumes: Task 2's models

Produces: CreateSubtask::handle(BoardCard $card, array $data, User $user): Subtask, UpdateSubtask::handle(Subtask $subtask, array $data, User $user): Subtask, DeleteSubtask::handle(Subtask $subtask): void, CompleteBoardCard::handle(BoardCard $card, User $user): void

 Step 1: Write the failing test


// tests/Feature/Boards/SubtaskActionTest.php
test('create subtask action creates subtask with correct creator', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $card = BoardCard::factory()->create();
    $subtask = app(CreateSubtask::class)->handle($card, ['title' => 'Test subtask'], $user);

    expect($subtask->title)->toBe('Test subtask')
        ->and($subtask->created_by)->toBe($user->id);
});

test('complete board card action fails when subtasks incomplete', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $card = BoardCard::factory()->create();
    Subtask::factory()->create(['board_card_id' => $card->id, 'is_completed' => false]);

    expect(fn() => app(CompleteBoardCard::class)->handle($card, $user))
        ->toThrow(Exception::class);
});
 Step 2: Run test to verify it fails
Run: php artisan test --filter=SubtaskActionTest
Expected: FAIL with "Class CreateSubtask not found"

 Step 3: Create the action classes
Create app/Actions/Subtask/CreateSubtask.php:


<?php

namespace App\Actions\Subtask;

use App\Models\Subtask;
use App\Models\User;

class CreateSubtask
{
    public function handle(\App\Models\BoardCard $card, array $data, User $user): Subtask
    {
        $maxPosition = $card->subtasks()->max('position') ?? -1;

        return Subtask::create([
            ...$data,
            'board_card_id' => $card->id,
            'position' => $maxPosition + 1,
            'created_by' => $user->id,
        ]);
    }
}
Create app/Actions/Subtask/UpdateSubtask.php:


<?php

namespace App\Actions\Subtask;

use App\Models\Subtask;
use App\Models\User;

class UpdateSubtask
{
    public function handle(Subtask $subtask, array $data, User $user): Subtask
    {
        $subtask->update([
            ...$data,
            'updated_by' => $user->id,
        ]);

        return $subtask;
    }
}
Create app/Actions/Subtask/DeleteSubtask.php:


<?php

namespace App\Actions\Subtask;

use App\Models\Subtask;

class DeleteSubtask
{
    public function handle(Subtask $subtask): void
    {
        $subtask->delete();
    }
}
Create app/Actions/Board/CompleteBoardCard.php:


<?php

namespace App\Actions\Board;

use App\Models\BoardCard;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CompleteBoardCard
{
    public function handle(BoardCard $card, User $user): void
    {
        if ($card->subtasks()->where('is_completed', false)->exists()) {
            throw new \Exception('Cannot complete card with incomplete subtasks.');
        }

        $card->update(['is_completed' => true]);
    }
}
 Step 4: Run test to verify it passes
Run: php artisan test --filter=SubtaskActionTest
Expected: PASS

 Step 5: Commit

git add app/Actions/
git commit -m "feat: add subtask and card completion actions"
Task 4: Factories
Files:

Create: database/factories/SubtaskFactory.php
Create: database/factories/BoardCardDependencyFactory.php
Modify: database/factories/BoardCardFactory.php
Interfaces:

Consumes: Task 2's models

Produces: SubtaskFactory, BoardCardDependencyFactory, updated BoardCardFactory with new fields

 Step 1: Write the failing test


// tests/Feature/Boards/SubtaskFactoryTest.php
test('subtask factory creates valid subtask', function () {
    $subtask = Subtask::factory()->create();

    expect($subtask)->toBeInstanceOf(Subtask::class)
        ->and($subtask->title)->not->toBeEmpty();
});
 Step 2: Run test to verify it fails
Run: php artisan test --filter=SubtaskFactoryTest
Expected: FAIL with "Class SubtaskFactory not found"

 Step 3: Create the factories
Create database/factories/SubtaskFactory.php:


<?php

namespace Database\Factories;

use App\Models\BoardCard;
use App\Models\Subtask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subtask>
 */
class SubtaskFactory extends Factory
{
    protected $model = Subtask::class;

    public function definition(): array
    {
        return [
            'board_card_id' => BoardCard::factory(),
            'title' => fake()->sentence(),
            'is_completed' => fake()->boolean(),
            'position' => fake()->numberBetween(0, 10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
Create database/factories/BoardCardDependencyFactory.php:


<?php

namespace Database\Factories;

use App\Models\BoardCard;
use App\Models\BoardCardDependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoardCardDependency>
 */
class BoardCardDependencyFactory extends Factory
{
    protected $model = BoardCardDependency::class;

    public function definition(): array
    {
        return [
            'board_card_id' => BoardCard::factory(),
            'depends_on_card_id' => BoardCard::factory(),
        ];
    }
}
Modify database/factories/BoardCardFactory.php — add new fields:


// Add to definition array:
'start_date' => fake()->optional()->date(),
'due_date' => fake()->optional()->date(),
'priority' => fake()->randomElement(['Low', 'Medium', 'High', 'Urgent']),
 Step 4: Run test to verify it passes
Run: php artisan test --filter=SubtaskFactoryTest
Expected: PASS

 Step 5: Commit

git add database/factories/
git commit -m "feat: add Subtask and BoardCardDependency factories"
Task 5: Controllers
Files:

Create: app/Http/Controllers/SubtaskController.php
Modify: app/Http/Controllers/BoardCardController.php
Create: app/Http/Controllers/TaskController.php
Interfaces:

Consumes: Task 2's models, Task 3's actions

Produces: SubtaskController (index, store, update, destroy), updated BoardCardController (show method for task detail page), TaskController (show)

 Step 1: Write the failing test


// tests/Feature/Boards/SubtaskControllerTest.php
test('assignee can create subtask for card', function () {
    [$team, $assignee] = teamUser(TeamRole::Member);
    $card = BoardCard::factory()->create(['assigned_to' => $assignee->id]);

    $this->actingAs($assignee)
        ->post(route('subtasks.store', [$team, $card]), [
            'title' => 'New subtask',
        ])
        ->assertCreated();

    $this->assertDatabaseHas('subtasks', [
        'board_card_id' => $card->id,
        'title' => 'New subtask',
        'created_by' => $assignee->id,
    ]);
});

test('non-creator cannot edit subtask', function () {
    [$team, $creator] = teamUser(TeamRole::Owner);
    [$team2, $otherUser] = teamUser(TeamRole::Member);
    $card = BoardCard::factory()->create();
    $subtask = Subtask::factory()->create([
        'board_card_id' => $card->id,
        'created_by' => $creator->id,
    ]);

    $this->actingAs($otherUser)
        ->put(route('subtasks.update', [$team, $card, $subtask]), [
            'title' => 'Hacked',
        ])
        ->assertForbidden();
});
 Step 2: Run test to verify it fails
Run: php artisan test --filter=SubtaskControllerTest
Expected: FAIL with "Route [subtasks.store] not defined"

 Step 3: Create the controllers
Create app/Http/Controllers/SubtaskController.php:


<?php

namespace App\Http\Controllers;

use App\Actions\Subtask\CreateSubtask;
use App\Actions\Subtask\DeleteSubtask;
use App\Actions\Subtask\UpdateSubtask;
use App\Http\Requests\StoreSubtaskRequest;
use App\Http\Requests\UpdateSubtaskRequest;
use App\Models\BoardCard;
use App\Models\Subtask;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class SubtaskController extends Controller
{
    public function index(Request $request, Team $currentTeam, BoardCard $card)
    {
        Gate::authorize('view', $card);

        $subtasks = $card->subtasks()
            ->with('creator:id,name', 'updater:id,name')
            ->get();

        return response()->json(['subtasks' => $subtasks]);
    }

    public function store(StoreSubtaskRequest $request, Team $currentTeam, BoardCard $card, CreateSubtask $createSubtask)
    {
        Gate::authorize('create', [Subtask::class, $card]);

        $subtask = $createSubtask->handle($card, $request->validated(), $request->user());

        return response()->json(['subtask' => $subtask->load('creator:id,name')], 201);
    }

    public function update(UpdateSubtaskRequest $request, Team $currentTeam, BoardCard $card, Subtask $subtask, UpdateSubtask $updateSubtask)
    {
        Gate::authorize('update', $subtask);

        $subtask = $updateSubtask->handle($subtask, $request->validated(), $request->user());

        return response()->json(['subtask' => $subtask->load('creator:id,name', 'updater:id,name')]);
    }

    public function destroy(Team $currentTeam, BoardCard $card, Subtask $subtask, DeleteSubtask $deleteSubtask)
    {
        Gate::authorize('delete', $subtask);

        $deleteSubtask->handle($subtask);

        return response()->json(['message' => 'Subtask deleted']);
    }
}
Create app/Http/Controllers/TaskController.php:


<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function show(Request $request, Team $currentTeam, BoardCard $card): Response
    {
        $role = $request->user()->teamRole($currentTeam);

        $card->load([
            'subtasks' => fn ($q) => $q->orderBy('position')->with('creator:id,name', 'updater:id,name'),
            'labels',
            'assignee:id,name,email',
            'dependencies' => fn ($q) => $q->with('labels'),
            'column.board.team',
        ]);

        return Inertia::render('tasks/Show', [
            'card' => $card,
            'team' => $currentTeam,
            'permissions' => [
                'canEditCards' => $role?->isAtLeast(TeamRole::Admin) ?? false,
                'canManageSubtasks' => $request->user()->id === $card->created_by || $request->user()->id === $card->assigned_to,
            ],
        ]);
    }
}
Modify app/Http/Controllers/BoardCardController.php — add show method:


// Add use statement:
use App\Actions\Board\CompleteBoardCard;

// Add method:
public function show(Request $request, Team $currentTeam, int $id): Response
{
    $card = $this->card($currentTeam, $id);
    $role = $request->user()->teamRole($currentTeam);

    $card->load([
        'subtasks' => fn ($q) => $q->orderBy('position')->with('creator:id,name', 'updater:id,name'),
        'labels',
        'assignee:id,name,email',
        'dependencies' => fn ($q) => $q->with('labels'),
        'column.board.team',
    ]);

    return Inertia::render('tasks/Show', [
        'card' => $card,
        'team' => $currentTeam,
        'permissions' => [
            'canEditCards' => $role?->isAtLeast(TeamRole::Admin) ?? false,
            'canManageSubtasks' => $request->user()->id === $card->created_by || $request->user()->id === $card->assigned_to,
        ],
    ]);
}
 Step 4: Run test to verify it passes
Run: php artisan test --filter=SubtaskControllerTest
Expected: PASS

 Step 5: Commit

git add app/Http/Controllers/
git commit -m "feat: add SubtaskController and TaskController"
Task 6: Routes
Files:

Modify: routes/boards.php
Interfaces:

Consumes: Task 5's controllers

Produces: New routes for subtasks and task detail page

 Step 1: Write the failing test


// tests/Feature/Boards/RouteTest.php
test('subtask routes are registered', function () {
    expect(route('subtasks.store', [1, 1]))->not->toBeNull();
    expect(route('tasks.show', [1, 1]))->not->toBeNull();
});
 Step 2: Run test to verify it fails
Run: php artisan test --filter=RouteTest
Expected: FAIL with "Route [subtasks.store] not defined"

 Step 3: Modify routes/boards.php
Add the following routes after the cards routes:


// Subtask routes
Route::get('/cards/{card}/subtasks', [SubtaskController::class, 'index'])->name('subtasks.index');
Route::post('/cards/{card}/subtasks', [SubtaskController::class, 'store'])->name('subtasks.store');
Route::put('/cards/{card}/subtasks/{subtask}', [SubtaskController::class, 'update'])->name('subtasks.update');
Route::delete('/cards/{card}/subtasks/{subtask}', [SubtaskController::class, 'destroy'])->name('subtasks.destroy');

// Task detail page
Route::get('/cards/{card}', [TaskController::class, 'show'])->name('tasks.show');
 Step 4: Run test to verify it passes
Run: php artisan test --filter=RouteTest
Expected: PASS

 Step 5: Commit

git add routes/
git commit -m "feat: add subtask and task detail routes"
Task 7: TypeScript Types
Files:

Modify: resources/js/types/boards.ts
Interfaces:

Consumes: Task 2's models

Produces: Updated BoardCard type with new fields; new Subtask type

 Step 1: Write the failing test


// resources/js/types/boards.ts test (run via npm run types:check)
test('BoardCard type includes new task properties', () => {
    const card: BoardCard = {
        id: 1,
        column_id: 1,
        title: 'Test',
        description: null,
        position: 0,
        assigned_to: null,
        start_date: '2026-10-24',
        due_date: '2026-10-31',
        priority: 'High',
        subtasks: [],
        dependencies: [],
    };
    expect(card).toBeDefined();
});
 Step 2: Run type check to verify it fails
Run: npm run types:check
Expected: FAIL with "Property 'start_date' does not exist on type 'BoardCard'"

 Step 3: Modify resources/js/types/boards.ts
Add the following types:


export type Subtask = {
    id: number;
    board_card_id: number;
    title: string;
    is_completed: boolean;
    position: number;
    created_by: number | null;
    updated_by: number | null;
    creator?: { id: number; name: string } | null;
    updater?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
};

export type BoardCardDependency = {
    id: number;
    board_card_id: number;
    depends_on_card_id: number;
    created_at: string;
    updated_at: string;
    depends_on_card?: BoardCard;
};

// Update BoardCard type:
export type BoardCard = {
    id: number;
    column_id: number;
    title: string;
    description: string | null;
    position: number;
    assigned_to: number | null;
    assignee?: UserMinimal | null;
    labels?: Label[];
    start_date?: string | null;
    due_date?: string | null;
    priority?: 'Low' | 'Medium' | 'High' | 'Urgent';
    subtasks?: Subtask[];
    dependencies?: BoardCardDependency[];
    is_completable?: boolean;
};
 Step 4: Run type check to verify it passes
Run: npm run types:check
Expected: PASS

 Step 5: Commit

git add resources/js/types/
git commit -m "feat: add Subtask and BoardCardDependency TypeScript types"
Task 8: Vue Components
Files:

Create: resources/js/components/SubtaskList.vue
Create: resources/js/pages/tasks/Show.vue
Modify: resources/js/pages/boards/Index.vue
Interfaces:

Consumes: Task 7's types, Task 5's controllers

Produces: SubtaskList component for inline subtask management, tasks/Show.vue page for dedicated task detail, updated Index.vue with subtask list in modal and link to task page

 Step 1: Write the failing test


// tests/Feature/Boards/SubtaskVueComponentTest.php (conceptual)
test('subtask list renders correctly', function () {
    // This would test the component renders with given props
});
 Step 2: Run test to verify it fails
Run: npm run types:check
Expected: FAIL with "Cannot find module SubtaskList.vue"

 Step 3: Create the Vue components
Create resources/js/components/SubtaskList.vue:


<script setup lang="ts">
import { Plus, Trash2, Check, Pencil, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

interface Props {
    cardId: number;
    subtasks: {
        id: number;
        title: string;
        is_completed: boolean;
        creator?: { id: number; name: string } | null;
        updater?: { id: number; name: string } | null;
    }[];
    canCreate: boolean;
    canEdit: (subtask: any) => boolean;
    teamSlug: string;
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const showAddForm = ref(false);
const newSubtaskForm = useForm({
    title: '',
});

const editingId = ref<number | null>(null);
const editForm = useForm({
    title: '',
});

const addSubtask = () => {
    newSubtaskForm.post(`/teams/${props.teamSlug}/cards/${props.cardId}/subtasks`, {
        preserveScroll: true,
        onSuccess: () => {
            newSubtaskForm.reset();
            showAddForm.value = false;
        },
    });
};

const startEdit = (subtask: any) => {
    editingId.value = subtask.id;
    editForm.title = subtask.title;
};

const saveEdit = (subtask: any) => {
    editForm.put(`/teams/${props.teamSlug}/cards/${props.cardId}/subtasks/${subtask.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
};

const toggleComplete = (subtask: any) => {
    router.patch(`/teams/${props.teamSlug}/cards/${props.cardId}/subtasks/${subtask.id}`, {
        is_completed: !subtask.is_completed,
    }, {
        preserveScroll: true,
    });
};

const deleteSubtask = (subtask: any) => {
    if (confirm('Are you sure?')) {
        router.delete(`/teams/${props.teamSlug}/cards/${props.cardId}/subtasks/${subtask.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Subtasks ({{ subtasks.filter(s => s.is_completed).length }}/{{ subtasks.length }})
            </h4>
            <button
                v-if="canCreate"
                @click="showAddForm = !showAddForm"
                class="text-xs text-blue-600 hover:text-blue-700"
            >
                <Plus class="w-3 h-3 inline" /> Add
            </button>
        </div>

        <div v-if="showAddForm" class="flex gap-2">
            <input
                v-model="newSubtaskForm.title"
                @keyup.enter="addSubtask"
                placeholder="Subtask title..."
                class="flex-1 px-2 py-1 text-sm bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded"
            />
            <button
                @click="addSubtask"
                :disabled="newSubtaskForm.processing || !newSubtaskForm.title.trim()"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
            >
                Add
            </button>
        </div>

        <ul class="space-y-1">
            <li
                v-for="subtask in subtasks"
                :key="subtask.id"
                class="flex items-center gap-2 group"
            >
                <button
                    @click="toggleComplete(subtask)"
                    class="flex-shrink-0"
                >
                    <Check
                        v-if="subtask.is_completed"
                        class="w-4 h-4 text-green-600"
                    />
                    <div
                        v-else
                        class="w-4 h-4 border-2 border-gray-300 dark:border-gray-600 rounded"
                    />
                </button>

                <div v-if="editingId === subtask.id" class="flex-1 flex gap-2">
                    <input
                        v-model="editForm.title"
                        @keyup.enter="saveEdit(subtask)"
                        @keyup.esc="editingId = null"
                        class="flex-1 px-2 py-1 text-sm bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded"
                        autofocus
                    />
                    <button
                        @click="saveEdit(subtask)"
                        class="px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Save
                    </button>
                    <button
                        @click="editingId = null"
                        class="px-2 py-1 text-sm bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <span
                    v-else
                    :class="['flex-1 text-sm', subtask.is_completed ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300']"
                >
                    {{ subtask.title }}
                </span>

                <div v-if="canEdit(subtask)" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                        @click="startEdit(subtask)"
                        class="p-1 text-gray-400 hover:text-blue-600"
                    >
                        <Pencil class="w-3 h-3" />
                    </button>
                    <button
                        @click="deleteSubtask(subtask)"
                        class="p-1 text-gray-400 hover:text-red-600"
                    >
                        <Trash2 class="w-3 h-3" />
                    </button>
                </div>
            </li>
        </ul>
    </div>
</template>
Create resources/js/pages/tasks/Show.vue:


<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SubtaskList from '@/components/SubtaskList.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Flag, Link, User, ArrowLeft } from 'lucide-vue-next';

interface Props {
    card: {
        id: number;
        title: string;
        description: string | null;
        start_date: string | null;
        due_date: string | null;
        priority: 'Low' | 'Medium' | 'High' | 'Urgent';
        subtasks: Array<{
            id: number;
            title: string;
            is_completed: boolean;
            creator?: { id: number; name: string } | null;
            updater?: { id: number; name: string } | null;
        }>;
        dependencies: Array<{
            id: number;
            title: string;
            priority: string;
        }>;
        assignee?: { id: number; name: string } | null;
        labels?: Array<{ id: number; name: string; color: string }>;
    };
    team: { slug: string };
    permissions: {
        canEditCards: boolean;
        canManageSubtasks: boolean;
    };
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = computed(() => page.props.auth.user);

const priorityColors = {
    Low: 'bg-gray-100 text-gray-800',
    Medium: 'bg-blue-100 text-blue-800',
    High: 'bg-orange-100 text-orange-800',
    Urgent: 'bg-red-100 text-red-800',
};

const canEditSubtask = (subtask: any) => {
    return subtask.created_by === currentUser.value.id;
};

const goBack = () => {
    router.visit(`/teams/${props.team.slug}/board`);
};
</script>

<template>
    <AppLayout>
        <Head :title="card.title" />
        
        <div class="max-w-4xl mx-auto p-6 space-y-6">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ArrowLeft class="w-5 h-5" />
                </Button>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ card.title }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <Badge :class="priorityColors[card.priority]">
                            <Flag class="w-3 h-3 mr-1" />
                            {{ card.priority }}
                        </Badge>
                        <span v-if="card.due_date" class="text-sm text-gray-500">
                            <Calendar class="w-3 h-3 inline mr-1" />
                            Due {{ new Date(card.due_date).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                {{ card.description || 'No description' }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Subtasks</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <SubtaskList
                                :cardId="card.id"
                                :subtasks="card.subtasks"
                                :canCreate="permissions.canManageSubtasks"
                                :canEdit="canEditSubtask"
                                :teamSlug="team.slug"
                            />
                        </CardContent>
                    </Card>

                    <Card v-if="card.dependencies && card.dependencies.length > 0">
                        <CardHeader>
                            <CardTitle>Blocking Dependencies</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2">
                                <li
                                    v-for="dep in card.dependencies"
                                    :key="dep.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <Link class="w-4 h-4 text-gray-400" />
                                    <span>{{ dep.title }}</span>
                                    <Badge :class="priorityColors[dep.priority]" class="text-xs">
                                        {{ dep.priority }}
                                    </Badge>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-if="card.assignee" class="flex items-center gap-2">
                                <User class="w-4 h-4 text-gray-400" />
                                <span class="text-sm">Assigned to {{ card.assignee.name }}</span>
                            </div>
                            <div v-if="card.start_date" class="flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-gray-400" />
                                <span class="text-sm">Start {{ new Date(card.start_date).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="card.labels && card.labels.length > 0">
                                <span class="text-sm text-gray-500">Labels:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span
                                        v-for="label in card.labels"
                                        :key="label.id"
                                        class="px-2 py-0.5 rounded text-xs"
                                        :style="{ backgroundColor: label.color, color: '#fff' }"
                                    >
                                        {{ label.name }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
Modify resources/js/pages/boards/Index.vue — add subtask list in modal and link to task page:

Add SubtaskList import
Add subtask list component in modal
Add "View details" link in card display
 Step 4: Run tests to verify they pass
Run: npm run types:check
Expected: PASS

Run: php artisan test --filter=SubtaskControllerTest
Expected: PASS

 Step 5: Commit

git add resources/
git commit -m "feat: add SubtaskList component and task detail page"
Task 9: Feature Tests
Files:

Create: tests/Feature/Boards/SubtaskFeatureTest.php
Create: tests/Feature/Boards/TaskDetailPageTest.php
Create: tests/Feature/Boards/BlockingDependencyTest.php
Interfaces:

Consumes: All previous tasks

Produces: Comprehensive feature tests covering the full feature

 Step 1: Write the failing test


// tests/Feature/Boards/SubtaskFeatureTest.php
test('full subtask workflow: create, edit, complete, delete', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $card = BoardCard::factory()->create();

    // Create
    $this->actingAs($user)
        ->post(route('subtasks.store', [$team, $card]), ['title' => 'Task 1'])
        ->assertCreated();

    $subtask = Subtask::where('title', 'Task 1')->first();

    // Edit
    $this->actingAs($user)
        ->put(route('subtasks.update', [$team, $card, $subtask]), ['title' => 'Renamed task'])
        ->assertOk();

    // Complete
    $this->actingAs($user)
        ->patch(route('subtasks.update', [$team, $card, $subtask]), ['is_completed' => true])
        ->assertOk();

    // Delete
    $this->actingAs($user)
        ->delete(route('subtasks.destroy', [$team, $card, $subtask]))
        ->assertOk();

    $this->assertDatabaseMissing('subtasks', ['id' => $subtask->id]);
});
 Step 2: Run test to verify it fails
Run: php artisan test --filter=SubtaskFeatureTest
Expected: FAIL with "Route [subtasks.store] not defined"

 Step 3: Create the test files
Create tests/Feature/Boards/SubtaskFeatureTest.php:


<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\Subtask;
use App\Models\Team;
use App\Models\User;

test('full subtask workflow: create, edit, complete, delete', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $card = BoardCard::factory()->create();

    $this->actingAs($user)
        ->post(route('subtasks.store', [$team, $card]), ['title' => 'Task 1'])
        ->assertCreated();

    $subtask = Subtask::where('title', 'Task 1')->first();

    $this->actingAs($user)
        ->put(route('subtasks.update', [$team, $card, $subtask]), ['title' => 'Renamed task'])
        ->assertOk();

    $this->actingAs($user)
        ->patch(route('subtasks.update', [$team, $card, $subtask]), ['is_completed' => true])
        ->assertOk();

    $this->actingAs($user)
        ->delete(route('subtasks.destroy', [$team, $card, $subtask]))
        ->assertOk();

    $this->assertDatabaseMissing('subtasks', ['id' => $subtask->id]);
});

test('assignee can create subtask but cannot edit others subtasks', function () {
    [$team, $assignee] = teamUser(TeamRole::Member);
    [$team2, $creator] = teamUser(TeamRole::Owner);
    $card = BoardCard::factory()->create(['assigned_to' => $assignee->id]);
    $subtask = Subtask::factory()->create([
        'board_card_id' => $card->id,
        'created_by' => $creator->id,
    ]);

    // Assignee can create
    $this->actingAs($assignee)
        ->post(route('subtasks.store', [$team, $card]), ['title' => 'New subtask'])
        ->assertCreated();

    // Assignee cannot edit creator's subtask
    $this->actingAs($assignee)
        ->put(route('subtasks.update', [$team, $card, $subtask]), ['title' => 'Hacked'])
        ->assertForbidden();

    // Assignee cannot delete creator's subtask
    $this->actingAs($assignee)
        ->delete(route('subtasks.destroy', [$team, $card, $subtask]))
        ->assertForbidden();
});

test('parent card cannot be completed with incomplete subtasks', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $card = BoardCard::factory()->create();
    Subtask::factory()->create(['board_card_id' => $card->id, 'is_completed' => false]);

    $this->actingAs($user)
        ->patch(route('cards.update', [$team, $card]), ['is_completed' => true])
        ->assertSessionHasErrors();
});

test('parent card can be completed when all subtasks are complete', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $card = BoardCard::factory()->create();
    Subtask::factory()->create(['board_card_id' => $card->id, 'is_completed' => true]);

    $this->actingAs($user)
        ->patch(route('cards.update', [$team, $card]), ['is_completed' => true])
        ->assertRedirect();
});
Create tests/Feature/Boards/TaskDetailPageTest.php:


<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\Team;
use App\Models\User;

test('team member can view task detail page', function () {
    [$team, $user] = teamUser(TeamRole::Member);
    $card = BoardCard::factory()->create();

    $this->actingAs($user)
        ->get(route('tasks.show', [$team, $card]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('tasks/Show')
            ->where('card.id', $card->id)
        );
});

test('non-member cannot view task detail page', function () {
    [$team, $user] = teamUser(TeamRole::Member);
    $otherUser = User::factory()->create();
    $card = BoardCard::factory()->create();

    $this->actingAs($otherUser)
        ->get(route('tasks.show', [$team, $card]))
        ->assertForbidden();
});
Create tests/Feature/Boards/BlockingDependencyTest.php:


<?php

use App\Enums\TeamRole;
use App\Models\BoardCard;
use App\Models\BoardCardDependency;
use App\Models\Team;
use App\Models\User;

test('can create blocking dependency between cards', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $cardA = BoardCard::factory()->create();
    $cardB = BoardCard::factory()->create();

    $this->actingAs($user)
        ->post(route('cards.dependencies.store', [$team, $cardA]), [
            'depends_on_card_id' => $cardB->id,
        ])
        ->assertCreated();

    $this->assertDatabaseHas('board_card_dependencies', [
        'board_card_id' => $cardA->id,
        'depends_on_card_id' => $cardB->id,
    ]);
});

test('cannot create circular dependency', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    $cardA = BoardCard::factory()->create();
    $cardB = BoardCard::factory()->create();

    // A depends on B
    BoardCardDependency::create([
        'board_card_id' => $cardA->id,
        'depends_on_card_id' => $cardB->id,
    ]);

    // Try B depends on A (circular)
    $this->actingAs($user)
        ->post(route('cards.dependencies.store', [$team, $cardB]), [
            'depends_on_card_id' => $cardA->id,
        ])
        ->assertSessionHasErrors();
});

test('cannot create cross-team dependency', function () {
    [$team, $user] = teamUser(TeamRole::Owner);
    [$team2, $otherUser] = teamUser(TeamRole::Owner);
    $cardA = BoardCard::factory()->create();
    $cardB = BoardCard::factory()->create();

    $this->actingAs($otherUser)
        ->post(route('cards.dependencies.store', [$team2, $cardA]), [
            'depends_on_card_id' => $cardB->id,
        ])
        ->assertForbidden();
});
 Step 4: Run tests to verify they pass
Run: php artisan test --filter=SubtaskFeatureTest
Expected: PASS

Run: php artisan test --filter=TaskDetailPageTest
Expected: PASS

Run: php artisan test --filter=BlockingDependencyTest
Expected: PASS

 Step 5: Commit

git add tests/
git commit -m "feat: add comprehensive subtask and dependency feature tests"
Task 10: Final Integration & Polish
Files:

Modify: resources/js/pages/boards/Index.vue (add link to task detail page from card)
Modify: app/Http/Controllers/BoardController.php (ensure new fields are loaded)
Verify: npm run types:check passes
Verify: php artisan test passes
Interfaces:

Consumes: All previous tasks

Produces: A fully integrated feature

 Step 1: Update BoardController to load new relationships

Modify app/Http/Controllers/BoardController.php:


$board = $currentTeam->board()
    ->with([
        'columns' => fn ($q) => $q->orderBy('position'),
        'columns.cards' => fn ($q) => $q->orderBy('position')
            ->with('assignee:id,name,email', 'labels', 'subtasks', 'dependencies'),
    ])
    ->firstOrFail();
 Step 2: Update Index.vue to add link to task detail page
Add a "View details" link in the card display:


<div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-600">
    <div v-if="card.labels && card.labels.length > 0" class="flex items-center gap-1">
        <span v-for="label in card.labels" :key="label.id" class="w-2 h-2 rounded-full inline-block" :style="{ background: label.color }" />
        <span v-for="label in card.labels" :key="`label-${label.id}`" class="text-xs font-medium text-gray-500 dark:text-gray-400 ml-1">{{ label.name }}</span>
    </div>
    <Link :href="route('tasks.show', { current_team: props.currentTeam.slug, card: card.id })" class="text-xs text-blue-600 hover:text-blue-700">
        View details
    </Link>
</div>
 Step 3: Run full test suite
Run: php artisan test
Expected: All tests pass

Run: npm run types:check
Expected: PASS

 Step 4: Commit

git add app/Http/Controllers/BoardController.php resources/js/pages/boards/Index.vue
git commit -m "feat: integrate subtasks into board view and task detail page"