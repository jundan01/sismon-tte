import pptxgen from 'pptxgenjs';

// 1. Inisialisasi Presentasi
const pres = new pptxgen();

// Konfigurasi Metadata
pres.title = 'Presentasi Sistem Monitoring TTE & Email Dinas Resmi Pemkot Cirebon';
pres.subject = 'Penjelasan Lengkap Arsitektur, Fitur & Operasional Sistem V4.1';
pres.author = 'Diskominfo / Bidang Persandian Pemerintah Kota Cirebon';
pres.company = 'Pemerintah Kota Cirebon';
pres.revision = '4.1';

// Layout 16:9 Widescreen
pres.layout = 'LAYOUT_16x9';

// Palet Warna Resmi IBM Carbon / Diskominfo
const COLORS = {
    BG_DARK: '0F172A',
    BG_CARD: '1E293B',
    BG_CARD_ALT: '334155',
    TEXT_WHITE: 'FFFFFF',
    TEXT_MUTED: '94A3B8',
    CYAN: '06B6D4',
    BLUE: '0284C7',
    GREEN: '10B981',
    AMBER: 'F59E0B',
    ROSE: 'F43F5E',
    BORDER: '334155',
};

// Helper: Background Standar Slide
function applySlideBase(slide, categoryText, titleText, subtitleText = '') {
    slide.background = { color: COLORS.BG_DARK };

    // Header Category
    slide.addText(categoryText.toUpperCase(), {
        x: 0.8,
        y: 0.4,
        w: 8.0,
        h: 0.3,
        fontSize: 10,
        fontFace: 'Arial',
        color: COLORS.CYAN,
        bold: true,
        letterSpacing: 2,
    });

    // Header Title
    slide.addText(titleText, {
        x: 0.8,
        y: 0.65,
        w: 11.5,
        h: 0.5,
        fontSize: 20,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });

    // Header Line
    slide.addShape(pres.ShapeType.line, {
        x: 0.8,
        y: 1.25,
        w: 11.7,
        h: 0,
        line: { color: COLORS.BORDER, width: 1 },
    });

    // Footer
    slide.addText('DISKOMINFO / BIDANG PERSANDIAN KOTA CIREBON • SISTEM MONITORING TTE V4.1', {
        x: 0.8,
        y: 7.1,
        w: 10.0,
        h: 0.3,
        fontSize: 9,
        fontFace: 'Courier New',
        color: COLORS.TEXT_MUTED,
    });
}

// ==========================================
// SLIDE 1: COVER
// ==========================================
{
    const slide = pres.addSlide();
    slide.background = { color: COLORS.BG_DARK };

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.5,
        w: 4.5,
        h: 0.35,
        fill: { color: '1E293B' },
        line: { color: COLORS.CYAN, width: 1 },
    });
    slide.addText('DOKUMEN PRESENTASI RESMI SKALA NASIONAL', {
        x: 0.8,
        y: 1.5,
        w: 4.5,
        h: 0.35,
        fontSize: 9,
        fontFace: 'Courier New',
        color: COLORS.CYAN,
        bold: true,
        align: 'center',
    });

    slide.addText('SISTEM MONITORING PENERBITAN TTE\n& PELAYANAN EMAIL DINAS RESMI', {
        x: 0.8,
        y: 2.2,
        w: 11.5,
        h: 1.8,
        fontSize: 32,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
        lineSpacing: 38,
    });

    slide.addText('Pemerintah Kota Cirebon — Dinas Komunikasi dan Informatika / Bidang Persandian\nTransformasi Digital SPBE Berdaulat, Efisien, dan Aman (V4.1 Post-Audit Hardened)', {
        x: 0.8,
        y: 4.2,
        w: 11.5,
        h: 0.9,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
    });

    // Metadata Box
    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 5.5,
        w: 11.7,
        h: 1.1,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.BORDER, width: 1 },
    });

    slide.addText([
        { text: 'FRAMEWORK: ', options: { bold: true, color: COLORS.CYAN, fontSize: 10 } },
        { text: 'Laravel 13 + PHP 8.3 FPM    ', options: { color: COLORS.TEXT_WHITE, fontSize: 10 } },
        { text: 'DATABASE: ', options: { bold: true, color: COLORS.CYAN, fontSize: 10 } },
        { text: 'PostgreSQL 16 + Redis 7    ', options: { color: COLORS.TEXT_WHITE, fontSize: 10 } },
        { text: 'PERIMETER: ', options: { bold: true, color: COLORS.CYAN, fontSize: 10 } },
        { text: 'Zero-Trust VPN Gateway    ', options: { color: COLORS.TEXT_WHITE, fontSize: 10 } },
        { text: 'VERSI: ', options: { bold: true, color: COLORS.CYAN, fontSize: 10 } },
        { text: '3.2.0-PROD (V4.1)', options: { color: COLORS.TEXT_WHITE, fontSize: 10 } },
    ], { x: 1.0, y: 5.7, w: 11.3, h: 0.7, fontFace: 'Courier New', align: 'center' });

    slide.addNotes('Selamat pagi/siang bapak/ibu sekalian. Hari ini kami mempresentasikan Sistem Monitoring Penerbitan TTE dan Pelayanan Email Dinas Resmi Pemerintah Kota Cirebon versi 4.1. Sistem ini telah melalui tahap audit kode program menyeluruh dan siap beroperasi di level produksi skala nasional.');
}

// ==========================================
// SLIDE 2: LATAR BELAKANG & URGENSI
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '01. PENDAHULUAN & REGULASI', 'Latar Belakang & Urgensi Pembangunan Sistem');

    const cards = [
        {
            title: '🏛️ Mandat SPBE & BSrE BSSN',
            text: 'Sesuai Perpres No. 95/2018, seluruh naskah dinas kedinasan (SK, SPT, Nota Dinas) wajib beralih ke dokumen elektronik terotentikasi. Diskominfo bermitra resmi dengan BSrE BSSN sebagai Registration Authority.',
            color: COLORS.CYAN,
            x: 0.8,
        },
        {
            title: '🛡️ Kepatuhan UU PDP No. 27/2022',
            text: 'Pengumpulan data sensitif NIK 16 digit & NIP 18 digit ASN mewajibkan Pemkot bertindak sebagai Pengendali Data Pribadi yang menerapkan enkripsi at-rest dan isolasi akses ketat.',
            color: COLORS.AMBER,
            x: 4.8,
        },
        {
            title: '📧 Syarat Email Dinas Resmi',
            text: 'BSrE BSSN mewajibkan akun email dinas resmi @cirebonkota.go.id sebagai identitas tunggal penerbitan sertifikat TTE bagi seluruh aparatur sipil negara.',
            color: COLORS.GREEN,
            x: 8.8,
        },
    ];

    cards.forEach(c => {
        slide.addShape(pres.ShapeType.rect, {
            x: c.x,
            y: 1.6,
            w: 3.7,
            h: 3.4,
            fill: { color: COLORS.BG_CARD },
            line: { color: c.color, width: 1.5 },
        });
        slide.addText(c.title, {
            x: c.x + 0.2,
            y: 1.8,
            w: 3.3,
            h: 0.4,
            fontSize: 13,
            fontFace: 'Arial',
            color: COLORS.TEXT_WHITE,
            bold: true,
        });
        slide.addText(c.text, {
            x: c.x + 0.2,
            y: 2.3,
            w: 3.3,
            h: 2.5,
            fontSize: 10,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            lineSpacing: 15,
        });
    });

    // Bottom banner
    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 5.3,
        w: 11.7,
        h: 1.5,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.BORDER, width: 1 },
    });
    slide.addText('⚠️ Evaluasi Masalah Ekosistem Lama:', {
        x: 1.0,
        y: 5.4,
        w: 11.3,
        h: 0.3,
        fontSize: 11,
        fontFace: 'Arial',
        color: COLORS.ROSE,
        bold: true,
    });
    slide.addText([
        { text: '1. Penggunaan Google Forms: ', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: 'Data NIK/NIP tersimpan di cloud luar negeri tanpa enkripsi lokal (Melanggar UU PDP).\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '2. Usulan Email Tidak Seragam: ', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: 'Banyak ASN belum punya email dinas atau format acak yang memicu konflik mailbox.\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '3. Sertifikat Mati Senyap: ', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: 'Masa aktif 2 tahun habis tanpa peringatan H-3, menghambat penandatanganan dokumen dinas.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 1.0, y: 5.75, w: 11.3, h: 0.9, fontSize: 9.5, fontFace: 'Arial', lineSpacing: 14 });

    slide.addNotes('Latar belakang sistem ini berfokus pada 3 regulasi utama: SPBE 95/2018, UU PDP 27/2022, dan aturan penerbitan BSrE BSSN. Sistem lama memiliki kerentanan besar karena memakai Google Forms dan tidak memiliki sistem monitoring kadaluarsa.');
}

// ==========================================
// SLIDE 3: MASALAH VS SOLUSI V4.1
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '02. ANALISIS MASALAH & SOLUSI', 'Matriks Masalah Operasional vs Solusi Teruji V4.1');

    const rows = [
        [
            { text: 'Masalah Operasional Lama', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
            { text: 'Risiko & Dampak Sistemik', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
            { text: 'Solusi Teruji Sistem V4.1 (Hardened)', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
        ],
        [
            { text: 'Form Publik Google Forms' },
            { text: 'Pelanggaran UU PDP, data di server luar negeri tanpa enkripsi at-rest.' },
            { text: 'Portal Mandiri On-Premise: Enkripsi AES-256-GCM + Blind Indexing PostgreSQL 16.' },
        ],
        [
            { text: 'Login Admin Terbuka di Internet' },
            { text: 'Target credential stuffing, bot brute-force, port scanning, dan DDoS.' },
            { text: 'Zero-Trust VPN Gateway: Rute /gateway/login khusus VPN Diskominfo + Stealth 404.' },
        ],
        [
            { text: 'Format Usulan Email Acak' },
            { text: 'Konflik mailbox di mail server dan penolakan penerbitan oleh BSrE.' },
            { text: 'Auto Email Generator: nama.nip@cirebonkota.go.id + Widget Rekap Email AJAX 1-Klik.' },
        ],
        [
            { text: 'Beban Unggah Dokumen Berkas' },
            { text: 'Koneksi seluler pelosok putus, gagal upload file berulang kali.' },
            { text: 'Form Ramping Bebas Upload (< 5KB) + SessionStorage draft auto-save aman.' },
        ],
        [
            { text: 'Ekspor Data Massal Crash (OOM)' },
            { text: 'PHP Fatal Error / Out of Memory saat ekspor ratusan ribu records.' },
            { text: 'Database Cursor Streaming: Single query leftJoin cursor, RAM stabil < 20MB, 0 N+1.' },
        ],
    ];

    slide.addTable(rows, {
        x: 0.8,
        y: 1.6,
        w: 11.7,
        colW: [2.8, 4.2, 4.7],
        fontSize: 9,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
        border: { color: COLORS.BORDER, width: 0.5 },
        fill: { color: COLORS.BG_CARD },
    });

    slide.addNotes('Tabel matriks komparasi ini menunjukkan bahwa seluruh 5 masalah konvensional telah dijawab dengan solusi arsitektural yang terbukti stabil dan telah diaudit.');
}

// ==========================================
// SLIDE 4: TUJUAN & TARGET KPI
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '03. SASARAN STRATEGIS', 'Tujuan Pembangunan & Target KPI Operasional');

    // Kolom Kiri: Sasaran Bisnis
    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.6,
        w: 5.6,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.CYAN, width: 1.5 },
    });
    slide.addText('🎯 Sasaran Bisnis & Tata Kelola', {
        x: 1.0,
        y: 1.8,
        w: 5.2,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText([
        { text: '• Standardisasi Satu Pintu:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Melayani pendaftaran ASN dari 20 SKPD Kota Cirebon secara terpadu tanpa formulir manual.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Efisiensi Rekapitulasi:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Otomatisasi penetapan email dinas dan pencatatan nomor sertifikat BSrE secara deterministik.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Lifecycle Management Proaktif:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Monitoring masa aktif sertifikat harian dengan zona peringatan dini H-3 Warning Zone.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Transparansi Eksekutif:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Dashboard analitik real-time sub-milidetik untuk pimpinan Diskominfo.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 1.0, y: 2.3, w: 5.2, h: 4.3, fontSize: 10, fontFace: 'Arial', lineSpacing: 16 });

    // Kolom Kanan: 4 Stat Boxes
    const kpis = [
        { val: '≥ 99.9%', label: 'System Uptime SLA', color: COLORS.GREEN, x: 6.8, y: 1.6 },
        { val: '< 2.0 Detik', label: 'Page Load Jaringan 3G', color: COLORS.CYAN, x: 9.7, y: 1.6 },
        { val: '< 20 MB', label: 'RAM PHP Saat Ekspor CSV', color: COLORS.AMBER, x: 6.8, y: 4.3 },
        { val: '< 25 ms', label: 'Latensi Query Dashboard', color: COLORS.BLUE, x: 9.7, y: 4.3 },
    ];

    kpis.forEach(k => {
        slide.addShape(pres.ShapeType.rect, {
            x: k.x,
            y: k.y,
            w: 2.7,
            h: 2.5,
            fill: { color: COLORS.BG_CARD },
            line: { color: k.color, width: 1.5 },
        });
        slide.addText(k.val, {
            x: k.x + 0.1,
            y: k.y + 0.4,
            w: 2.5,
            h: 0.8,
            fontSize: 24,
            fontFace: 'Courier New',
            color: k.color,
            bold: true,
            align: 'center',
        });
        slide.addText(k.label, {
            x: k.x + 0.1,
            y: k.y + 1.3,
            w: 2.5,
            h: 0.8,
            fontSize: 10,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            align: 'center',
            bold: true,
        });
    });

    slide.addNotes('Target KPI operasional menetapkan uptime 99.9%, load time di bawah 2 detik pada jaringan 3G, RAM stabil di bawah 20MB saat ekspor laporan, dan query dashboard di bawah 25ms.');
}

// ==========================================
// SLIDE 5: STACK TEKNOLOGI
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '04. ARSITEKTUR TEKNOLOGI', 'Stack Teknologi Enterprise Skala Nasional');

    const stacks = [
        {
            title: '⚙️ Backend Core',
            tech: 'Laravel 13 + PHP 8.3 FPM',
            desc: '• OPcache JIT Optimization\n• Clean Domain Architecture\n• Action, Observer & Policy pattern\n• StreamedResponse memory safe',
            color: COLORS.CYAN,
            x: 0.8,
        },
        {
            title: '🗄️ Database Engine',
            tech: 'PostgreSQL 16 Alpine',
            desc: '• Native Range Partitioning bulanan\n• Partial Unique Indexing\n• Kolom JSONB + GIN Indexing\n• BIGINT Identity Primary Key',
            color: COLORS.GREEN,
            x: 3.8,
        },
        {
            title: '⚡ In-Memory Buffer',
            tech: 'Redis 7 Alpine',
            desc: '• In-memory cache dashboard\n• Async Queue "audit-logs"\n• AOF Persistence & Requirepass\n• Non-blocking Worker Supervisor',
            color: COLORS.AMBER,
            x: 6.8,
        },
        {
            title: '🛡️ Edge & Frontend',
            tech: 'Nginx + Carbon Flat UI',
            desc: '• TLS 1.3 Strict & HSTS 2 Tahun\n• Rate Limiting 5r/m & 30r/s\n• 100% Zero External CDN lokal\n• Desain Flat 0px Radius WCAG 2.1',
            color: COLORS.BLUE,
            x: 9.8,
        },
    ];

    stacks.forEach(s => {
        slide.addShape(pres.ShapeType.rect, {
            x: s.x,
            y: 1.6,
            w: 2.7,
            h: 3.8,
            fill: { color: COLORS.BG_CARD },
            line: { color: s.color, width: 1.5 },
        });
        slide.addText(s.title, {
            x: s.x + 0.15,
            y: 1.8,
            w: 2.4,
            h: 0.3,
            fontSize: 12,
            fontFace: 'Arial',
            color: COLORS.TEXT_WHITE,
            bold: true,
        });
        slide.addText(s.tech, {
            x: s.x + 0.15,
            y: 2.15,
            w: 2.4,
            h: 0.4,
            fontSize: 10,
            fontFace: 'Arial',
            color: s.color,
            bold: true,
        });
        slide.addText(s.desc, {
            x: s.x + 0.15,
            y: 2.6,
            w: 2.4,
            h: 2.6,
            fontSize: 9,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            lineSpacing: 14,
        });
    });

    // Docker Banner
    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 5.6,
        w: 11.7,
        h: 1.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.BORDER, width: 1 },
    });
    slide.addText('🐳 Isolasi Kontainerisasi Docker Compose V2 (Enterprise Isolation):', {
        x: 1.0,
        y: 5.7,
        w: 11.3,
        h: 0.3,
        fontSize: 10.5,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText('• Named Volumes (vendor_data, node_data, storage_data) bebas I/O lag  • Network backend (internal: true) tersembunyi dari publik  • Non-root user (1000:1000)', {
        x: 1.0,
        y: 6.05,
        w: 11.3,
        h: 0.5,
        fontSize: 9,
        fontFace: 'Courier New',
        color: COLORS.CYAN,
    });

    slide.addNotes('Teknologi backend menggunakan Laravel 13 dan PHP 8.3, didukung PostgreSQL 16 dengan Native Partitioning dan Redis 7. Seluruh infrastruktur dibungkus dalam Docker Compose dengan named volumes terisolasi.');
}

// ==========================================
// SLIDE 6: ALUR BISNIS DUA TAHAP
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '05. ALUR BISNIS & TRANSAKSI', 'Siklus Hidup Transaksi Dua Tahap (Two-Stage Verification)');

    const stages = [
        {
            num: 'TAHAP 1',
            actor: 'ASN Pemohon (Publik)',
            title: 'Pendaftaran Mandiri',
            desc: '• ASN input Nama, NIK, NIP, HP, SKPD, Jabatan, Golongan.\n• Sistem generate usulan email dinas baku.\n• AES-256-GCM enkripsi kolom NIK/NIP.\n• Terbit Nomor Referensi Registrasi unik.\n• Status: MENUNGGU_VERIFIKASI',
            color: COLORS.CYAN,
            x: 0.8,
        },
        {
            num: 'TAHAP 2',
            actor: 'Superadmin (VPN Gateway)',
            title: 'Verifikasi & Rekap Email',
            desc: '• Superadmin review data ASN via nomor ref.\n• Klik "Gunakan Usulan" / edit manual.\n• Klik "Kunci & Rekap Email" via AJAX.\n• Keputusan: Setujui (DISETUJUI) atau Tolak (DITOLAK + alasan penolakan).',
            color: COLORS.AMBER,
            x: 4.8,
        },
        {
            num: 'TAHAP 3',
            actor: 'BSrE BSSN & Cron Scanner',
            title: 'Penerbitan & Monitoring',
            desc: '• Mailbox fisik dibuat di server email.\n• Superadmin input Nomor Sertifikat BSrE.\n• Sistem kalkulasi kadaluarsa (+2 Tahun).\n• Status: AKTIF.\n• Daily Scanner 00:01 WIB: H-3 & Expired.',
            color: COLORS.GREEN,
            x: 8.8,
        },
    ];

    stages.forEach(s => {
        slide.addShape(pres.ShapeType.rect, {
            x: s.x,
            y: 1.6,
            w: 3.7,
            h: 4.3,
            fill: { color: COLORS.BG_CARD },
            line: { color: s.color, width: 1.5 },
        });

        slide.addText(s.num, {
            x: s.x + 0.2,
            y: 1.8,
            w: 1.5,
            h: 0.3,
            fontSize: 9,
            fontFace: 'Courier New',
            color: s.color,
            bold: true,
        });

        slide.addText(s.actor, {
            x: s.x + 1.7,
            y: 1.8,
            w: 1.8,
            h: 0.3,
            fontSize: 9,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            align: 'right',
        });

        slide.addText(s.title, {
            x: s.x + 0.2,
            y: 2.15,
            w: 3.3,
            h: 0.4,
            fontSize: 13,
            fontFace: 'Arial',
            color: COLORS.TEXT_WHITE,
            bold: true,
        });

        slide.addText(s.desc, {
            x: s.x + 0.2,
            y: 2.6,
            w: 3.3,
            h: 3.1,
            fontSize: 9.5,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            lineSpacing: 15,
        });
    });

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 6.1,
        w: 11.7,
        h: 0.7,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.BORDER, width: 1 },
    });
    slide.addText('🛡️ Segregation of Duty Audit: Verifikasi Tahap 1 (verified_by) & Penerbitan Tahap 2 (verified_by_tahap2) dicatat pada kolom terpisah.', {
        x: 1.0,
        y: 6.25,
        w: 11.3,
        h: 0.4,
        fontSize: 9.5,
        fontFace: 'Arial',
        color: COLORS.CYAN,
        bold: true,
    });

    slide.addNotes('Alur transaksi dibagi menjadi 3 tahap teratur: Pendaftaran mandiri oleh ASN publik; Verifikasi dan rekapitulasi email oleh Superadmin via VPN; serta Penerbitan sertifikat BSrE dengan kalkulasi otomatis 2 tahun dan scanning harian.');
}

// ==========================================
// SLIDE 7: INOVASI FORMULIR PUBLIK
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '06. INOVASI FORMULIR PUBLIK', 'Portal Publik Mandiri & Resiliensi Low-Bandwidth');

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.6,
        w: 5.6,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.CYAN, width: 1.5 },
    });
    slide.addText('⚡ Desain Ramping Bebas Upload', {
        x: 1.0,
        y: 1.8,
        w: 5.2,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText([
        { text: '• Eliminasi Berkas Fisik:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Menghapus kewajiban upload berkas puluhan MB. Transaksi berupa data teks murni (< 5 KB payload).\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• 100% Zero External CDN:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Font IBM Plex dan styling CSS dibundle lokal. Portal tetap bisa diakses pada jaringan intranet tertutup tanpa koneksi Google Fonts / Cloudflare.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Waktu Muat Kilat:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Halaman terbuka dalam < 2 detik pada koneksi seluler 2G/3G di kantor kelurahan/kecamatan pelosok.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 1.0, y: 2.3, w: 5.2, h: 4.3, fontSize: 10, fontFace: 'Arial', lineSpacing: 16 });

    slide.addShape(pres.ShapeType.rect, {
        x: 6.8,
        y: 1.6,
        w: 5.7,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.GREEN, width: 1.5 },
    });
    slide.addText('🛡️ Auto-Save Aman & CAPTCHA Cerdas', {
        x: 7.0,
        y: 1.8,
        w: 5.3,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText([
        { text: '• Auto-Save Sesi (sessionStorage):\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Draft tersimpan per ketikan (tte_registration_draft_temp). Aman dari risiko tab browser tertutup.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Kepatuhan Mutlak UU PDP:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Field NIK 16 digit dan NIP 18 digit sengaja TIDAK disimpan di browser untuk mencegah kebocoran pada workstation bersama.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Router CAPTCHA Cerdas:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Google reCAPTCHA v2/v3 diproteksi timeout 3 detik. Jika lambat/gagal, otomatis aktifkan Local Math CAPTCHA (HMAC-SHA256).', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 7.0, y: 2.3, w: 5.3, h: 4.3, fontSize: 10, fontFace: 'Arial', lineSpacing: 16 });

    slide.addNotes('Portal publik dibuat sangat ringan tanpa upload berkas, 100% lokal tanpa CDN luar, dan auto-save draft ke sessionStorage tanpa menyimpan NIK/NIP demi kepatuhan UU PDP.');
}

// ==========================================
// SLIDE 8: GENERATOR & REKAP EMAIL
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '07. OTOMASI EMAIL DINAS', 'Auto Email Generator & AJAX Recap Widget');

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.6,
        w: 5.6,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.CYAN, width: 1.5 },
    });
    slide.addText('🤖 Logika Pembangkitan Otomatis', {
        x: 1.0,
        y: 1.8,
        w: 5.2,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText('Sistem menjalankan EmailGeneratorService yang membersihkan spasi, simbol khusus, dan seluruh gelar ASN:', {
        x: 1.0,
        y: 2.3,
        w: 5.2,
        h: 0.8,
        fontSize: 10,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
    });

    slide.addShape(pres.ShapeType.rect, {
        x: 1.0,
        y: 3.1,
        w: 5.2,
        h: 3.3,
        fill: { color: '090D16' },
        line: { color: COLORS.BLUE, width: 1 },
    });
    slide.addText([
        { text: 'Contoh Nama:\n', options: { color: COLORS.TEXT_MUTED, fontSize: 9 } },
        { text: '"Dr. H. Ahmad Fauzi, S.Kom., M.Si"\n', options: { color: COLORS.TEXT_WHITE, bold: true, fontSize: 10 } },
        { text: 'NIP: 198501012010011001\n\n', options: { color: COLORS.TEXT_MUTED, fontSize: 9 } },
        { text: 'Format Usulan Otomatis:\n', options: { color: COLORS.CYAN, fontSize: 9 } },
        { text: 'ahmad.198501012010011001@cirebonkota.go.id\n\n', options: { color: COLORS.GREEN, bold: true, fontSize: 10 } },
        { text: 'Regex Sinkron Frontend-Backend:\n', options: { color: COLORS.TEXT_MUTED, fontSize: 9 } },
        { text: '/^[a-z0-9]+(\\.[a-z0-9]+)*$/', options: { color: COLORS.AMBER, fontSize: 9.5 } },
    ], { x: 1.2, y: 3.2, w: 4.8, h: 3.1, fontFace: 'Courier New' });

    slide.addShape(pres.ShapeType.rect, {
        x: 6.8,
        y: 1.6,
        w: 5.7,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.AMBER, width: 1.5 },
    });
    slide.addText('⚡ Interaksi Verifikasi Satu Klik (Admin)', {
        x: 7.0,
        y: 1.8,
        w: 5.3,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText([
        { text: '• Tombol "Gunakan Usulan":\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Menyalin usulan email otomatis ke field input email definitif secara instan.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Fleksibilitas Edit Manual:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Superadmin tetap dapat mengubah username jika ada kebutuhan penyesuaian kedinasan.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Tombol "Kunci & Rekap":\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Eksekusi AJAX mengunci email_final_terverifikasi dan menandai flag is_email_recap_confirmed = true.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Efisiensi Waktu:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Memangkas waktu rekapitulasi dari 15 menit menjadi < 1 menit per pemohon.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 7.0, y: 2.3, w: 5.3, h: 4.3, fontSize: 10, fontFace: 'Arial', lineSpacing: 16 });

    slide.addNotes('Generator email dinas membersihkan gelar akademik secara otomatis dan menyusun format nama.nip@cirebonkota.go.id. Di panel admin, verifikator cukup klik tombol Gunakan Usulan dan Kunci & Rekap Email via AJAX.');
}

// ==========================================
// SLIDE 9: ZERO-TRUST VPN GATEWAY
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '08. KEAMANAN PERIMETER', 'Zero-Trust VPN Gateway & Stealth 404 Defense');

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.6,
        w: 5.6,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.ROSE, width: 1.5 },
    });
    slide.addText('🚪 Penghapusan Login Publik Terbuka', {
        x: 1.0,
        y: 1.8,
        w: 5.2,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText([
        { text: '• Rute /login Publik Dihapus:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Mengeliminasi target serangan credential stuffing, brute force bot, dan port scanning.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Pintu Masuk Khusus VPN:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Autentikasi dialihkan ke /gateway/login dan /admin/*.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Whitelist Subnet CIDR (config/vpn.php):\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  10.0.0.0/8 (VPN Diskominfo)\n  172.16.0.0/12 (Docker & Gateway)\n  192.168.0.0/16 (LAN Pemkot Cirebon)\n  127.0.0.1/32 (Localhost)', options: { color: COLORS.CYAN, fontFace: 'Courier New', fontSize: 9 } },
    ], { x: 1.0, y: 2.3, w: 5.2, h: 4.3, fontSize: 10, fontFace: 'Arial', lineSpacing: 16 });

    slide.addShape(pres.ShapeType.rect, {
        x: 6.8,
        y: 1.6,
        w: 5.7,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.CYAN, width: 1.5 },
    });
    slide.addText('🕵️ Mekanisme Stealth 404 Block', {
        x: 7.0,
        y: 1.8,
        w: 5.3,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText('Jika IP publik dari luar VPN mencoba membuka rute admin, server TIDAK memberikan status 403 Forbidden atau redirect login, melainkan:', {
        x: 7.0,
        y: 2.3,
        w: 5.3,
        h: 0.8,
        fontSize: 10,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
    });

    slide.addShape(pres.ShapeType.rect, {
        x: 7.2,
        y: 3.2,
        w: 4.9,
        h: 1.5,
        fill: { color: '090D16' },
        line: { color: COLORS.ROSE, width: 1.5 },
    });
    slide.addText('HTTP 404 NOT FOUND', {
        x: 7.2,
        y: 3.5,
        w: 4.9,
        h: 0.5,
        fontSize: 22,
        fontFace: 'Courier New',
        color: COLORS.ROSE,
        bold: true,
        align: 'center',
    });
    slide.addText('Halaman Tidak Ditemukan (Stealth Mode)', {
        x: 7.2,
        y: 4.0,
        w: 4.9,
        h: 0.4,
        fontSize: 10,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
        align: 'center',
    });

    slide.addText('Penyerang menganggap endpoint admin fiktif atau tidak ada di domain ini (Security through Obscurity + Zero-Trust Perimeter).', {
        x: 7.0,
        y: 5.0,
        w: 5.3,
        h: 1.5,
        fontSize: 10,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
        lineSpacing: 15,
    });

    slide.addNotes('Keamanan perimeter Zero-Trust mengisolasi login admin ke subnet VPN Diskominfo. Akses dari IP publik dijawab dengan HTTP 404 murni sehingga menyembunyikan eksistensi portal admin.');
}

// ==========================================
// SLIDE 10: ARSITEKTUR DEFENSE-IN-DEPTH
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '09. KEAMANAN BERLAPIS', 'Arsitektur Keamanan Berlapis (8 Layer Defense-in-Depth)');

    const layers = [
        { l: 'LAYER 0-1', t: 'Perimeter & Edge Proxy', d: 'VPN Gateway Subnet CIDR, TLS 1.3, HSTS 2 Tahun, Rate Limit 5r/m & 30r/s.', c: COLORS.CYAN, x: 0.8 },
        { l: 'LAYER 2-3', t: 'Aplikasi & Kriptografi', d: 'Sanitasi Input XSS, CSRF, AES-256-GCM NIK/NIP & HMAC Blind Index.', c: COLORS.GREEN, x: 3.8 },
        { l: 'LAYER 4-5', t: 'IAM & Otorisasi', d: 'RFC 6238 TOTP MFA, 8 Backup Codes, Step-Up Auth & Laravel Policies.', c: COLORS.AMBER, x: 6.8 },
        { l: 'LAYER 6-7', t: 'Audit & Kontainer', d: 'Redis Queue audit-logs, Partisi Bulanan PostgreSQL, Non-Root 1000:1000.', c: COLORS.BLUE, x: 9.8 },
    ];

    layers.forEach(ly => {
        slide.addShape(pres.ShapeType.rect, {
            x: ly.x,
            y: 1.6,
            w: 2.7,
            h: 3.6,
            fill: { color: COLORS.BG_CARD },
            line: { color: ly.c, width: 1.5 },
        });
        slide.addText(ly.l, {
            x: ly.x + 0.15,
            y: 1.8,
            w: 2.4,
            h: 0.3,
            fontSize: 9.5,
            fontFace: 'Courier New',
            color: ly.c,
            bold: true,
        });
        slide.addText(ly.t, {
            x: ly.x + 0.15,
            y: 2.15,
            w: 2.4,
            h: 0.5,
            fontSize: 12,
            fontFace: 'Arial',
            color: COLORS.TEXT_WHITE,
            bold: true,
        });
        slide.addText(ly.d, {
            x: ly.x + 0.15,
            y: 2.7,
            w: 2.4,
            h: 2.3,
            fontSize: 9.5,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            lineSpacing: 14,
        });
    });

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 5.4,
        w: 11.7,
        h: 1.4,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.BORDER, width: 1 },
    });
    slide.addText([
        { text: 'Kriptografi Data Sensitif: ', options: { bold: true, color: COLORS.CYAN } },
        { text: 'nik = AES-256-GCM(plaintext, IV, Tag)  |  nik_hash = HMAC-SHA256(plaintext, APP_KEY)\n', options: { fontFace: 'Courier New', fontSize: 9 } },
        { text: 'Proteksi Akun Superadmin: ', options: { bold: true, color: COLORS.AMBER } },
        { text: 'Lockout progresif (5x gagal), TOTP MFA Google Authenticator, dan konfirmasi kata sandi saat mematikan MFA.', options: { fontSize: 9.5 } },
    ], { x: 1.0, y: 5.55, w: 11.3, h: 1.1, fontSize: 10, fontFace: 'Arial', color: COLORS.TEXT_WHITE, lineSpacing: 15 });

    slide.addNotes('Sistem menerapkan 8 lapisan Defense-in-Depth yang saling melengkapi. Dari jaringan luar hingga database lokal, setiap komponen memiliki mekanisme pengamanan berlapis.');
}

// ==========================================
// SLIDE 11: DATABASE CURSOR STREAMING
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '10. EFISIENSI & SKALABILITAS', 'Database Cursor Streaming Mass Export Engine');

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.6,
        w: 5.6,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.ROSE, width: 1.5 },
    });
    slide.addText('❌ Ekspor Tradisional (Bahaya OOM)', {
        x: 1.0,
        y: 1.8,
        w: 5.2,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.ROSE,
        bold: true,
    });
    slide.addText([
        { text: '• Memuat Seluruh Data ke RAM:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Metode Eloquent PendaftaranTte::with(...)->get() memuat ratusan ribu objek ke memori PHP.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Konsumsi RAM Membengkak:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Membutuhkan 500 MB – 1 GB RAM, memicu Fatal Error: Allowed memory size exhausted.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Badai Kueri N+1:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Lazy loading relasi SKPD/Jabatan mengeksekusi ribuan query tambahan ke database.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 1.0, y: 2.3, w: 5.2, h: 4.3, fontSize: 10, fontFace: 'Arial', lineSpacing: 16 });

    slide.addShape(pres.ShapeType.rect, {
        x: 6.8,
        y: 1.6,
        w: 5.7,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.GREEN, width: 1.5 },
    });
    slide.addText('✅ Cursor Streaming Terpadu (Sistem V4.1)', {
        x: 7.0,
        y: 1.8,
        w: 5.3,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.GREEN,
        bold: true,
    });
    slide.addText([
        { text: '• Kueri Tunggal Join Terpadu:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  DB::table()->leftJoin()->cursor() berjalan dalam 1 kueri tunggal bebas N+1 query.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Konsumsi RAM Konstan < 20 MB:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Mengalirkan data baris demi baris via php://output langsung ke browser pemohon.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• Nginx FastCGI Unbuffered:\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  fastcgi_buffering off; pada /admin/export/ menjamin aliran data CSV tidak tertahan di buffer.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• UTF-8 BOM Header (\\xEF\\xBB\\xBF):\n', options: { bold: true, color: COLORS.TEXT_WHITE } },
        { text: '  Karakter aksen/khusus terbaca sempurna di Microsoft Excel.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 7.0, y: 2.3, w: 5.3, h: 4.3, fontSize: 10, fontFace: 'Arial', lineSpacing: 15 });

    slide.addNotes('Fitur Cursor Streaming menjamin server tidak akan crash saat mengekspor data laporan dalam jumlah jutaan baris, karena RAM PHP terkunci stabil di bawah 20MB.');
}

// ==========================================
// SLIDE 12: SKEMA BASIS DATA POSTGRESQL 16
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '11. STRUKTUR DATA & PARTISI', 'Struktur Basis Data PostgreSQL 16 (8 Skema Normalisasi)');

    const tbls = [
        { n: '1. skpd', d: 'Master 20 unit kerja Pemkot Cirebon.', c: COLORS.CYAN, x: 0.8, y: 1.6 },
        { n: '2. master_jabatan_golongan', d: 'Standarisasi jabatan & golongan ASN.', c: COLORS.CYAN, x: 3.8, y: 1.6 },
        { n: '3. users', d: 'IAM Superadmin + MFA & recovery codes.', c: COLORS.CYAN, x: 6.8, y: 1.6 },
        { n: '4. sessions', d: 'Penyimpanan sesi login database.', c: COLORS.CYAN, x: 9.8, y: 1.6 },
        { n: '5. pendaftaran_tte', d: 'Transaksi pendaftaran & AES NIK/NIP.', c: COLORS.GREEN, x: 0.8, y: 3.4 },
        { n: '6. sertifikat_tte', d: 'Sertifikat BSrE, jatuh tempo +2 th.', c: COLORS.GREEN, x: 3.8, y: 3.4 },
        { n: '7. dashboard_daily_stats', d: 'Fast aggregator harian menyuplai Redis.', c: COLORS.AMBER, x: 6.8, y: 3.4 },
        { n: '8. security_audit_logs', d: 'Partisi bulanan native + default partition.', c: COLORS.BLUE, x: 9.8, y: 3.4 },
    ];

    tbls.forEach(t => {
        slide.addShape(pres.ShapeType.rect, {
            x: t.x,
            y: t.y,
            w: 2.7,
            h: 1.6,
            fill: { color: COLORS.BG_CARD },
            line: { color: t.c, width: 1.5 },
        });
        slide.addText(t.n, {
            x: t.x + 0.15,
            y: t.y + 0.2,
            w: 2.4,
            h: 0.3,
            fontSize: 10.5,
            fontFace: 'Arial',
            color: COLORS.TEXT_WHITE,
            bold: true,
        });
        slide.addText(t.d, {
            x: t.x + 0.15,
            y: t.y + 0.55,
            w: 2.4,
            h: 0.9,
            fontSize: 9,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            lineSpacing: 13,
        });
    });

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 5.2,
        w: 11.7,
        h: 1.6,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.BORDER, width: 1 },
    });
    slide.addText([
        { text: 'Keunggulan Skema PostgreSQL 16:\n', options: { bold: true, color: COLORS.TEXT_WHITE, fontSize: 11 } },
        { text: '1. Partial Unique Index: ', options: { bold: true, color: COLORS.CYAN } },
        { text: 'Mengizinkan ASN yang DITOLAK mendaftar ulang secara mandiri tanpa tabrakan kunci unik.\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '2. Native Partitioning: ', options: { bold: true, color: COLORS.GREEN } },
        { text: 'Tabel security_audit_logs dipartisi bulanan dan dilengkapi partisi fallback security_audit_logs_default.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 1.0, y: 5.35, w: 11.3, h: 1.3, fontSize: 9.5, fontFace: 'Arial', lineSpacing: 15 });

    slide.addNotes('Skema database terdiri dari 8 tabel normalisasi dengan Partial Unique Indexing dan Native Monthly Partitioning pada tabel log audit.');
}

// ==========================================
// SLIDE 13: HASIL REMEDIASI AUDIT 4 PILAR
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '12. EVALUASI KUALITAS KODE', 'Hasil Evaluasi & Remediasi Audit Kode Program 4 Pilar (100% Tuntas)');

    const pilars = [
        {
            t: '🛡️ Pilar I: Keamanan Data Pribadi',
            d: '• Temuan 1 (Kritis): NIK/NIP tersimpan di localStorage browser.\n• Solusi: Dialihkan ke sessionStorage tanpa menyimpan NIK/NIP mentah, auto-clear saat form disubmit (Kepatuhan UU PDP No. 27/2022).',
            c: COLORS.CYAN,
            x: 0.8,
            y: 1.6,
        },
        {
            t: '🔑 Pilar II: IAM & Otorisasi',
            d: '• Temuan 2 & 3: Absensi step-up auth & gagal simpan backup codes.\n• Solusi: Wajib validasi current_password saat matikan MFA, enkripsi 8 backup codes di users, penegakan Laravel Policies.',
            c: COLORS.AMBER,
            x: 6.8,
            y: 1.6,
        },
        {
            t: '⚡ Pilar III: Kinerja Basis Data',
            d: '• Temuan 5 & 6: Potensi badai N+1 & absensi partisi fallback.\n• Solusi: Ekspor kueri single leftJoin cursor bebas N+1 & migrasi partisi default security_audit_logs_default.',
            c: COLORS.GREEN,
            x: 0.8,
            y: 4.3,
        },
        {
            t: '⚙️ Pilar IV: Infrastruktur & Kode',
            d: '• Temuan 7-11: Regex email tidak sinkron & password Docker kosong.\n• Solusi: Sinkronisasi regex, DRY exception handler $jsonResponder, dan penegakan strict variable ${DB_PASSWORD:?...}.',
            c: COLORS.BLUE,
            x: 6.8,
            y: 4.3,
        },
    ];

    pilars.forEach(p => {
        slide.addShape(pres.ShapeType.rect, {
            x: p.x,
            y: p.y,
            w: 5.7,
            h: 2.5,
            fill: { color: COLORS.BG_CARD },
            line: { color: p.c, width: 1.5 },
        });
        slide.addText(p.t, {
            x: p.x + 0.2,
            y: p.y + 0.2,
            w: 5.3,
            h: 0.35,
            fontSize: 12,
            fontFace: 'Arial',
            color: COLORS.TEXT_WHITE,
            bold: true,
        });
        slide.addText(p.d, {
            x: p.x + 0.2,
            y: p.y + 0.6,
            w: 5.3,
            h: 1.7,
            fontSize: 9.5,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            lineSpacing: 14,
        });
    });

    slide.addNotes('Hasil audit 4 pilar telah menuntaskan 11 temuan perbaikan: Keamanan PII browser, IAM Step-Up Auth, Database Cursor Streaming, dan Penegakan Environment Docker.');
}

// ==========================================
// SLIDE 14: PANDUAN OPERASIONAL & TROUBLESHOOTING
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '13. PEMELIHARAAN & DEPLOYMENT', 'Panduan Operasional & Mitigasi Error (E-01 s/d E-12)');

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.6,
        w: 5.6,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.CYAN, width: 1.5 },
    });
    slide.addText('🚀 Prosedur Deployment Standar', {
        x: 1.0,
        y: 1.8,
        w: 5.2,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addShape(pres.ShapeType.rect, {
        x: 1.0,
        y: 2.3,
        w: 5.2,
        h: 4.3,
        fill: { color: '090D16' },
        line: { color: COLORS.BLUE, width: 1 },
    });
    slide.addText([
        { text: '# 1. Build & Jalankan Kontainer Stack\n', options: { color: COLORS.TEXT_MUTED } },
        { text: 'docker compose up -d --build\n\n', options: { color: COLORS.CYAN } },
        { text: '# 2. Migrasi Database & Seeder Data\n', options: { color: COLORS.TEXT_MUTED } },
        { text: 'docker compose exec app php artisan migrate --seed --force\n\n', options: { color: COLORS.GREEN } },
        { text: '# 3. Hardening Cache Produksi\n', options: { color: COLORS.TEXT_MUTED } },
        { text: 'docker compose exec app php artisan config:cache\n', options: { color: COLORS.TEXT_WHITE } },
        { text: 'docker compose exec app php artisan route:cache\n\n', options: { color: COLORS.TEXT_WHITE } },
        { text: '# 4. Crontab Host Server (Scheduler 00:01 WIB)\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '* * * * * cd /sismon-tte && docker compose exec -T app php artisan schedule:run >> /dev/null 2>&1', options: { color: COLORS.AMBER } },
    ], { x: 1.15, y: 2.4, w: 4.9, h: 4.1, fontFace: 'Courier New', fontSize: 8.5 });

    slide.addShape(pres.ShapeType.rect, {
        x: 6.8,
        y: 1.6,
        w: 5.7,
        h: 5.2,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.AMBER, width: 1.5 },
    });
    slide.addText('🔧 Mitigasi Error Operasional Cepat', {
        x: 7.0,
        y: 1.8,
        w: 5.3,
        h: 0.4,
        fontSize: 14,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });
    slide.addText([
        { text: '• E-02 (HTTP 404 pada /admin):\n', options: { bold: true, color: COLORS.ROSE } },
        { text: '  IP klien di luar subnet VPN. Hubungkan ke VPN Diskominfo atau perbarui VPN_ALLOWED_SUBNETS di .env.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• E-06 (Redis NOAUTH Exception):\n', options: { bold: true, color: COLORS.ROSE } },
        { text: '  Pastikan REDIS_PASSWORD di .env identik dengan flag --requirepass di docker-compose.yml.\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• E-09 (MFA Lockout):\n', options: { bold: true, color: COLORS.ROSE } },
        { text: '  Reset status MFA via Tinker terminal:\n  App\\Models\\User::first()->update([\'is_mfa_enabled\'=>false]);\n\n', options: { color: COLORS.TEXT_MUTED } },
        { text: '• E-11 (Karakter Rusak di Excel):\n', options: { bold: true, color: COLORS.ROSE } },
        { text: '  Telah disematkan UTF-8 BOM (\\xEF\\xBB\\xBF) pada streaming export.', options: { color: COLORS.TEXT_MUTED } },
    ], { x: 7.0, y: 2.3, w: 5.3, h: 4.3, fontSize: 9.5, fontFace: 'Arial', lineSpacing: 14 });

    slide.addNotes('Panduan operasional merinci perintah deployment standar dan mitigasi cepat jika terjadi error operasional E-01 sampai E-12.');
}

// ==========================================
// SLIDE 15: PEMETAAN RUTE & KREDENSIAL
// ==========================================
{
    const slide = pres.addSlide();
    applySlideBase(slide, '14. PEMETAAN AKSES RESMI', 'Pemetaan Rute Sistem & Kredensial Akun Default');

    // Tabel Rute
    const ruteRows = [
        [
            { text: 'Rute URL', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
            { text: 'Akses', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
            { text: 'Fungsi Utama', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
        ],
        [{ text: '/' }, { text: 'Publik' }, { text: 'Form Pendaftaran Mandiri ASN' }],
        [{ text: '/gateway/login' }, { text: 'VPN Only' }, { text: 'Login Kredensial Superadmin' }],
        [{ text: '/admin/dashboard' }, { text: 'VPN + Auth' }, { text: 'Dashboard Ringkasan & Antrian' }],
        [{ text: '/admin/verifikasi' }, { text: 'VPN + Auth' }, { text: 'Antrean Verifikasi & Rekap Email' }],
        [{ text: '/admin/penerbitan' }, { text: 'VPN + Auth' }, { text: 'Penerbitan Sertifikat BSrE' }],
        [{ text: '/admin/export/csv' }, { text: 'VPN + Auth' }, { text: 'Database Cursor Streaming Export' }],
    ];

    slide.addTable(ruteRows, {
        x: 0.8,
        y: 1.6,
        w: 5.6,
        colW: [2.0, 1.2, 2.4],
        fontSize: 8.5,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
        border: { color: COLORS.BORDER, width: 0.5 },
        fill: { color: COLORS.BG_CARD },
    });

    // Tabel Akun
    const userRows = [
        [
            { text: 'Nama Akun', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
            { text: 'Email Login', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
            { text: 'Peran', options: { bold: true, fill: { color: '334155' }, color: COLORS.TEXT_WHITE } },
        ],
        [{ text: 'Superadmin Utama' }, { text: 'admin@cirebonkota.go.id' }, { text: 'superadmin' }],
        [{ text: 'Akun Penguji' }, { text: 'ajriajundan@gmail.com' }, { text: 'superadmin' }],
        [{ text: 'Operator Sandi' }, { text: 'operator@cirebonkota.go.id' }, { text: 'superadmin' }],
    ];

    slide.addTable(userRows, {
        x: 6.8,
        y: 1.6,
        w: 5.7,
        colW: [2.0, 2.5, 1.2],
        fontSize: 8.5,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
        border: { color: COLORS.BORDER, width: 0.5 },
        fill: { color: COLORS.BG_CARD },
    });

    slide.addShape(pres.ShapeType.rect, {
        x: 6.8,
        y: 4.8,
        w: 5.7,
        h: 2.0,
        fill: { color: COLORS.BG_CARD },
        line: { color: COLORS.AMBER, width: 1 },
    });
    slide.addText('🔒 Keamanan Kredensial Produksi:', {
        x: 7.0,
        y: 5.0,
        w: 5.3,
        h: 0.3,
        fontSize: 10,
        fontFace: 'Arial',
        color: COLORS.AMBER,
        bold: true,
    });
    slide.addText('• Seluruh password akun default dicatat aman pada dokumentasi internal.\n• Superadmin wajib mengganti password saat sistem pertama kali dideploy di server produksi.\n• Seluruh akun Superadmin diwajibkan mengaktifkan TOTP MFA (Google Authenticator).', {
        x: 7.0,
        y: 5.35,
        w: 5.3,
        h: 1.3,
        fontSize: 9,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
        lineSpacing: 14,
    });

    slide.addNotes('Slide ini memetakan rute resmi dan akun seeder default untuk keperluan operasional.');
}

// ==========================================
// SLIDE 16: KESIMPULAN & PENUTUP
// ==========================================
{
    const slide = pres.addSlide();
    slide.background = { color: COLORS.BG_DARK };

    slide.addShape(pres.ShapeType.rect, {
        x: 0.8,
        y: 1.2,
        w: 3.5,
        h: 0.35,
        fill: { color: '1E293B' },
        line: { color: COLORS.GREEN, width: 1 },
    });
    slide.addText('STATUS: PRODUCTION READY', {
        x: 0.8,
        y: 1.2,
        w: 3.5,
        h: 0.35,
        fontSize: 9,
        fontFace: 'Courier New',
        color: COLORS.GREEN,
        bold: true,
        align: 'center',
    });

    slide.addText('SISTEM SIAP BEROPERASI SECARA\nSTABIL, EFISIEN & AMAN', {
        x: 0.8,
        y: 1.8,
        w: 11.5,
        h: 1.2,
        fontSize: 28,
        fontFace: 'Arial',
        color: COLORS.TEXT_WHITE,
        bold: true,
    });

    slide.addText('Sistem Monitoring Penerbitan TTE & Pelayanan Email Dinas Resmi V4.1 Pemerintah Kota Cirebon telah memenuhi seluruh standar SPBE, regulasi UU PDP No. 27/2022, standar persandian BSSN, dan hasil audit kode program 4 pilar.', {
        x: 0.8,
        y: 3.1,
        w: 11.5,
        h: 0.8,
        fontSize: 12,
        fontFace: 'Arial',
        color: COLORS.TEXT_MUTED,
        lineSpacing: 18,
    });

    const pillars = [
        { t: '🛡️ Berdaulat & Terenkripsi', d: 'Penyimpanan lokal di PostgreSQL 16 dengan enkripsi AES-256-GCM pada data NIK/NIP ASN.', c: COLORS.CYAN, x: 0.8 },
        { t: '⚡ Resiliensi Low-Bandwidth', d: '100% Zero CDN & form pendaftaran < 5 KB untuk kemudahan akses di kantor pelosok.', c: COLORS.GREEN, x: 4.8 },
        { t: '🔒 Zero-Trust VPN Gateway', d: 'Panel administrasi terisolasi penuh dari ancaman bot internet publik dengan Stealth 404.', c: COLORS.AMBER, x: 8.8 },
    ];

    pillars.forEach(p => {
        slide.addShape(pres.ShapeType.rect, {
            x: p.x,
            y: 4.1,
            w: 3.7,
            h: 1.8,
            fill: { color: COLORS.BG_CARD },
            line: { color: p.c, width: 1 },
        });
        slide.addText(p.t, {
            x: p.x + 0.2,
            y: 4.3,
            w: 3.3,
            h: 0.3,
            fontSize: 11,
            fontFace: 'Arial',
            color: COLORS.TEXT_WHITE,
            bold: true,
        });
        slide.addText(p.d, {
            x: p.x + 0.2,
            y: 4.65,
            w: 3.3,
            h: 1.1,
            fontSize: 9,
            fontFace: 'Arial',
            color: COLORS.TEXT_MUTED,
            lineSpacing: 13,
        });
    });

    slide.addText('HAK CIPTA © 2026 DINAS KOMUNIKASI DAN INFORMATIKA / BIDANG PERSANDIAN KOTA CIREBON', {
        x: 0.8,
        y: 6.6,
        w: 11.7,
        h: 0.4,
        fontSize: 9.5,
        fontFace: 'Courier New',
        color: COLORS.TEXT_MUTED,
        align: 'center',
    });

    slide.addNotes('Sebagai penutup, sistem telah 100% siap digunakan untuk melayani ASN Kota Cirebon. Terima kasih atas perhatian bapak/ibu sekalian.');
}

// 3. Simpan File PPTX
const outputPath = 'presentasi_sistem_tte.pptx';
pres.writeFile({ fileName: outputPath })
    .then(fileName => {
        console.log(`✅ File presentasi PowerPoint berhasil digenerate: ${fileName}`);
    })
    .catch(err => {
        console.error('❌ Terjadi kesalahan saat generate PPTX:', err);
    });
