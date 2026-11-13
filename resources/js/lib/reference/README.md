# Jurnal Guru reference engines

Source: https://github.com/ilhamrizqiawan21/jurnal-harian-guru/tree/fitur-lisensi-2-0

`itemAnalysis.cjs` and `quickAnalysis.cjs` retain the original statistical and validation implementations. The relative CommonJS dependency uses the `.cjs` extension. The `.js` counterparts adapt the same factory bodies to ES modules for Vite. Source copyright remains with the original repository owner.

Pinned reference commit: `27b2eaaf69c1f8e555fb3198c4150a837500322e`.

`examLayout.js` and `examPackage.js` preserve the reference grouping, numbering, paper settings, letterhead, compact options, essay lines, answer keys, and blueprints. Browser adaptations use ES modules, Web Crypto UUIDs, Uint8Array images, and DOCX Blob output. The application attaches its existing CSP nonce to preview styles.

Integration:
- Bank metadata includes C1–C6 (existing L1–L3 remains supported), learning indicators, class, source, discussion, tags, and single or multiple matching pairs. CSV/XLSX imports accept both the School Suite template and the reference PG template; preview supports explicit valid-only import and duplicate skipping. School Suite retains school-specific subject masters, teacher permissions, and version history.
- Packages store document identity, letterhead, A4/F4, orientation, and 1–3 columns. Existing immutable question versions remain fixed when a package is edited or copied. School Suite matching pairs are expanded into individual reference matching statements, and true/false items become two-option questions in printed documents.
- Quick analysis uses the original three-step validation and statistics. XLSX/CSV import previews, three-sheet XLSX exports, dedicated PDF reports, and encrypted per-school/per-user drafts are connected to Laravel. Excel export clears the draft; PDF export preserves it.
- Package PDF uses the browser print/save-as-PDF dialog in place of the reference Electron printToPDF API. Word uses the reference DOCX layout engine. The application does not require Electron.

Verification:
`node --test tests/reference/*.cjs tests/reference/*.mjs`
`php artisan test --compact --filter='QuestionWorkflowParityTest|AssessmentControllersTest'`
`npm run typecheck && npm run build`

The browser-engine tests compare ESM statistics to the unmodified CommonJS engine, roundtrip the Excel data model, and inspect generated Word XML across all three document types and column settings.
