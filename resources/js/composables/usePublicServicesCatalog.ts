export interface PublicServiceItem {
    id: string;
    title: string;
    description: string;
    category: 'correspondence' | 'students' | 'order_facilities' | 'portal_info';
    href: string;
    synonyms: string[];
    available: boolean;
    badge?: string;
}

export interface PublicServiceCategory {
    key: PublicServiceItem['category'];
    title: string;
    subtitle: string;
    items: PublicServiceItem[];
}

export const PUBLIC_SERVICE_CATEGORIES: PublicServiceCategory[] = [
    {
        key: 'correspondence',
        title: 'Persuratan & Tamu',
        subtitle: 'Layanan administrasi tata usaha, surat dinas, dan buku tamu.',
        items: [
            {
                id: 'ptsp',
                title: 'PTSP & Surat Keterangan',
                description: 'Pengajuan surat aktif, rekomendasi, dan layanan administrasi terpadu.',
                category: 'correspondence',
                href: '/login',
                synonyms: ['ptsp', 'tata usaha', 'surat', 'keterangan', 'administrasi'],
                available: true,
                badge: 'Portal',
            },
            {
                id: 'lacak',
                title: 'Lacak Tiket Layanan',
                description: 'Pantau status permohonan PTSP, izin, mutasi, dan legalisir.',
                category: 'correspondence',
                href: '/kontak',
                synonyms: ['lacak', 'tracking', 'resi', 'tiket', 'status'],
                available: false,
                badge: 'Segera',
            },
            {
                id: 'surat-eksternal',
                title: 'Surat Dinas Eksternal',
                description: 'Kirim surat resmi instansi beserta tanda terima agenda TU.',
                category: 'correspondence',
                href: '/kontak',
                synonyms: ['instansi', 'surat masuk', 'eksternal', 'agenda'],
                available: false,
                badge: 'Segera',
            },
            {
                id: 'buku-tamu',
                title: 'Buku Tamu Digital',
                description: 'Registrasi kunjungan tamu sekolah dan jadwal pertemuan pegawai.',
                category: 'correspondence',
                href: '/kontak',
                synonyms: ['tamu', 'kunjungan', 'resepsionis', 'pegawai'],
                available: false,
                badge: 'Segera',
            },
        ],
    },
    {
        key: 'students',
        title: 'Siswa & Alumni',
        subtitle: 'Penerimaan peserta didik, perizinan, kelulusan, dan alumni.',
        items: [
            {
                id: 'ppdb',
                title: 'SPMB / PPDB Online',
                description: 'Informasi jalur pendaftaran, jadwal, syarat, dan status seleksi.',
                category: 'students',
                href: '/ppdb',
                synonyms: ['ppdb', 'spmb', 'daftar', 'pendaftaran', 'siswa baru'],
                available: true,
            },
            {
                id: 'izin-siswa',
                title: 'Izin & Dispensasi Siswa',
                description: 'Pengajuan izin tidak masuk atau dispensasi kegiatan oleh wali.',
                category: 'students',
                href: '/login',
                synonyms: ['izin', 'sakit', 'dispensasi', 'wali', 'absensi'],
                available: true,
                badge: 'Portal',
            },
            {
                id: 'verifikasi-alumni',
                title: 'Verifikasi Siswa & Alumni',
                description: 'Cek keaktifan siswa, keabsahan SKL, ijazah, dan legalisir.',
                category: 'students',
                href: '/profil-sekolah',
                synonyms: ['alumni', 'ijazah', 'skl', 'legalisir', 'verifikasi', 'mutasi'],
                available: false,
                badge: 'Segera',
            },
            {
                id: 'tracer-alumni',
                title: 'Tracer & Direktori Alumni',
                description: 'Pembaruan data kuliah/karier alumni dan kartu E-KTA.',
                category: 'students',
                href: '/login',
                synonyms: ['tracer', 'alumni', 'ekta', 'beasiswa', 'karier'],
                available: false,
                badge: 'Segera',
            },
        ],
    },
    {
        key: 'order_facilities',
        title: 'Ketertiban, Aduan & Pustaka',
        subtitle: 'Kanal pengaduan aman, perpustakaan, unduhan, dan zona integritas.',
        items: [
            {
                id: 'siap-lapor',
                title: 'Siap Lapor / Pengaduan',
                description: 'Sampaikan laporan bernama atau anonim secara aman dan terpantau.',
                category: 'order_facilities',
                href: '/kontak',
                synonyms: ['aduan', 'lapor', 'siap lapor', 'bullying', 'satgas', 'anonim'],
                available: false,
                badge: 'Segera',
            },
            {
                id: 'perpustakaan',
                title: 'Katalog Perpustakaan',
                description: 'Telusuri koleksi buku, e-book, usulan pustaka, dan bebas pustaka.',
                category: 'order_facilities',
                href: '/akademik-fasilitas',
                synonyms: ['perpustakaan', 'buku', 'sipus', 'ebook', 'pustaka'],
                available: true,
            },
            {
                id: 'pusat-unduh',
                title: 'Pusat Unduh & Dokumen',
                description: 'Formulir resmi, kalender akademik, tata tertib, dan SOP layanan.',
                category: 'order_facilities',
                href: '/pusat-unduh',
                synonyms: ['unduh', 'download', 'formulir', 'kalender', 'sop', 'brosur'],
                available: true,
            },
            {
                id: 'zona-integritas',
                title: 'Zona Integritas & IKM',
                description: 'Maklumat pelayanan, standar mutu, dan survei kepuasan masyarakat.',
                category: 'order_facilities',
                href: '/zona-integritas',
                synonyms: ['zi', 'zona integritas', 'ikm', 'survei', 'maklumat'],
                available: true,
            },
        ],
    },
];

export function filterServiceCategories(query: string): PublicServiceCategory[] {
    const normalized = query.trim().toLocaleLowerCase('id-ID');
    if (!normalized) {
        return PUBLIC_SERVICE_CATEGORIES;
    }

    return PUBLIC_SERVICE_CATEGORIES
        .map((category) => ({
            ...category,
            items: category.items.filter((item) => {
                const haystack = `${item.title} ${item.description} ${category.title} ${item.synonyms.join(' ')}`.toLocaleLowerCase('id-ID');
                return haystack.includes(normalized);
            }),
        }))
        .filter((category) => category.items.length > 0);
}
