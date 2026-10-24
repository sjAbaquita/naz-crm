# Team collaboration design

## Goal

Turn the existing Calendar and Board into reliable shared, team-scoped CRM
features. Restore the automated-test database setup and replace the Dashboard
placeholders with a useful team overview.

## Scope

Each team has exactly one shared Board. Board columns and cards are therefore
scoped to a team through that Board. Calendar events are directly scoped to a
team and retain their creator for audit and display purposes.

All team members can view the team's Board and Calendar. Owners and admins can
create, edit, move, and delete Board cards and Calendar events. Members can
create Board cards and Calendar events, move cards, and reschedule events.
Members cannot edit card or event details, manage columns, or delete content.

The Board has the following additional column rules:

- Owners and admins can create, rename, reorder, and delete columns.
- Members can view columns but cannot change them.

## Data model

- Add a nullable `team_id` foreign key to `boards`; enforce one active Board
  per team with a unique index. New Boards must always have a team.
- Add a `team_id` foreign key and `created_by` foreign key to
  `calendar_events`.
- Create the Board when a team is created. The migration creates an empty Board
  for every existing team. Legacy unscoped Boards are preserved but excluded
  from every team query, avoiding an unsafe guess about their ownership.
- Keep `board_columns` and `board_cards` related through their existing Board
  hierarchy. Every query resolves content from the active team rather than by
  a globally supplied numeric identifier.

## Server behavior

- Replace the Board's unvalidated JSON endpoints with validated, team-scoped
  actions for columns, cards, and ordering updates.
- Use authorization policies for Board, column, card, and Calendar event
  operations. Routes bind models within the current team, preventing access to
  another team's records.
- Calendar creation assigns the active team and authenticated user server-side.
  Rescheduling accepts only date changes; detailed edits and deletion require
  owner or admin privileges.
- Return Inertia redirects and flash toasts for normal mutations. Validation
  errors remain attached to forms; authorization failures return Laravel's
  standard 403 response.

## Client behavior

- The Board renders one active-team Board. Adding a list/card, renaming a
  column, editing a card, deleting, and drag-and-drop ordering invoke the
  server and refresh local data from the resulting Inertia response.
- Board controls are conditionally visible from the current user's team role,
  while the server remains the source of truth for permissions.
- The Calendar shows all active-team events. Members can open the create flow
  and drag or resize an event to reschedule it; owners/admins additionally see
  edit and delete controls.
- The Dashboard shows the active team's card totals by column, upcoming events,
  and links to the Board and Calendar. Empty states explain what to create
  next.

## Testing and verification

- Re-enable `RefreshDatabase` for feature tests so the in-memory SQLite schema
  is migrated for every test.
- Add Board and Calendar feature tests for team isolation and the agreed role
  matrix, including member creation/movement/rescheduling and denied edits or
  deletion.
- Run PHP tests, TypeScript type-checking, ESLint, and a production frontend
  build. Inspect and correct any project-owned failures uncovered by these
  checks.

## Non-goals

- Multiple Boards per team.
- Assignees, due dates, activity feeds, reporting, or real-time collaboration.
- Changes to the existing authentication or team-invitation workflow beyond
  creating a team's default Board.
