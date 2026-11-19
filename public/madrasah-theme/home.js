const byId = (id) => document.getElementById(id);
const mobileMenu = byId('mobileMenu');
const mobileToggle = document.querySelector('[data-toggle-mobile]');
const searchInput = byId('globalSearchInput');
const searchLinks = [...document.querySelectorAll('#portalSearchResults a')];
let activeDialog = null;
let opener = null;

function closeDialog(restoreFocus = true) {
    if (!activeDialog) return;
    activeDialog.classList.add('hidden');
    if (activeDialog === mobileMenu) mobileToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    activeDialog = null;
    if (restoreFocus) opener?.focus();
}

function openDialog(dialog, trigger) {
    if (!activeDialog) opener = trigger;
    closeDialog(false);
    activeDialog = dialog;
    dialog.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    if (dialog === mobileMenu) mobileToggle.setAttribute('aria-expanded', 'true');
    const initialFocus = dialog === byId('searchModal') ? searchInput : dialog;
    initialFocus.focus();
}

function populateDialog(trigger) {
    if (trigger.dataset.image) {
        const image = byId('lightboxImg');
        image.src = trigger.dataset.image;
        image.alt = trigger.dataset.caption;
        byId('lightboxCaption').textContent = trigger.dataset.caption;
    }
    if (trigger.dataset.article) {
        const article = JSON.parse(trigger.dataset.article);
        byId('bmKategori').textContent = article.category ?? 'Warta';
        byId('bmJudul').textContent = article.title ?? '';
        byId('bmTanggal').textContent = article.date ?? '';
        byId('bmPenulis').textContent = article.author ?? 'Humas Madrasah';
        byId('bmIsi').textContent = article.text ?? '';
        const image = byId('bmGambar');
        image.src = article.image || trigger.querySelector('img')?.src || '/madrasah-theme/images/campus.webp';
        image.alt = article.title ?? '';
    }
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-open-dialog], [data-close-dialog], [data-toggle-mobile], [data-scroll-top]');
    if (trigger?.hasAttribute('data-toggle-mobile')) {
        if (activeDialog === mobileMenu) closeDialog();
        else openDialog(mobileMenu, trigger);
        return;
    }
    if (trigger?.dataset.openDialog) {
        populateDialog(trigger);
        openDialog(byId(trigger.dataset.openDialog), trigger);
        return;
    }
    if (trigger?.hasAttribute('data-close-dialog') || event.target === activeDialog) {
        closeDialog();
        return;
    }
    if (trigger?.hasAttribute('data-scroll-top')) {
        window.scrollTo({ top: 0, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
        return;
    }
    if (activeDialog === mobileMenu && event.target.closest('a[href^="#"]')) closeDialog(false);
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && activeDialog) {
        closeDialog();
        return;
    }
    if (event.key === ' ' || event.key === 'Enter') {
        const button = event.target.closest('[role="button"][data-open-dialog]');
        if (button) {
            event.preventDefault();
            button.click();
            return;
        }
    }
    if (event.key !== 'Tab' || !activeDialog) return;

    const focusable = [...activeDialog.querySelectorAll('a[href], button, input, [tabindex="0"]')]
        .filter((element) => element.getClientRects().length > 0 && !element.disabled);
    const first = focusable[0];
    const last = focusable.at(-1);
    if (event.shiftKey && (document.activeElement === first || document.activeElement === activeDialog)) {
        event.preventDefault();
        last?.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first?.focus();
    }
});

searchInput.addEventListener('input', () => {
    const query = searchInput.value.trim().toLocaleLowerCase('id-ID');
    for (const link of searchLinks) {
        link.hidden = !link.textContent.toLocaleLowerCase('id-ID').includes(query);
    }
    byId('portalSearchEmpty').hidden = searchLinks.some((link) => !link.hidden);
});

const scrollTopButton = byId('backToTopBtn');
function updateScrollTop() {
    const visible = window.scrollY > 400;
    scrollTopButton.classList.toggle('opacity-0', !visible);
    scrollTopButton.classList.toggle('pointer-events-none', !visible);
    scrollTopButton.classList.toggle('translate-y-3', !visible);
    scrollTopButton.disabled = !visible;
}
window.addEventListener('scroll', updateScrollTop, { passive: true });
updateScrollTop();

const ikmFeedback = document.querySelector('[data-ikm-submitted]');
if (ikmFeedback) openDialog(byId('ziModal'), document.querySelector('[data-open-dialog="ziModal"]'));
