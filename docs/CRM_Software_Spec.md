# Software Spec — CRM System (Enquiry Management) — v1.2

> **Stack:** Laravel (existing Blade) + Livewire 3 + Alpine.js + Laravel Reverb (WebSocket) + Laravel Echo + spatie/laravel-permission + Laravel Sanctum
> **Scope:** Add Role/Permission, Assignment + Notification (realtime + email), Activity Tracking + KPI, redesigned `/enquiry` `/gis-enquiry` pages (filter, soft delete, bulk delete, assign), and a new User Management page.
> **Database:** `enquiries`, `gis_enquiries` (existing — extended via new migrations)

### Changelog v1.2
| # | Added / Changed | Rationale |
|---|---|---|
| 1 | Added **Section 2.4: User Management page** — root/ceo/gm can create users limited to `admin`, `sale`, `sale_manager` roles | New requirement |
| 2 | Split `user.manage` into `user.view / user.create / user.update / user.deactivate`, granted to **gm** as well | Original spec didn't give gm user-management rights |
| 3 | Enforced **server-side role whitelist** — ceo/gm cannot create or promote a user to `root`, `ceo`, or `general_manager` | Prevent privilege escalation |
| 4 | Use **invitation link** (user sets their own password) instead of admin-typed password, and **deactivate** instead of hard delete | Better security + preserves KPI history |

### Changelog v1.1
| # | Added / Changed | Rationale |
|---|---|---|
| 1 | Added **Section 10: Full Security Spec** (AuthN, AuthZ, Input/Output, Public form, API token, Realtime, Audit, PDPA, Infra) | Original spec had only a short summary |
| 2 | **Stored XSS** — enquiry data comes from a public form = untrusted input, must be escaped everywhere | Highest-risk vector in this system |
| 3 | **Filter/Sort whitelist** in Filter API | Prevent SQL injection / data leaks via `sort`, `filter` params |
| 4 | **Server-side validation of assign target** (must actually hold sale/sale_manager role) | Prevent privilege abuse via direct API calls |
| 5 | Added **public form protections** (rate limit, honeypot/captcha) | Prevent lead-spam flooding + email flooding |
| 6 | Audit log must be **append-only** + enforce `Relation::enforceMorphMap()` | Mutable logs can't back KPI evidence |
| 7 | Added **PDPA** requirements (enquiry data = personal data under Thai law) | Legal compliance |
| 8 | Added **Section 11: Technical caveats** — queue worker, assign concurrency, indexes, timezone | Common production gotchas |
| 9 | Reassign → also notify the previous assignee + cap bulk delete at 100 records/call | Close functional gaps |
| 10 | Broadcast payloads carry minimal data (id + short label) — no full PII over socket | Reduce exposure of personal data |

---

## 1. Architecture Overview

| Layer | Technology | Rationale |
|---|---|---|
| UI | Blade + **Livewire 3** | Reactive table / assign dropdown / bulk-select / live notification without an SPA or build step |
| Micro-interaction | **Alpine.js** | Client-side checkbox multi-select, dropdowns, modals |
| Realtime | **Laravel Reverb** + **Laravel Echo** | First-party Laravel WebSocket server; serves both web and future mobile app |
| Role/Permission | **spatie/laravel-permission** | Laravel standard, complete role + permission + Gate integration |
| API auth (mobile) | **Laravel Sanctum** | Token auth so mobile can subscribe to realtime + hit APIs |
| Notification | Laravel Notifications (`database` + `broadcast` + `mail`) | In-app bell, realtime tab, email — one class, three channels |
| Queue | Laravel Queue (`database` or Redis) + Supervisor | **Required** — all notifications/emails are `ShouldQueue`, worker must actually run |

**Principle:** `enquiries` and `gis_enquiries` share all logic via a `HasEnquiryWorkflow` trait + `Enquirable` interface — no duplicated code across the two tables.

---

## 2. Role / Permission

### 2.1 Roles

| Role | Level | Summary |
|---|---|---|
| `root` | System super admin | Can do everything (bypasses every gate) + system/user management |
| `ceo` | Business | Effectively root within the CRM domain; can assign to sale + sale_manager; views KPI |
| `general_manager` | Business | Can assign to sale + sale_manager; views KPI |
| `admin` | Ops | **Read-only** — cannot assign / change status / delete |
| `sale_manager` | Sales | Can assign to sale; can change status |
| `sale` | Sales | Sees only assigned enquiries; can change status; **cannot delete** |

> **root vs ceo:** `root` = system-level authority (uses `Gate::before` to bypass everything — for debug/config), `ceo` = highest business-level authority but doesn't touch system config.

### 2.2 Permission Matrix

| Permission (slug) | root | ceo | gm | admin | sale_mgr | sale |
|---|:--:|:--:|:--:|:--:|:--:|:--:|
| `enquiry.view.all` | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| `enquiry.view.assigned` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `enquiry.filter` | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| `enquiry.assign.to_sale_manager` | ✓ | ✓ | ✓ | — | — | — |
| `enquiry.assign.to_sale` | ✓ | ✓ | ✓ | — | ✓ | — |
| `enquiry.update_status` | ✓ | ✓ | ✓ | — | ✓ | ✓ |
| `enquiry.delete` (soft) | ✓ | ✓ | ✓ | — | ✓ ⚠️ | — |
| `enquiry.bulk_delete` | ✓ | ✓ | ✓ | — | ✓ ⚠️ | — |
| `enquiry.restore` | ✓ | ✓ | ✓ | — | — | — |
| `tracking.view` (KPI) | ✓ | ✓ | ✓ | — | — | — |
| `user.view` | ✓ | ✓ | ✓ | — | — | — |
| `user.create` | ✓ | ✓ | ✓ | — | — | — |
| `user.update` | ✓ | ✓ | ✓ | — | — | — |
| `user.deactivate` | ✓ | ✓ | ✓ | — | — | — |

> ⚠️ **To confirm:** Can `sale_manager` delete? Currently set to allow (soft) — if it should be restricted to root/ceo/gm only, it's a one-line seeder change.
> **tracking.view:** Requirement says "only CEO and GM" — root sees it only because of super-admin bypass (the KPI menu is rendered only for ceo/gm/root).
> **Key constraint on `user.create` / `user.update`:** ceo and gm can only create/edit users at roles `admin`, `sale`, `sale_manager` (see 2.4). Creating or promoting anyone to `root`, `ceo`, or `general_manager` can only be done by `root` via seeder/artisan command — not exposed in the UI.

### 2.3 Implementation

```php
// database/seeders/RolePermissionSeeder.php — create permissions + assign per matrix

// app/Providers/AuthServiceProvider.php
Gate::before(fn ($user) => $user->hasRole('root') ? true : null); // root bypass

// Defense in depth on every layer:
// - Route: ->middleware('permission:enquiry.assign.to_sale')
// - Livewire: $this->authorize(...) inside every action method
// - Policy: EnquiryPolicy (view / update / delete / assign / restore)
```

**Additional rules (v1.1):**
- One user has one primary role (keeps KPI snapshots simple) — if multi-role is needed later, store a designated "primary role" for activity logging
- Any change to a user's role writes an audit log entry (who changed it, from → to)
- A user cannot edit their own role even if they hold user-management permissions (prevents self-escalation)

### 2.4 User Management Page (v1.2)

**Route:** `/users` — `->middleware(['auth','permission:user.view'])` (accessible only to root / ceo / gm)

#### Capabilities
| Feature | Details |
|---|---|
| **User list** | Livewire table: name, email, role, status (active/inactive), created date; search/filter by role |
| **Create user** | Form: name, email, role — **role restricted to `admin`, `sale`, `sale_manager`** |
| **Edit user** | Change name / email / role (within the three permitted roles) |
| **Deactivate / Reactivate** | Disable account instead of deleting (see rationale below) |
| **Reset password** | Button to email a password-reset link to the user |

#### User creation flow (best practice — the creator never sees the password)
```
root/ceo/gm enters name + email + role
   ├─ validate: role ∈ whitelist ['admin','sale','sale_manager'] (server-side)
   ├─ validate: email unique
   ├─ create user (no password yet, status: pending)
   ├─ audit log entry: who created, which role, when
   └─ send invitation email → user clicks signed URL (48h expiry)
        → sets their own password → status becomes active
```
> If the link expires, an admin can resend it from the user list.

#### Security rules for the User Management page
- **Server-enforced role whitelist:** `['role' => 'required|in:admin,sale,sale_manager']` — even if the UI dropdown is tampered with and a value like `ceo` is posted, the server rejects it. Creating or promoting to `root`/`ceo`/`general_manager` is not exposed in the UI at all — only via seeder/artisan command by root.
- **Cannot edit self:** users cannot change their own role or deactivate themselves (prevents self-escalation and locking oneself out).
- **Cannot edit users at equal or higher role:** gm cannot edit ceo/other gm accounts; ceo cannot edit root. The Policy always enforces the role hierarchy.
- **Deactivate instead of delete:** users are linked to `assigned_to`, `closed_by`, and activity logs — a real delete breaks KPI history. Use an `is_active` flag and block login instead. An `inactive` user (1) cannot log in, (2) has their Sanctum tokens revoked, (3) disappears from assign dropdowns.
- **Audit every action:** create / role change / deactivate / reset password → append to `user_audit_logs` (actor, target, action, old/new value, timestamp) — append-only, same as `enquiry_activities`.
- **Rate limit:** cap invitation/reset email sending (prevent the admin page from becoming a spam-email amplifier).

#### Additional migration
```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_active')->default(true)->index();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('invited_at')->nullable();
    $table->timestamp('activated_at')->nullable();   // set when password creation succeeds
});

Schema::create('user_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('target_user_id')->constrained('users')->cascadeOnDelete();
    $table->enum('action', ['created','role_changed','updated','deactivated','reactivated','password_reset_sent','invitation_resent']);
    $table->json('old_value')->nullable();
    $table->json('new_value')->nullable();
    $table->timestamp('created_at')->index();
});
```

#### `is_active` enforcement across the system
- Middleware `EnsureUserIsActive` runs after `auth` on every route — inactive users are logged out with a notice.
- `/broadcasting/auth` and Sanctum must also check this flag (prevents a deactivated user from continuing to receive realtime events or hit APIs via a lingering session/token).
- Assign dropdowns always filter `where('is_active', true)`.

---

## 3. Enquiry — Status & Lifecycle

### 3.1 Funnel Status (applies to both tables)

| DB value | Meaning | Source |
|---|---|---|
| `lead_mql` | Lead / MQL — newly submitted via form | Auto-set on create |
| `sql` | Sales Qualified Lead — passed initial qualification | Set by sale |
| `prospect` | Interested / requested trial | Set by sale |
| `customer` | Trial converted to paying customer (won) | Set by sale/manager |

> **`deleted` is not a funnel status** — it's a separate flag (`deleted_at` + `deleted_by`) so the real funnel status is preserved even after deletion (auditable / restorable). The UI shows it as a separate "Deleted" filter.

### 3.2 Transition rules
- New records are always `lead_mql` (enforced server-side — the public form can't set status)
- Status can only be changed by someone with `enquiry.update_status` AND who can see the record (`visibleTo`)
- Use a PHP Enum (`EnquiryStatus`) + validate in FormRequest — reject anything outside the enum
- Every change → append to `enquiry_activities` + update `last_updated_by/at`
- Transition to `customer` = "close/won" → record `closed_at`, `closed_by`, `closed_by_role`
- Soft-deleted records cannot be assigned or have their status changed until restored

---

## 4. Data Model

### 4.1 Migration — add columns to `enquiries` and `gis_enquiries` (identical for both)

```php
Schema::table('enquiries', function (Blueprint $table) {
    $table->enum('status', ['lead_mql','sql','prospect','customer'])
          ->default('lead_mql')->index();

    // assignment
    $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('assigned_at')->nullable();

    // audit / KPI
    $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('closed_at')->nullable();
    $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('closed_by_role')->nullable();      // role snapshot at close time
    $table->boolean('counts_for_sale_kpi')->default(true); // false if ceo/gm closed it themselves

    // soft delete
    $table->softDeletes();                              // deleted_at
    $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();

    // indexes for hot queries (v1.1)
    $table->index(['assigned_to', 'deleted_at']);       // sale's list page
    $table->index(['status', 'deleted_at']);            // status filters
    $table->index('created_at');
});
// ↑ mirror migration file for gis_enquiries with identical content
```

### 4.2 Migration — activity/KPI tracking table — **append-only**

```php
Schema::create('enquiry_activities', function (Blueprint $table) {
    $table->id();
    $table->morphs('enquirable');   // enquirable_type, enquirable_id
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('user_role')->nullable();    // role snapshot at action time
    $table->enum('action', ['created','assigned','reassigned','status_changed','deleted','restored']);
    $table->string('old_status')->nullable();
    $table->string('new_status')->nullable();
    $table->json('meta')->nullable();           // e.g. {from_user_id, to_user_id}
    $table->timestamp('created_at')->index();
});
```

```php
// AppServiceProvider — v1.1: enforce morph map (security + refactor-safe)
Relation::enforceMorphMap([
    'enquiry'     => \App\Models\Enquiry::class,
    'gis_enquiry' => \App\Models\GisEnquiry::class,
]);
```

**Append-only rule:** no route/method updates or deletes rows in `enquiry_activities` — the model exposes no `UPDATE/DELETE`. For extra hardening, the application's DB user should have `UPDATE/DELETE` revoked on this table.

### 4.3 Models

```php
class Enquiry extends Model {
    use SoftDeletes, HasEnquiryWorkflow;

    // v1.1 — mass assignment protection: the public form may only write contact fields
    protected $fillable = ['name','email','phone','company','message','subject'];
    // status / assigned_* / closed_* / deleted_by / counts_for_sale_kpi
    // are only mutated through trait methods — never mass-assigned
}

// trait HasEnquiryWorkflow provides:
// - relationships: assignedTo(), assignedBy(), lastUpdatedBy(), activities()
// - assignTo(User $u, User $by), changeStatus(EnquiryStatus $s, User $by),
//   softDeleteBy(User $by), restoreBy(User $by)  ← each method writes activity log
// - scopeVisibleTo(User $u)
```

**Visibility scope (critical):**
```php
public function scopeVisibleTo($q, User $u) {
    if ($u->can('enquiry.view.all')) return $q;   // root/ceo/gm/admin/sale_mgr
    return $q->where('assigned_to', $u->id);      // sale
}
// Every query in the app (list, show, update, delete, filter API) must go through this scope
// → IDOR defense: a sale probing another user's id gets 404, not 403 (doesn't leak existence)
```

---

## 5. Assignment + Notification

### 5.1 Flow

```
[user with assign permission] → picks a target user → Livewire assign() action
   ├─ authorize(permission matching the target's role)
   ├─ validate target: must actually hold role sale or sale_manager (server-side)  ← v1.1
   ├─ DB::transaction + lockForUpdate() to prevent assign race conditions           ← v1.1
   ├─ $enquiry->assignTo($target, $actor)   // update fields + write activity
   ├─ if reassign → also notify the previous assignee (EnquiryUnassignedNotification) ← v1.1
   └─ $target->notify(new EnquiryAssignedNotification($enquiry))
```

**Target validation (v1.1 — important):** the server must verify the target `user_id` (1) exists, (2) is active, and (3) holds a role the actor is permitted to assign to. Never trust the client-side dropdown — direct API calls exist.

### 5.2 Notification channels (one class, three channels)

```php
class EnquiryAssignedNotification extends Notification implements ShouldQueue {
    public function via($n) { return ['database','broadcast','mail']; }
    // database  → in-app bell (unread count, historical view)
    // broadcast → realtime into the tab via Reverb/Echo
    // mail      → email to users.email of the assignee
}
```

- **Minimal email content:** enquiry title/subject + link back into the system. Do not include full contact details of the lead in email bodies (emails leak/forward more easily than the app).
- All notifications implement `ShouldQueue` → a queue worker must run (see Section 11).

### 5.3 "Browser tab" notification — two layers
1. **In-app bell (Livewire):** subscribes to `notification-created` via Echo → increments unread count + shows a toast instantly.
2. **Native browser notification:** the Echo listener calls the browser's `Notification` API (permission requested on first use) → notification fires even when the tab isn't focused.

---

## 6. Realtime / Socket (Reverb)

### 6.1 Channel design

```php
// routes/channels.php
Broadcast::channel('App.Models.User.{id}',
    fn ($user, $id) => (int) $user->id === (int) $id);   // per-user private channel
```

- The event `EnquiryAssigned implements ShouldBroadcast` broadcasts to `PrivateChannel('App.Models.User.'.$targetId)`.
- **Minimal payload (v1.1):** send only `{enquiry_id, source, display_name}` — the client fetches details via the authorized API. Never push full PII over the socket.

### 6.2 Socket security (v1.1)
- Production must be **`wss://` (TLS)** only — put Reverb behind an nginx reverse proxy.
- Use **private channels only** for enquiry data — no public channel is allowed to carry customer information.
- `/broadcasting/auth` sits behind the `auth` middleware (web session / Sanctum for mobile).
- The Reverb app key is not a secret, but the **app secret must never reach the frontend**.

### 6.3 Mobile app readiness
- Mobile login goes through **Sanctum** → obtains a token → uses it to authenticate the private channel subscription (`/broadcasting/auth`).
- **Login is mandatory** before any realtime access (per the requirement).

```
Web  (Echo + cookie session) ─┐
                              ├─→  Laravel Reverb (wss/TLS) ──→ private channels
Mobile (Echo + Sanctum token)─┘
```

---

## 7. Activity Tracking & KPI

### 7.1 `/tracking` page (KPI) — **CEO + GM only**
- `->middleware(['auth','permission:tracking.view'])`
- Displays metrics per enquiry / per sale rep / per department.

### 7.2 Core metrics
| Metric | Calculation |
|---|---|
| **Response time** | First activity by role sale/sale_manager − `assigned_at` |
| **Time to close** | `closed_at` − `assigned_at` |
| **Sales-side closes** | Count only where `counts_for_sale_kpi = true` |
| **Edit history** | `last_updated_by` + timeline from `enquiry_activities` |

### 7.3 "CEO/GM closing themselves doesn't count for sales KPI"
```php
$countsForSale = in_array($actorRole, ['sale','sale_manager']);
$enquiry->update([
    'closed_at' => now(),
    'closed_by' => $actor->id,
    'closed_by_role' => $actorRole,          // snapshot at this moment, not queried later
    'counts_for_sale_kpi' => $countsForSale, // false when ceo/gm close it themselves
]);
```
→ The sales KPI report always filters `WHERE counts_for_sale_kpi = true`.

**v1.1:** Set `APP_TIMEZONE` (e.g. `Asia/Bangkok`) explicitly from the start — same-day KPI metrics drift when the server runs UTC but reports are read in local time.

---

## 8. UI — `/enquiry` and `/gis-enquiry`

*(Both routes share one Livewire component with a different `source` parameter.)*

### 8.1 Components
| Feature | Details |
|---|---|
| **Filter** | status, assigned_to, date range, keyword (email/name/company), include/exclude deleted → via Filter API |
| **`assign_to` column** | Per-row dropdown showing only users the actor is permitted to assign to |
| **Multi-select checkbox** | Select many rows + "select all on page" → **Bulk Delete** button with confirmation modal |
| **Soft delete** | Sets `deleted_at` + `deleted_by`; restorable (root/ceo/gm) from the "Deleted" filter |
| **Status badge** | Four funnel statuses + Deleted badge |
| **Role-aware controls** | Assign/delete buttons hidden if the actor lacks the permission (and re-enforced server-side); sale sees only their own rows |
| **Output escaping** | All enquiry fields rendered via `{{ }}` only (see Section 10.3) |

### 8.2 Bulk delete (soft)
```php
public function bulkDelete() {
    $this->authorize('enquiry.bulk_delete');
    abort_if(count($this->selected) > 100, 422);          // v1.1: cap at 100 per call

    DB::transaction(function () {
        Enquiry::whereIn('id', $this->selected)
            ->visibleTo(auth()->user())                    // guards against smuggled ids
            ->each(fn ($e) => $e->softDeleteBy(auth()->user()));
    });
    $this->selected = [];
}
```

---

## 9. API Endpoints

> Every endpoint goes through `auth` + `permission` middleware; `/api/*` uses **Sanctum** + `throttle`.

| Method | Endpoint | Permission | Notes |
|---|---|---|---|
| `GET` | `/api/enquiries` | `enquiry.filter` | **Filter API** — `status, assigned_to, source, date_from, date_to, q, trashed, sort, page` |
| `GET` | `/api/gis-enquiries` | `enquiry.filter` | Same as above, for gis |
| `POST` | `/enquiries/{id}/assign` | `enquiry.assign.*` | body: `user_id` (validate target role) |
| `PATCH` | `/enquiries/{id}/status` | `enquiry.update_status` | body: `status` (validated against enum) |
| `DELETE` | `/enquiries/{id}` | `enquiry.delete` | Soft delete + records `deleted_by` |
| `POST` | `/enquiries/bulk-delete` | `enquiry.bulk_delete` | body: `ids[]` (max 100) |
| `POST` | `/enquiries/{id}/restore` | `enquiry.restore` | root/ceo/gm |
| `GET` | `/api/tracking/kpi` | `tracking.view` | ceo/gm/root |

### 9.1 Filter API — security rules (v1.1)
```php
// Whitelist every parameter — never map input directly into a query
$request->validate([
    'status'      => ['nullable', new Enum(EnquiryStatus::class)],
    'assigned_to' => ['nullable','integer','exists:users,id'],
    'date_from'   => ['nullable','date'],
    'date_to'     => ['nullable','date','after_or_equal:date_from'],
    'q'           => ['nullable','string','max:100'],
    'trashed'     => ['nullable','in:with,only'],   // only usable by holders of enquiry.restore
    'sort'        => ['nullable','in:created_at,-created_at,assigned_at,-assigned_at,status,-status'],
]);
// - sort MUST be a fixed whitelist — never accept a raw column name (prevents SQLi / column leaks)
// - q uses Eloquent parameter binding only
// - every query starts with ->visibleTo(auth()->user())
```

---

## 10. Security Spec (v1.1 — full)

### 10.1 Authentication
| Requirement | Details |
|---|---|
| Password policy | Minimum 10 characters + `Password::defaults()->uncompromised()` (checks against leaked password lists) |
| Login throttling | `throttle` + lockout after 5 failed attempts (Laravel provides this via `RateLimiter`) |
| 2FA | **Strongly recommended** for root / ceo / gm (e.g. Laravel Fortify TOTP) — these accounts see all customer data |
| Session | `SESSION_SECURE_COOKIE=true`, `http_only`, `same_site=lax`, sensible session lifetime (e.g. 8h), regenerate session on login |
| Logout all devices | Provide a "log out other devices" button at minimum for high-privilege roles |

### 10.2 Authorization
- **Three-layer defense in depth:** Route middleware → Livewire/Controller `authorize()` → Policy — never rely on hiding UI controls
- **IDOR defense:** any `{id}` access must pass through `visibleTo()` scope → unauthorized ids return 404
- **Privilege escalation defense:** users cannot edit their own role / server-side validation of assign targets (5.1) / role changes always audit-logged
- **Log failed authorizations:** capture (user, route, timestamp) on any 403 — detects cross-role probing attempts

### 10.3 Input / Output — the #1 risk in this system
> **Every enquiry row is input from an anonymous internet stranger** — the name, email, or message field can contain `<script>` payloads that will be rendered on a CEO's or sale rep's screen = **Stored XSS directly targeting privileged accounts.**

| Measure | Details |
|---|---|
| Output escaping | Render enquiry data with `{{ }}` only — **never use `{!! !!}` on enquiry data, anywhere, including email templates** |
| Input validation | FormRequest on every form: length caps, email/phone format, strip control characters |
| Mass assignment | `$fillable` limited to contact fields (4.3); status/assign/delete flow only through methods |
| CSRF | Blade forms use `@csrf`; Livewire handles it automatically — never exclude an authenticated route from CSRF |
| SQL injection | Eloquent + parameter binding only; sort/filter whitelisting (9.1); no `DB::raw` on user input |
| File upload | If the form accepts attachments: whitelist extensions + real mime, store outside public path, randomize filenames, size cap |

### 10.4 Public form (the enquiry origin)
- `throttle:` on the form-receiving endpoint (e.g. 5 requests/min/IP) — prevents lead spam and email flood
- **Honeypot field + minimum submission time** (e.g. spatie/laravel-honeypot) and/or CAPTCHA (Turnstile/reCAPTCHA)
- Never accept `status`, `assigned_to`, or any system fields from the form (enforced via `$fillable`)
- The form response must not leak whether a given email has submitted before

### 10.5 API / Sanctum (mobile)
- Tokens **have an expiration** (`sanctum.expiration`, e.g. 7–30 days) + are revoked on logout
- Use **token abilities** to constrain scope (e.g. `enquiry:read`, `enquiry:update-status`) — mobile doesn't need delete
- `throttle:api` on every endpoint (e.g. 60 req/min/user)
- API error responses must not leak stack traces / SQL (see 10.8)

### 10.6 Realtime
- `wss://` only in production, private channels only for enquiry data, minimal broadcast payloads (6.1–6.2)

### 10.7 Audit & personal data (PDPA)
- `enquiry_activities` is **append-only** (4.2) — a mutable log can't serve as KPI evidence
- Enquiry data (name, email, phone) = **personal data under PDPA (Thailand)**:
  - The public form must display a privacy notice explaining collection purpose
  - Define a **retention policy**: records soft-deleted for more than N days (e.g. 180) → job hard-deletes or anonymizes them — decide the number with the business
  - Restrict data export: if a CSV export exists, allow only ceo/gm and log every export (who, when, what)
- Encrypt database backups + verify restore actually works

### 10.8 Infrastructure / Config
- `APP_DEBUG=false` in production (the debug page leaks env/credentials wholesale)
- `.env` outside web root with restrictive file permissions; force HTTPS everywhere (`URL::forceScheme('https')` + HSTS)
- Security headers: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, a baseline CSP
- Keep dependencies updated + run `composer audit` in CI
- Give the application's DB user only the privileges it needs (not the MySQL root account)
- **Note about the phpMyAdmin URL shared earlier:** phpMyAdmin should not be exposed to the public internet — restrict by IP or move it behind a VPN, and verify that this URL/credential set has not been shared elsewhere

---

## 11. Technical Caveats (v1.1)

| Topic | Details |
|---|---|
| **Queue worker** | All notifications are `ShouldQueue` → a worker must run under Supervisor + monitor `failed_jobs` (email retries depend on it) — **without a worker, notifications go silently missing** |
| **Concurrency** | Two managers assigning the same record simultaneously → wrap `assignTo()` in `DB::transaction` + `lockForUpdate()`; the second becomes a properly-logged reassign |
| **Indexes** | Add those in 4.1 — especially `(assigned_to, deleted_at)`, the hot path for sale's list view |
| **Timezone** | Set `APP_TIMEZONE=Asia/Bangkok` explicitly or store UTC and convert on display — pick one convention and use it everywhere |
| **Reverb on shared hosting** | Reverb needs a long-running process + an open port — if the current host (Plesk) can't run it, fall back to **Pusher** (Echo code is nearly identical). Verify hosting constraints before starting. |
| **gis_enquiries schema** | Diff the actual schemas of the two tables before writing migrations — any field differences may require trait/blade tweaks |

---

## 12. Implementation Plan (recommended order)

1. Install: `spatie/laravel-permission`, `laravel/reverb`, `laravel/sanctum`, `livewire/livewire`, `spatie/laravel-honeypot`
2. Base hardening (10.8): `APP_DEBUG`, HTTPS, session config, security headers
3. Migrations: extend both tables + create `enquiry_activities` + morph map
4. Seeder: roles + permissions per the matrix
5. Model + trait `HasEnquiryWorkflow` + Policy + Enum
6. Public form hardening (throttle + honeypot) — do this before any new feature since it's the data intake
7. Filter API + validation whitelist
8. Livewire pages `/enquiry` `/gis-enquiry` (filter, assign, bulk soft-delete, restore)
9. Assignment event + Notification (database + broadcast + mail) + queue worker
10. Reverb + Echo (bell + native notification), or Pusher based on hosting constraints
11. `/tracking` KPI page + `counts_for_sale_kpi` logic
12. User Management page (2.4): invitation flow + audit log + `EnsureUserIsActive` middleware
13. Sanctum + broadcasting auth + token abilities (mobile readiness)
14. Testing: unit-test policy/permission per role, IDOR (sale probing other ids), XSS payloads in the form, user-creation role whitelist (posting `ceo` directly must be rejected)

---

## 13. Open Questions to Confirm Before Building

1. **Can `sale_manager` delete?** — currently allowed; if it should be root/ceo/gm only, please confirm
2. **Reassignment:** can it override an existing assignment freely, or only under conditions (e.g. only if untouched)?
3. **Definition of "close" for KPI:** currently `status = customer` (won) — is there also a "lost/not interested" outcome to track? (If yes, add `lost` to the enum or a separate `outcome` field)
4. **Notification email** goes to `users.email` of the assignee — confirm?
5. **Can `admin` view KPI?** currently no (only ceo/gm)
6. **(v1.1) Retention for soft-deleted records:** how many days before hard-delete/anonymize? (Linked to PDPA — needs a business decision)
7. **(v1.1) Can the current hosting run a long-lived process (Reverb) with an open WebSocket port?** If not, fall back to Pusher from day one
8. **(v1.1) When a brand-new enquiry arrives (still unassigned), should anyone be notified?** e.g. notify ceo/gm/sale_manager of a new lead awaiting assignment — not in the original spec but heavily improves response-time KPI
9. **(v1.2) When a user is deactivated, what happens to their pending enquiries?** — leave them for a manager to reassign, or force/auto-reassign? (Currently: left in place + user disappears from dropdowns)
10. **(v1.2) User creation uses an invitation link (user sets own password — best practice, creator never sees it)** — confirm, or would you prefer the creator sets a temporary password with forced change on first login?