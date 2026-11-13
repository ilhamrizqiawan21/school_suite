export async function assessmentRequest(path: string, body?: unknown, method = 'POST'): Promise<Response> {
    const response = await fetch(path, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
            ...(body instanceof FormData || body === undefined ? {} : { 'Content-Type': 'application/json' }),
        },
        body: body === undefined ? undefined : body instanceof FormData ? body : JSON.stringify(body),
    });
    if (!response.ok) {
        const error = await response.json().catch(() => ({}));
        throw new Error(Object.values(error.errors ?? {}).flat().join(' ') || error.message || 'Permintaan gagal. Coba lagi.');
    }
    return response;
}

export function saveBlob(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.click();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}
