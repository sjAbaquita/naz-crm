File Attachments & Media Handling Implementation Plan
For agentic workers: REQUIRED SUB‑SKILL: Use superpowers:subagent‑driven‑development (recommended) or superpowers:executing‑plans to implement this plan task‑by‑task. Steps use checkbox (- [ ]) syntax for tracking.

Goal

Add first‑class support for attaching files (images, PDFs, videos, etc.) to board cards, with upload, preview, download and delete capabilities, while respecting size and mime‑type constraints.

Architecture

A new attachments table stores metadata and the file path.
Laravel’s Storage façade (default public disk) handles physical files.
A dedicated AttachmentController provides RESTful endpoints used by Inertia/Vue.
Front‑end components (AttachmentList.vue, AttachmentUpload.vue) render the UI inside the card modal.
Policies enforce that only the card creator, assignee, or team admins can manage attachments.
Validation rules guarantee safe mime‑types and size limits.
Tech Stack

Laravel 11, PHP 8.3, Inertia Vue 3, Tailwind CSS, SQLite (dev) / MySQL (prod), Laravel Filesystem, spatie/laravel-medialibrary‑style conventions (but custom lightweight implementation).

Spec

docs/superpowers/plans/2026-10-24-advanced-task-management.md (the existing advanced‑task‑management spec) – this plan extends the same board‑card model with attachment fields and UI.

Global Constraints
Disk: public disk (storage/app/public) must be used; files served via Laravel’s /storage symlink.
Max file size: 10 MiB (configurable via config/filesystems.php).
Allowed mime types: image/*, application/pdf, video/*.
All routes must be protected by auth and team middleware.
All code must be covered by tests (TDD) and follow the project’s PSR‑12 coding standards.
Review Focus
Unauthorized access – non‑team members must never reach attachment endpoints.
File overwrite – uploading a file with the same original name must not overwrite existing attachments.
Storage cleanup – deleted attachments must be removed from the filesystem.
Large files – attempts to upload > 10 MiB must be rejected with a clear error.
Mime‑type spoofing – only validated mime types may be stored; content‑type checks must be enforced server‑side.
Task 1: Database – attachments table & model
Files

Create migration 2026_10_25_080000_create_attachments_table.php.
Create model app/Models/Attachment.php.
Interfaces

Consumes: none.
Produces: Attachment model with fillable ['board_card_id','filename','filepath','mime_type','size','uploaded_by'] and relationship belongsTo BoardCard.

- [ ] Write failing test: database has no `attachments` table.
- [ ] Run test (fails).
- [ ] Implement migration creating columns:
    id, board_card_id (FK), filename, filepath, mime_type, size, uploaded_by (FK users), timestamps.
- [ ] Implement `Attachment` model with `$fillable` and `boardCard()` relation.
- [ ] Run migration in test (`RefreshDatabase`) and assert table exists.
- [ ] Commit.
Task 2: API – AttachmentController (store, download, destroy)
Files

Create app/Http/Controllers/AttachmentController.php.
Interfaces

Consumes: Attachment model, StoreAttachmentRequest, DeleteAttachmentRequest.
Produces: JSON responses (201 on upload, 200 on download, 204 on delete).

- [ ] Write failing tests:
    * POST /{team}/cards/{card}/attachments (unauthenticated → 403).
    * POST with invalid mime → 422.
    * POST with valid file → 201 and DB record.
    * GET download URL returns file stream.
    * DELETE removes DB record and file.
- [ ] Implement `store()`:
    * Authorize (policy).
    * Validate request (mime & size).
    * Store file via `Storage::disk('public')->putFile('attachments', $request->file('file'))`.
    * Create `Attachment` record linking to card and uploader.
    * Return `response()->json(['attachment' => $attachment], 201)`.
- [ ] Implement `download()`:
    * Authorize view.
    * Return `Storage::disk('public')->download($attachment->filepath, $attachment->filename)`.
- [ ] Implement `destroy()`:
    * Authorize delete.
    * Delete file (`Storage::delete($attachment->filepath)`).
    * Delete DB record.
    * Return `response()->noContent()`.
- [ ] Add routes in `routes/attachments.php` and include in `RouteServiceProvider`.
- [ ] Commit.
Task 3: Form Requests & Policies
Files

app/Http/Requests/Attachment/StoreAttachmentRequest.php.
app/Policies/AttachmentPolicy.php.
Interfaces

Consumes: validation rules, user/team context.
Produces: authorized request objects.

- [ ] Write failing tests for policy:
    * non‑member cannot upload.
    * member can upload.
- [ ] Implement `StoreAttachmentRequest` with rules:
    * `file` required, file, max:10240, mimetypes:image/*,application/pdf,video/*.
- [ ] Implement `AttachmentPolicy`:
    * `view`, `create`, `delete` allowed for card creator, assignee, or admin.
- [ ] Register policy in `AuthServiceProvider`.
- [ ] Commit.
Task 4: Front‑end – Vue components
Files

resources/js/components/AttachmentUpload.vue.
resources/js/components/AttachmentList.vue.
Interfaces

Consumes: Inertia useForm, router, cardId prop.
Produces: UI for file selection, preview (image thumbnail/video icon), progress bar, list of existing attachments with download/delete actions.

- [ ] Write failing component tests (using @vue/test-utils):
    * Upload button sends POST via Inertia form.
    * Invalid file shows error.
    * Existing attachments render with download links.
    * Delete button triggers DELETE request.
- [ ] Implement `AttachmentUpload.vue`:
    * `<input type="file">` bound to FormData.
    * On submit, use `useForm().post(route('attachments.store', {team: teamSlug, card: cardId}), {preserveScroll:true, onSuccess: reset})`.
    * Show validation errors from server.
- [ ] Implement `AttachmentList.vue`:
    * Props: `attachments` array, `cardId`, `teamSlug`.
    * Render each attachment with filename, size, and icons.
    * Download via `router.visit(route('attachments.download', ...))`.
    * Delete via `router.delete(..., {onSuccess: () => emit('refresh')})`.
- [ ] Add Tailwind styling consistent with existing UI.
- [ ] Commit.
Task 5: Integrate into Card Modal (Boards/Index.vue & Tasks/Show.vue)
Files

Update resources/js/pages/boards/Index.vue (card modal).
Update resources/js/pages/tasks/Show.vue (card detail page).
Interfaces

Consumes: AttachmentUpload and AttachmentList components, new card.attachments relationship.
Produces: UI section inside the modal that shows current attachments, allows upload, and refreshes after actions.

- [ ] Write failing tests:
    * Card modal displays attachment section when card has attachments.
    * Uploading a file updates the attachment list without closing the modal.
- [ ] Modify backend `BoardCard` model to load `attachments` relationship with `->with('attachments')` in `BoardController@index` and `TaskController@show`.
- [ ] In modal markup, after the existing fields, insert:
    <AttachmentList :attachments="editingCard.attachments" :cardId="editingCard.id" :teamSlug="props.currentTeam.slug" @refresh="refreshCard"/>
    <AttachmentUpload :cardId="editingCard.id" :teamSlug="props.currentTeam.slug" @uploaded="refreshCard"/>
- [ ] Implement `refreshCard` method to reload the specific card (`router.reload({only:['card']})` or Inertia’s `router.visit` with preserve state).  
- [ ] Ensure `openCardModal` populates `editingCard.attachments` when opening.  
- [ ] Commit.
Task 6: Tests – End‑to‑end feature coverage
Files

tests/Feature/Boards/AttachmentFeatureTest.php.
Interfaces

Consumes all API endpoints, UI components via Laravel‑Dusk (or Inertia test helpers).

- [ ] Write failing feature test:
    * Create team, card, act as member.
    * Upload a valid image → assert DB record + file exists on disk.
    * Attempt oversized upload → assert validation error.
    * Download attachment → response contains file.
    * Delete attachment → DB record gone, file deleted.
- [ ] Run tests, implement missing pieces until all pass.
- [ ] Commit.
Task 7: Documentation & UI Polish
Files

Update docs/superpowers/feature-roadmap.md with new section.
Add README entry for “File Attachments & Media Handling”.
Ensure language strings for error messages are in lang/en/*.php.

- [ ] Add markdown section describing how to attach files to cards, UI screenshots, and API reference.  
- [ ] Add translation keys: `attachment.upload_failed`, `attachment.invalid_type`, `attachment.too_large`.  
- [ ] Verify all new strings appear in UI and tests.  
- [ ] Commit.