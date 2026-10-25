const views = [...document.querySelectorAll('[data-view]')];
const navButtons = [...document.querySelectorAll('[data-view-target]')];
const stateSelect = document.querySelector('#stateSelect');
const stateBanner = document.querySelector('#stateBanner');
const fallbackState = document.querySelector('#fallbackState');
const sidebar = document.querySelector('#sidebar');
const menuButton = document.querySelector('#menuButton');
const sidebarBackdrop = document.querySelector('#sidebarBackdrop');
const toast = document.querySelector('#toast');
let currentView = 'dashboard';
let toastTimer;

function icon(id) {
  return `<svg aria-hidden="true"><use href="#${id}"/></svg>`;
}

function showView(name) {
  currentView = name;
  views.forEach((view) => {
    const active = view.dataset.view === name;
    view.hidden = !active;
    view.classList.toggle('is-active', active);
  });
  document.querySelectorAll('.nav-item[data-view-target]').forEach((button) => {
    button.classList.toggle('is-active', button.dataset.viewTarget === name);
  });
  stateSelect.value = 'normal';
  renderState('normal');
  closeSidebar();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

navButtons.forEach((button) => {
  button.addEventListener('click', () => showView(button.dataset.viewTarget));
});

function renderState(state) {
  const activeView = views.find((view) => view.dataset.view === currentView);
  stateBanner.hidden = true;
  fallbackState.hidden = true;
  activeView.hidden = false;

  if (state === 'normal') return;

  if (state === 'partial') {
    stateBanner.hidden = false;
    stateBanner.innerHTML = `${icon('i-clock')}<strong>Beberapa data belum diperbarui.</strong> Kehadiran kelas VII-A terakhir tersinkron pukul 07.42. Data lain tetap dapat digunakan.`;
    return;
  }

  activeView.hidden = true;
  fallbackState.hidden = false;

  const states = {
    loading: `
      <div class="loading-skeleton" aria-label="Memuat data">
        <div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div><div class="skeleton"></div>
      </div>`,
    empty: `
      <div><div class="fallback-icon">＋</div><h2>Belum ada data pada periode ini</h2><p>Mulai dengan menambahkan kelas atau mengimpor daftar siswa. Kami akan memeriksa data sebelum disimpan.</p><button class="primary-button" data-reset-state>Impor data siswa</button></div>`,
    error: `
      <div><div class="fallback-icon" style="color:var(--red);background:var(--red-soft)">!</div><h2>Data belum dapat dimuat</h2><p>Koneksi ke server terputus. Perubahan yang sudah tersimpan tetap aman; coba lagi ketika jaringan membaik.</p><button class="primary-button" data-reset-state>Coba lagi</button></div>`,
    permission: `
      <div><div class="fallback-icon" style="color:var(--gold);background:var(--gold-soft)">⌁</div><h2>Akses dibatasi</h2><p>Halaman ini memerlukan peran wali kelas pada periode aktif. Hubungi operator bila penugasan Ibu belum diperbarui.</p><button class="secondary-button" data-reset-state>Kembali ke beranda</button></div>`
  };
  fallbackState.innerHTML = states[state] || '';
  fallbackState.querySelector('[data-reset-state]')?.addEventListener('click', () => {
    stateSelect.value = 'normal';
    renderState('normal');
  });
}

stateSelect.addEventListener('change', (event) => renderState(event.target.value));

function openSidebar() {
  sidebar.classList.add('is-open');
  sidebarBackdrop.classList.add('is-visible');
  menuButton.setAttribute('aria-expanded', 'true');
}

function closeSidebar() {
  sidebar.classList.remove('is-open');
  sidebarBackdrop.classList.remove('is-visible');
  menuButton.setAttribute('aria-expanded', 'false');
}

menuButton.addEventListener('click', () => sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar());
sidebarBackdrop.addEventListener('click', closeSidebar);
document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') closeSidebar();
  if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault();
    document.querySelector('.global-search input').focus();
  }
});

document.querySelector('#themeButton').addEventListener('click', () => {
  const dark = document.documentElement.dataset.theme === 'dark';
  document.documentElement.dataset.theme = dark ? 'light' : 'dark';
});

function showToast(message) {
  toast.querySelector('span').textContent = message;
  toast.classList.add('is-visible');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('is-visible'), 3200);
}

toast.querySelector('button').addEventListener('click', () => toast.classList.remove('is-visible'));

document.querySelectorAll('.task-check').forEach((button) => {
  button.addEventListener('click', () => {
    const item = button.closest('li');
    const complete = item.classList.toggle('is-complete');
    button.innerHTML = complete ? icon('i-check') : '';
    button.setAttribute('aria-label', complete ? 'Sudah selesai' : 'Tandai selesai');
    showToast(complete ? 'Tugas ditandai selesai.' : 'Tugas dikembalikan ke daftar.');
  });
});

const studentSearch = document.querySelector('#studentSearch');
studentSearch.addEventListener('input', () => {
  const query = studentSearch.value.toLowerCase().trim();
  document.querySelectorAll('#studentRows tr').forEach((row) => {
    row.hidden = !row.textContent.toLowerCase().includes(query);
  });
});

const rowChecks = [...document.querySelectorAll('.row-check')];
const selectAll = document.querySelector('#selectAll');
const selectionBar = document.querySelector('#selectionBar');
const selectionCount = document.querySelector('#selectionCount');

function updateSelection() {
  const count = rowChecks.filter((check) => check.checked).length;
  selectionBar.hidden = count === 0;
  selectionCount.textContent = count;
  selectAll.checked = count === rowChecks.length;
  selectAll.indeterminate = count > 0 && count < rowChecks.length;
}

rowChecks.forEach((check) => check.addEventListener('change', updateSelection));
selectAll.addEventListener('change', () => {
  rowChecks.forEach((check) => { check.checked = selectAll.checked; });
  updateSelection();
});

document.querySelector('#densitySelect').addEventListener('change', (event) => {
  document.querySelector('.table-card').classList.toggle('compact-density', event.target.value === 'compact');
});

function recalculateRow(input) {
  const row = input.closest('tr');
  const rawField = row.querySelector('.score-input:not(.remedial-input)');
  const remedialField = row.querySelector('.remedial-input');
  const raw = rawField?.value === '' ? null : Number(rawField?.value);
  const remedial = remedialField?.value === '' ? null : Number(remedialField?.value);
  const final = row.querySelector('.final-score');
  const status = row.querySelector('.status');
  if (raw === null) {
    final.textContent = '—';
    final.className = 'final-score muted';
    status.textContent = 'Belum dinilai';
    status.className = 'status neutral';
    row.classList.remove('needs-review');
    return;
  }
  const total = raw >= 75 || remedial === null ? raw : Math.max(raw, Math.min(remedial, 75));
  final.textContent = total;
  final.className = `final-score${total < 75 ? ' low' : ''}`;
  status.textContent = total < 75 ? 'Belum tuntas' : remedial !== null && raw < 75 ? 'Tuntas remedial' : 'Tuntas';
  status.className = `status ${total < 75 ? 'warning' : 'success'}`;
  row.classList.toggle('needs-review', total < 75);
}

document.querySelectorAll('.score-input').forEach((input) => {
  input.addEventListener('input', () => {
    const value = Number(input.value);
    if (input.value !== '' && (value < 0 || value > 100)) input.setCustomValidity('Nilai harus berada di antara 0 dan 100.');
    else input.setCustomValidity('');
    recalculateRow(input);
    const saveStatus = document.querySelector('#saveStatus');
    saveStatus.innerHTML = `${icon('i-clock')}Perubahan belum disimpan`;
  });
});

document.querySelector('#saveButton').addEventListener('click', () => {
  document.querySelector('#saveStatus').innerHTML = `${icon('i-check')}Tersimpan baru saja`;
  showToast('Draf nilai berhasil disimpan.');
});

document.querySelector('#submitButton').addEventListener('click', () => {
  document.querySelector('#saveStatus').innerHTML = `${icon('i-check')}Tersimpan baru saja`;
  showToast('Semua nilai berhasil disimpan.');
});

const requestedView = new URLSearchParams(window.location.search).get('view');
showView(views.some((view) => view.dataset.view === requestedView) ? requestedView : 'dashboard');
