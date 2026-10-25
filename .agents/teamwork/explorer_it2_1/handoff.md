# Handoff Report — Explorer it2_1 (Frontend Active Highlight Collision Specialist)

## 1. Observation

### 1.1 Verbatim Code Location and Issue
In `resources/js/components/SidebarNavigation.vue:148-160`:

```typescript
const isItemActive = (item: NavItem): boolean => {
    const rawPath = currentUrl.value.split('?')[0];
    const currentPath = rawPath.length > 1 && rawPath.endsWith('/') ? rawPath.slice(0, -1) : rawPath;
    const targetHref = item.href.length > 1 && item.href.endsWith('/') ? item.href.slice(0, -1) : item.href;

    if (item.exact || targetHref === '/') {
        return currentPath === targetHref;
    }
    if (item.activePrefix) {
        return currentPath.startsWith(item.activePrefix);
    }
    return currentPath === targetHref;
};
```

### 1.2 Route and Navigation Item Collision
In `resources/js/components/SidebarNavigation.vue:34` and `38`:
- Line 34: `{ name: 'Rombel / Kelas', href: '/akademik/kelas', key: 'classes', activePrefix: '/akademik/kelas', icon: '...' }`
- Line 38: `{ name: 'Kelas Daring', href: '/akademik/kelas-daring', key: 'online-classes', activePrefix: '/akademik/kelas-daring', icon: '...' }`

When a user visits `/akademik/kelas-daring`:
- For item `classes` (`item.activePrefix = '/akademik/kelas'`):
  - `currentPath.startsWith(item.activePrefix)` evaluates to:
    `"/akademik/kelas-daring".startsWith("/akademik/kelas") === true`
- For item `online-classes` (`item.activePrefix = '/akademik/kelas-daring'`):
  - `currentPath.startsWith(item.activePrefix)` evaluates to:
    `"/akademik/kelas-daring".startsWith("/akademik/kelas-daring") === true`
- Both items evaluate to `true` and receive the CSS class `'is-active'`, illuminating two menu items simultaneously.

### 1.3 Audit of Existing Items and Hierarchical Routes
Auditing all 33 navigation items across 7 sections in `SidebarNavigation.vue` and comparing them against registered routes in `app/Modules/*/Interface/Routes/web.php`:
1. Sibling path collision with shared prefix characters:
   - `/akademik/kelas` and `/akademik/kelas-daring`: collision occurs because the next character in `/akademik/kelas-daring` is `'-'` rather than `'/'`.
2. True parent-child URL hierarchies where a parent route and child route both appear as distinct sidebar items:
   - `/pembelajaran` (`Ruang Belajar`) vs `/pembelajaran/progress` (`Progress Belajar`)
   - `/wali-kelas` (`Ruang Wali Kelas`) vs `/wali-kelas/catatan` (`Catatan Observasi & BK`) and `/wali-kelas/kedisiplinan` (`Kedisiplinan & Prestasi`)
   - `/komunikasi` (`Pengumuman & Agenda`) vs `/komunikasi/chat` (`Diskusi Chat`) and `/komunikasi/whatsapp` (`WhatsApp Orang Tua`)
   - In all three cases, the parent item already has `exact: true` explicitly defined in `SidebarNavigation.vue` (lines 46, 73, 82), correctly restricting the parent to exact matching and preventing highlight leaking to child sidebar items.
3. Subroutes without distinct sidebar items:
   - `/akademik/kelas/{classGroup}/wali-kelas` (Class Group homeroom assignment)
   - `/akademik/kelas-daring/{onlineClassSession}/presensi` (Online class attendance)
   - `/penilaian/rapor/{report}` (Report card detail)
   - `/ujian/attempt/{attempt}` (CBT exam attempt)
   - `/laporan/nilai`, `/laporan/presensi`, `/laporan/tugas`, `/laporan/sikap` (Reporting sub-views)
   - In these cases, subroutes SHOULD maintain the active highlight of their parent sidebar item.

---

## 2. Logic Chain

1. **Root Cause Analysis (Observation 1.1 & 1.2)**:
   - The method `isItemActive()` relied on `currentPath.startsWith(item.activePrefix)`.
   - JavaScript's `String.prototype.startsWith()` does not know URL path segment semantics.
   - Any sibling route whose URL segment happens to begin with the same characters as another route's name (e.g., `/akademik/kelas-daring` vs `/akademik/kelas`) triggers a false positive match.

2. **Evaluation of Fix Options**:
   - *Option A: Add `exact: true` to `/akademik/kelas`*:
     - If `exact: true` is set on `/akademik/kelas`, `isItemActive` requires `currentPath === targetHref`.
     - This would fix the collision for `/akademik/kelas-daring`.
     - HOWEVER, it would break subroute highlighting for any child path under `/akademik/kelas` (e.g. `/akademik/kelas/10-A`, `/akademik/kelas/1/wali-kelas`, or future detail tabs).
   - *Option B: Enforce URL path segment boundary checking in `isItemActive()`*:
     - A path matches an `activePrefix` if and only if:
       `currentPath === prefix || currentPath.startsWith(prefix + '/')`
     - When `currentPath` is `/akademik/kelas`: `currentPath === prefix` is `true`.
     - When `currentPath` is `/akademik/kelas/10`: `currentPath.startsWith(prefix + '/')` is `true`.
     - When `currentPath` is `/akademik/kelas-daring`:
       - `currentPath === '/akademik/kelas'` is `false`.
       - `currentPath.startsWith('/akademik/kelas/')` is `false` (since character at position 15 is `'-'`, not `'/'`).
     - This cleanly resolves the sibling collision without breaking legitimate subroutes.

3. **URL Normalization and Robustness Improvements**:
   - `rawPath` currently only strips query strings (`split('?')[0]`). If a URL contains a fragment hash (e.g. `/akademik/kelas#tab-1`), `currentPath` would retain `#tab-1` and fail equality. Using `.split('#')[0].split('?')[0]` ensures both query strings and hashes are properly stripped before matching.
   - `activePrefix` trailing slashes: Normalizing `prefix` to strip any accidental trailing slash (`item.activePrefix.length > 1 && item.activePrefix.endsWith('/') ? item.activePrefix.slice(0, -1) : item.activePrefix`) prevents double-slash bugs when concatenating `prefix + '/'`.

4. **Integration with Existing `exact: true` Architecture (Observation 1.3)**:
   - Items with `exact: true` (`/`, `/pembelajaran`, `/wali-kelas`, `/komunikasi`) continue to be evaluated first by `if (item.exact || targetHref === '/') return currentPath === targetHref;`.
   - This ensures child items such as `/pembelajaran/progress` or `/komunikasi/chat` do not activate their parent menu item.

---

## 3. Caveats

- **No caveats**: Every route registered across all Laravel modules and every navigation item configured in `SidebarNavigation.vue` was inspected.
- Note on duplicate items across roles: `WhatsApp Orang Tua` exists in both Section "Perwalian" (`key: 'homeroom-whatsapp'`) and Section "Komunikasi" (`key: 'communication-whatsapp'`). `roleVisibility` enforces strict mutual exclusivity (`homeroom` vs `admin, operator, principal`), ensuring no user sees both simultaneously.

---

## 4. Conclusion

The recommended fix strategy for `resources/js/components/SidebarNavigation.vue` is:

### 4.1 Code Replacement Snippet
In `resources/js/components/SidebarNavigation.vue:148-160`:

**Before**:
```typescript
const isItemActive = (item: NavItem): boolean => {
    const rawPath = currentUrl.value.split('?')[0];
    const currentPath = rawPath.length > 1 && rawPath.endsWith('/') ? rawPath.slice(0, -1) : rawPath;
    const targetHref = item.href.length > 1 && item.href.endsWith('/') ? item.href.slice(0, -1) : item.href;

    if (item.exact || targetHref === '/') {
        return currentPath === targetHref;
    }
    if (item.activePrefix) {
        return currentPath.startsWith(item.activePrefix);
    }
    return currentPath === targetHref;
};
```

**After**:
```typescript
const isItemActive = (item: NavItem): boolean => {
    const rawPath = currentUrl.value.split('#')[0].split('?')[0];
    const currentPath = rawPath.length > 1 && rawPath.endsWith('/') ? rawPath.slice(0, -1) : rawPath;
    const targetHref = item.href.length > 1 && item.href.endsWith('/') ? item.href.slice(0, -1) : item.href;

    if (item.exact || targetHref === '/') {
        return currentPath === targetHref;
    }
    if (item.activePrefix) {
        const prefix = item.activePrefix.length > 1 && item.activePrefix.endsWith('/')
            ? item.activePrefix.slice(0, -1)
            : item.activePrefix;

        return currentPath === prefix || currentPath.startsWith(prefix + '/');
    }
    return currentPath === targetHref;
};
```

### 4.2 Machine-Applicable Patch
A tested patch is saved at:
`/home/ilhamzp/Projects/school_suite/.agents/teamwork/explorer_it2_1/sidebar_active_highlight.patch`

---

## 5. Verification Method

### 5.1 Patch Dry-Run Verification
Verify that the patch applies cleanly to `resources/js/components/SidebarNavigation.vue`:
```bash
git apply --check .agents/teamwork/explorer_it2_1/sidebar_active_highlight.patch
```

### 5.2 Automated Segment Boundary Matrix Test
Execute the following verification script in Node.js to test all active route combinations:
```bash
node -e '
const items = [
  { name: "Beranda", href: "/", exact: true },
  { name: "Data Siswa", href: "/akademik/siswa", activePrefix: "/akademik/siswa" },
  { name: "Rombel / Kelas", href: "/akademik/kelas", activePrefix: "/akademik/kelas" },
  { name: "Kelas Daring", href: "/akademik/kelas-daring", activePrefix: "/akademik/kelas-daring" },
  { name: "Ruang Belajar", href: "/pembelajaran", exact: true, activePrefix: "/pembelajaran" },
  { name: "Progress Belajar", href: "/pembelajaran/progress", activePrefix: "/pembelajaran/progress" },
  { name: "Ruang Wali Kelas", href: "/wali-kelas", exact: true, activePrefix: "/wali-kelas" },
  { name: "Catatan Observasi & BK", href: "/wali-kelas/catatan", activePrefix: "/wali-kelas/catatan" },
  { name: "Pengumuman & Agenda", href: "/komunikasi", exact: true, activePrefix: "/komunikasi" },
  { name: "Diskusi Chat", href: "/komunikasi/chat", activePrefix: "/komunikasi/chat" }
];

function isItemActive(url, item) {
    const rawPath = url.split("#")[0].split("?")[0];
    const currentPath = rawPath.length > 1 && rawPath.endsWith("/") ? rawPath.slice(0, -1) : rawPath;
    const targetHref = item.href.length > 1 && item.href.endsWith("/") ? item.href.slice(0, -1) : item.href;

    if (item.exact || targetHref === "/") {
        return currentPath === targetHref;
    }
    if (item.activePrefix) {
        const prefix = item.activePrefix.length > 1 && item.activePrefix.endsWith("/")
            ? item.activePrefix.slice(0, -1)
            : item.activePrefix;

        return currentPath === prefix || currentPath.startsWith(prefix + "/");
    }
    return currentPath === targetHref;
}

const testCases = [
  { url: "/", expectedActive: ["Beranda"] },
  { url: "/?tab=home", expectedActive: ["Beranda"] },
  { url: "/akademik/kelas", expectedActive: ["Rombel / Kelas"] },
  { url: "/akademik/kelas/", expectedActive: ["Rombel / Kelas"] },
  { url: "/akademik/kelas/12", expectedActive: ["Rombel / Kelas"] },
  { url: "/akademik/kelas-daring", expectedActive: ["Kelas Daring"] },
  { url: "/akademik/kelas-daring/", expectedActive: ["Kelas Daring"] },
  { url: "/akademik/kelas-daring/presensi", expectedActive: ["Kelas Daring"] },
  { url: "/pembelajaran", expectedActive: ["Ruang Belajar"] },
  { url: "/pembelajaran/progress", expectedActive: ["Progress Belajar"] },
  { url: "/wali-kelas", expectedActive: ["Ruang Wali Kelas"] },
  { url: "/wali-kelas/catatan", expectedActive: ["Catatan Observasi & BK"] },
  { url: "/komunikasi", expectedActive: ["Pengumuman & Agenda"] },
  { url: "/komunikasi/chat", expectedActive: ["Diskusi Chat"] }
];

let failed = 0;
for (const tc of testCases) {
  const activeItems = items.filter(it => isItemActive(tc.url, it)).map(it => it.name);
  const ok = JSON.stringify(activeItems) === JSON.stringify(tc.expectedActive);
  if (!ok) {
    console.error(`FAIL for URL "${tc.url}": expected [${tc.expectedActive.join(", ")}], got [${activeItems.join(", ")}]`);
    failed++;
  } else {
    console.log(`PASS for URL "${tc.url}": [${activeItems.join(", ")}]`);
  }
}
if (failed > 0) process.exit(1);
console.log("\nALL VERIFICATION CASES PASSED.");
'
```

### 5.3 Type Check and Build Command
After worker applies the change:
```bash
npm run typecheck
npm run build
```

### 5.4 Invalidation Conditions
The fix would be invalidated if:
1. Sibling paths without boundary checks cause multi-item illumination.
2. Legitimate nested subroutes under `/akademik/kelas/*` fail to highlight `Rombel / Kelas`.
3. `vue-tsc --noEmit` or `vite build` returns type errors.
