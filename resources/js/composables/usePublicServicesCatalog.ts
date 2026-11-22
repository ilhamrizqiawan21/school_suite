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
                href: '/layanan/ptsp',
                synonyms: ['ptsp', 'tata usaha', 'surat', 'keterangan', 'administrasi'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'lacak',
                title: 'Lacak Tiket Layanan',
                description: 'Pantau status permohonan PTSP, izin, mutasi, dan legalisir.',
                category: 'correspondence',
                href: '/lacak',
                synonyms: ['lacak', 'tracking', 'resi', 'tiket', 'status'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'surat-eksternal',
                title: 'Surat Dinas Eksternal',
                description: 'Kirim surat resmi instansi beserta tanda terima agenda TU.',
                category: 'correspondence',
                href: '/layanan/surat-dinas',
                synonyms: ['instansi', 'surat masuk', 'eksternal', 'agenda'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'buku-tamu',
                title: 'Buku Tamu Digital',
                description: 'Registrasi kunjungan tamu sekolah dan jadwal pertemuan pegawai.',
                category: 'correspondence',
                href: '/layanan/buku-tamu',
                synonyms: ['tamu', 'kunjungan', 'resepsionis', 'pegawai'],
                available: true,
                badge: 'Aktif',
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
                href: '/layanan/izin-siswa',
                synonyms: ['izin', 'sakit', 'dispensasi', 'wali', 'absensi'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'mutasi-siswa',
                title: 'Mutasi Masuk & Keluar',
                description: 'Pengajuan pindah masuk/keluar siswa, unggah syarat, dan pelacakan.',
                category: 'students',
                href: '/layanan/mutasi',
                synonyms: ['mutasi', 'pindah sekolah', 'rekomendasi pindah'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'kelulusan-skl',
                title: 'Kelulusan & Unduh SKL',
                description: 'Cek pengumuman kelulusan sesuai jadwal dan unduh SKL resmi.',
                category: 'students',
                href: '/layanan/kelulusan',
                synonyms: ['kelulusan', 'skl', 'surat keterangan lulus'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'verifikasi-alumni',
                title: 'Verifikasi Siswa & Alumni',
                description: 'Cek keaktifan siswa, keabsahan SKL, ijazah, dan legalisir.',
                category: 'students',
                href: '/verifikasi',
                synonyms: ['alumni', 'ijazah', 'skl', 'legalisir', 'verifikasi', 'mutasi'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'legalisir',
                title: 'Legalisir Ijazah & Kuasa',
                description: 'Ajukan legalisir ijazah/SKL serta buat Surat Kuasa atau SPTJM.',
                category: 'students',
                href: '/layanan/legalisir',
                synonyms: ['legalisir', 'ijazah', 'surat kuasa', 'sptjm', 'alumni'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'tracer-alumni',
                title: 'Tracer & Direktori Alumni',
                description: 'Pembaruan data kuliah/karier alumni, direktori terverifikasi, dan kartu E-KTA.',
                category: 'students',
                href: '/layanan/tracer-alumni',
                synonyms: ['tracer', 'alumni', 'ekta', 'beasiswa', 'karier'],
                available: true,
                badge: 'Aktif',
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
                href: '/layanan/lapor',
                synonyms: ['aduan', 'lapor', 'siap lapor', 'bullying', 'satgas', 'anonim'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'transparansi-lapor',
                title: 'Transparansi Penanganan Aduan',
                description: 'Ringkasan publik dan statistik penanganan pengaduan Satgas.',
                category: 'order_facilities',
                href: '/lapor/transparansi',
                synonyms: ['transparansi', 'aduan', 'statistik', 'satgas'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'perpustakaan',
                title: 'Katalog & Layanan Perpustakaan',
                description: 'Telusuri koleksi buku, e-book, kunjungan QR, usulan pustaka, dan bebas pustaka.',
                category: 'order_facilities',
                href: '/perpustakaan',
                synonyms: ['perpustakaan', 'buku', 'sipus', 'ebook', 'pustaka', 'bebas pustaka'],
                available: true,
                badge: 'Aktif',
            },
            {
                id: 'proposal-mandiri',
                title: 'Portal Proposal & LPJ Mandiri',
                description: 'Pengajuan proposal kegiatan siswa/organisasi, RAB terstruktur, dan unggah bukti LPJ.',
                category: 'order_facilities',
                href: '/layanan/proposal',
                synonyms: ['proposal', 'lpj', 'rab', 'osis', 'kegiatan', 'webinar'],
                available: true,
                badge: 'Aktif',
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
