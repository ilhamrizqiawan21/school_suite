import { onBeforeUnmount, onMounted } from 'vue';
import { lockBodyScroll, unlockBodyScroll } from '@/lib/bodyScrollLock';

const focusableSelector = 'button:not([disabled]), a[href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), summary, [contenteditable="true"], [tabindex]:not([tabindex="-1"])';

export function useDialogAccessibility(enabled: () => boolean = () => true) {
    let observer: MutationObserver | undefined;
    let scheduled = false;
    const scrollOwner = Symbol('admin-dialogs');
    let locked = false;
    let sequence = 0;
    const openDialogs = new Map<HTMLElement, HTMLElement | null>();

    const visible = (element: HTMLElement) => element.isConnected && !element.hidden
        && !element.closest('[inert], [aria-hidden="true"]')
        && element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden';
    const topDialog = () => [...openDialogs.keys()].filter(visible).at(-1);
    const controls = (dialog: HTMLElement) => Array.from(dialog.querySelectorAll<HTMLElement>(focusableSelector))
        .filter((element) => visible(element) && !element.matches(':disabled') && element.getAttribute('tabindex') !== '-1');

    const synchronize = () => {
        scheduled = false;
        let restoreTarget: HTMLElement | null = null;
        for (const [dialog, previous] of openDialogs) {
            if (!visible(dialog)) {
                openDialogs.delete(dialog);
                if (previous?.isConnected) restoreTarget = previous;
            }
        }
        const candidates = Array.from(document.querySelectorAll<HTMLElement>('[role="dialog"], [role="alertdialog"], .modal-backdrop, .modal-overlay'))
            .filter(() => enabled())
            .filter(visible)
            .filter((dialog, _, all) => !all.some((other) => other !== dialog && other.contains(dialog)));
        for (const dialog of candidates) {
            if (openDialogs.has(dialog)) continue;
            openDialogs.set(dialog, document.activeElement instanceof HTMLElement ? document.activeElement : null);
            if (!['dialog', 'alertdialog'].includes(dialog.getAttribute('role') ?? '')) dialog.setAttribute('role', 'dialog');
            dialog.setAttribute('aria-modal', 'true');
            dialog.tabIndex = -1;
            if (!dialog.hasAttribute('aria-label') && !dialog.hasAttribute('aria-labelledby')) {
                const heading = dialog.querySelector<HTMLElement>('h1, h2, h3, .modal-title');
                if (heading) {
                    heading.id ||= `admin-dialog-title-${++sequence}`;
                    dialog.setAttribute('aria-labelledby', heading.id);
                }
            }
            queueMicrotask(() => {
                if (visible(dialog) && !dialog.contains(document.activeElement)) {
                    (controls(dialog)[0] ?? dialog).focus();
                }
            });
        }
        if (openDialogs.size && !locked) {
            lockBodyScroll(scrollOwner);
            locked = true;
        } else if (!openDialogs.size && locked) {
            unlockBodyScroll(scrollOwner);
            locked = false;
        }
        if (restoreTarget?.isConnected && (!topDialog() || topDialog()!.contains(restoreTarget))) {
            restoreTarget.focus();
        }
    };
    const schedule = () => {
        if (scheduled) return;
        scheduled = true;
        queueMicrotask(synchronize);
    };
    const keydown = (event: KeyboardEvent) => {
        const dialog = topDialog();
        if (!enabled() || !dialog || event.key !== 'Tab') return;
        const items = controls(dialog);
        const first = items[0];
        const last = items.at(-1);
        if (!first || (event.shiftKey && document.activeElement === first)
            || (!event.shiftKey && document.activeElement === last) || !dialog.contains(document.activeElement)) {
            event.preventDefault();
            event.stopPropagation();
            (event.shiftKey ? last ?? dialog : first ?? dialog).focus();
        }
    };
    const focusin = (event: FocusEvent) => {
        const dialog = topDialog();
        if (enabled() && dialog && event.target instanceof Node && !dialog.contains(event.target)) {
            (controls(dialog)[0] ?? dialog).focus();
        }
    };
    onMounted(() => {
        observer = new MutationObserver(schedule);
        observer.observe(document.body, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style', 'hidden', 'inert', 'aria-hidden'] });
        document.addEventListener('keydown', keydown, true);
        document.addEventListener('focusin', focusin, true);
        synchronize();
    });
    onBeforeUnmount(() => {
        observer?.disconnect();
        document.removeEventListener('keydown', keydown, true);
        document.removeEventListener('focusin', focusin, true);
        unlockBodyScroll(scrollOwner);
        openDialogs.clear();
    });
}
