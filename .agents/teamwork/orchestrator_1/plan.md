# Orchestration Plan: School Suite Non-Admin Navigation & Dashboard Workflows

## Goal
Restructure and complete sidebar navigation (`SidebarNavigation.vue`) and dashboard workflows (`Dashboard.vue`) for non-admin roles (Teacher, Homeroom, Student, Principal, Operator) while strictly preserving `adminActions`, eliminating broken/404 routes, and ensuring all tests and typechecks pass 100%.

## Phases

### Phase 0: Scope Survey & Codebase Exploration
- Spawn 3 parallel Explorers:
  - **Explorer 1 (Routes & Navigation Structure)**: Map existing frontend & backend routes, check current `SidebarNavigation.vue`, identify missing routes/menu items and 404 links (like `/penilaian/laporan` vs `/laporan`).
  - **Explorer 2 (Dashboard Workflows & Non-Admin Actions)**: Examine `Dashboard.vue`, role actions (`teacherActions`, `homeroomActions`, `studentActions`, `principalActions`, `operatorActions`), verify `adminActions` boundaries to ensure it remains untouched.
  - **Explorer 3 (Modular Monolith Boundaries & Test Infrastructure)**: Check existing tests, test commands (`npm run typecheck`, `npm run build`, `php artisan test`), and module boundaries to prevent architectural violations.
- Synthesize survey findings into `PROJECT.md` with Feature Inventory and Implementation Plan.

### Phase 1: Implementation
- Spawn Worker to implement R1 & R2:
  - Update `SidebarNavigation.vue` with properly structured domain sections (Akademik, Pembelajaran LMS, Asesmen & CBT, Penilaian & Rapor, Perwalian, Komunikasi, Pengaturan) for teacher, homeroom, student, principal, operator.
  - Update `Dashboard.vue` non-admin action lists and quick links, fixing broken links and tuning copy/metrics. Ensure `adminActions` is untouched.
  - Run `npm run typecheck`, `npm run build`, and `php artisan test` to verify zero regressions.

### Phase 2: Review, Verification & Adversarial Stress Testing
- Spawn 2 Reviewers independently to verify code quality, role permissions, route validity, and non-admin requirements.
- Spawn 2 Challengers to empirically stress-test route resolution, edge cases, role permission boundaries, and ensure no 404 links exist.

### Phase 3: Forensic Audit & Integrity Verification
- Spawn Forensic Auditor (`teamwork_preview_auditor`) to verify authentic implementation (no cheating, no hardcoded stubs, no broken boundaries).

### Phase 4: Final Gate & Sentinel Reporting
- Gate verification: check worker output, reviewer approvals, challenger proofs, auditor clean verdict.
- Prepare comprehensive handoff and report completion to Sentinel.
