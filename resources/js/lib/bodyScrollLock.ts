const owners = new Set<symbol>();
let previousOverflow = '';

export function lockBodyScroll(owner: symbol) {
    if (owners.has(owner)) return;
    if (owners.size === 0) previousOverflow = document.body.style.overflow;
    owners.add(owner);
    document.body.style.overflow = 'hidden';
}

export function unlockBodyScroll(owner: symbol) {
    if (!owners.delete(owner)) return;
    if (owners.size === 0) document.body.style.overflow = previousOverflow;
}
