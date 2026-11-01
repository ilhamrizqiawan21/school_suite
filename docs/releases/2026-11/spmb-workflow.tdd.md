# SPMB Workflow TDD Evidence

## Source

Journeys were derived from the SPMB implementation plan: configure a period and track, register one applicant per period/track, verify private documents, rank by configurable criteria, finalize an audited decision, and confirm re-registration into Academic.

## Evidence

| Guarantee | Test | Result |
|---|---|---|
| Admin/operator can configure periods, tracks, requirements, and criteria | `tests/Feature/AdmissionsWorkflowTest.php` | PASS |
| Principal can view SPMB but cannot change configuration | `AdmissionsWorkflowTest::test_operator_can_manage_but_principal_is_read_only` | PASS |
| Period cannot open without complete configuration | `AdmissionsWorkflowTest::test_period_cannot_be_opened_without_track_and_required_configuration` | PASS |
| Duplicate NISN is rejected within a period | `AdmissionsWorkflowTest::test_applicant_requires_period_track_and_rejects_duplicate_nisn` | PASS |
| Applicant documents use private storage and can be verified | `AdmissionsWorkflowTest::test_document_is_private_and_can_be_verified_by_authorized_actor` | PASS |
| Ranking requires valid required documents and final decisions are audited | `AdmissionsWorkflowTest::test_ranking_and_finalization_are_audited_and_re_registration_creates_one_student` | PASS |
| Re-registration creates one Academic student even when submitted twice | `AdmissionsWorkflowTest::test_ranking_and_finalization_are_audited_and_re_registration_creates_one_student` | PASS |

## Commands

- RED: `php artisan test tests/Feature/AdmissionsWorkflowTest.php` — 5 expected failures because the new routes did not exist.
- GREEN: `php artisan test tests/Feature/AdmissionsWorkflowTest.php tests/Feature/AdmissionsControllersTest.php` — 11/11 passed.
- Boundary/regression: `php artisan test tests/Architecture/ModularBoundaryTest.php tests/Feature/AdmissionsWorkflowTest.php tests/Feature/AdmissionsControllersTest.php` — 16/16 passed.
- Typecheck: `npm run typecheck` — passed.
- Build: `npm run build` — passed.
- Full suite: `php artisan test` — 421 passed, 4 existing GD-extension errors in BrandingProfile and StudentLedger image tests.

## Known gaps

The public applicant portal remains intentionally deferred. Full coverage reporting was not available because the repository has no coverage script configured. Browser QA could not complete login because the seeded account is forced through the initial password-change flow; the browser host was otherwise reachable at the local login page.
