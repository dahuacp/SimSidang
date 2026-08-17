# AI-Assisted Submission Reading Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dosen klik "Baca dengan AI" di `/dosen/submissions/{id}` → PDF dikonversi ke Markdown → LLM lokal memberi insight rekomendasi revisi (fokus Bab 1,3,4,5) → ditampilkan di modal.

**Architecture:** Reuse `LlmProviderInterface` (FR-05) untuk LLM lokal. Service `AiSubmissionService` sebagai orchestrator PDF→Markdown→LLM→cache. Cache di `storage/app/ai-cache/`. Dua route POST + modal Alpine.

**Tech Stack:** smalot/pdfparser ^2.0, Laravel Http facade, Alpine.js, Tailwind v4.

## Global Constraints

- Bahasa Indonesia: prompt, respons, pesan error, label UI
- Cache path: `storage/app/ai-cache/{submission_id}.md` + `{submission_id}_response.json`, TTL 24 jam
- Env LLM: `ASSISTANT_LLM_URL/API_KEY/MODEL` (config `assistant.llm.*`)
- Tanpa truncation input (kirim full Markdown)
- Otorisasi: `$this->authorize('view', $submission)` (dosen harus ditugaskan ke schedule)
- Pesan error Bahasa Indonesia; pint + lint bersih sebelum selesai
- Update PRD (FR-07), GLOSSARY, ROADMAP, MEMORY

---

### Task 1: Install smalot/pdfparser

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Install package** `composer require smalot/pdfparser`
- [ ] **Step 2: Verify** `composer show smalot/pdfparser | head -1`
- [ ] **Step 3: Commit** `git add composer.json composer.lock && git commit -m "feat: add smalot/pdfparser untuk konversi PDF submission"`

### Task 2: AiSubmissionService + exception

**Files:**
- Create: `app/Services/AiSubmissionService.php`
- Create: `app/Services/AiReadServiceException.php`

Interfaces: `AiSubmissionService::analyze(Submission $s, bool $forceRefresh = false): array` returns `['summary' => string, 'suggestedPoints' => string[], 'cached' => bool, 'model' => string]`. Throws `AiReadServiceException(string $message, int $status)`.

### Task 3: Controller + FormRequest + routes

**Files:**
- Create: `app/Http/Controllers/Dosen/AiSubmissionController.php`
- Create: `app/Http/Requests/AiReadSubmissionRequest.php`
- Modify: `routes/web.php`

### Task 4: Alpine component + modal + tombol di show page

**Files:**
- Create: `resources/js/components/ai-read.js`
- Modify: `resources/js/app.js`
- Create: `resources/views/dosen/submissions/_ai-read-modal.blade.php`
- Modify: `resources/views/dosen/submissions/show.blade.php`

### Task 5: Feature tests

**Files:**
- Create: `tests/Feature/DosenAiSubmissionTest.php`

### Task 6: PRD update

**Files:**
- Modify: `docs/PRD-SIMSIDANG-v2.md` (tambah FR-07, revisi line 218 FR-05, update status line 5)

### Task 7: GLOSSARY + ROADMAP + MEMORY + full verification

**Files:**
- Modify: `docs/GLOSSARY.md`, `docs/ROADMAP.md`, `docs/MEMORY.md`
