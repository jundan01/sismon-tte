<?php

namespace App\Services;

use App\Domain\Pendaftaran\Models\PendaftaranTte;
use App\Domain\Sertifikat\Models\SertifikatTte;
use Illuminate\Support\Str;

/**
 * Service Pembuat Usulan Email Dinas Terstandarisasi (@cirebonkota.go.id)
 * dan Sanitasi Nama Lengkap ASN Sesuai KTP (Tanpa Gelar Akademik/Keagamaan, Tanpa Titik/Koma).
 * Ref: ARCHITECTURE.md §6.1, PRD.md §7.1
 */
class EmailGeneratorService
{
    public const DEFAULT_DOMAIN = 'cirebonkota.go.id';

    /**
     * Pola Gelar Depan yang Dieliminasi (Akademik & Keagamaan: Haji, Hajah, Ustadz, Kiai Haji, KH, dll.).
     */
    protected array $titlesToEliminate = [
        // Akademik Depan
        'profesor', 'prof\.', 'prof', 'dr\.', 'dr', 'drg\.', 'drg', 'drs\.', 'drs', 'dra\.', 'dra',
        'ir\.', 'ir',
        // Keagamaan Depan
        'kiai\s+haji', 'kiai', 'k\.h\.', 'kh\.', 'kh', 'ustadz', 'ust\.', 'ust', 'haji', 'hajah',
        'h\.', 'h', 'hj\.', 'hj', 'habib', 'pdt\.', 'pdt', 'pastur', 'romo'
    ];

    /**
     * Pola Gelar Belakang Akademik, Profesi, Spesialis, & Vokasi yang Dieliminasi.
     */
    protected array $academicSuffixTitles = [
        // Sarjana & Diploma
        's\.kom', 's\.pd', 's\.pd\.i', 's\.pd\.sd', 's\.st', 's\.tr', 's\.tr\.kom', 's\.tr\.p', 's\.tr\.t',
        's\.tr\.sos', 's\.tr\.keb', 's\.tr\.gz', 's\.sos', 's\.sos\.i', 's\.e', 's\.e\.i', 's\.h', 's\.h\.i',
        's\.ked', 's\.farm', 's\.si', 's\.t', 's\.ip', 's\.i\.p', 's\.ap', 's\.a\.p', 's\.ab', 's\.a\.b',
        's\.kep', 's\.gz', 's\.psi', 's\.stat', 's\.mat', 's\.fil', 's\.sn', 's\.ds', 's\.i\.kom', 's\.ak',
        's\.p', 's\.pt', 's\.pi', 's\.hut', 's\.kel', 's\.hum', 's\.th', 's\.ag', 's\.sy', 's\.kedw',
        's\.kedg', 's\.k\.m', 'skm', 's\.kom\.i', 's\.t\.p', 's\.p\.w\.k', 's\.ars', 's\.bio', 's\.fis',
        's\.keb', 's\.or', 'a\.md', 'a\.md\.kom', 'a\.md\.keb', 'a\.md\.kep', 'a\.md\.farm', 'a\.md\.rad',
        'a\.md\.rmik', 'a\.md\.ro', 'a\.md\.ak', 'a\.md\.a\.k', 'a\.ma', 'a\.p', 'd\.iii', 'd\.iv',
        'lc\.', 'lc', 'll\.?m', 'llm', 'bba', 'b\.a', 'b\.sc', 'b\.eng',
        // Magister
        'm\.kom', 'm\.pd', 'm\.pd\.i', 'm\.si', 'm\.m', 'm\.e', 'm\.e\.sy', 'm\.h', 'm\.ti', 'm\.t',
        'm\.ap', 'm\.a\.p', 'm\.ab', 'm\.a\.b', 'm\.kn', 'm\.kes', 'm\.farm', 'm\.psi', 'm\.stat',
        'm\.ak', 'm\.p', 'm\.sc', 'm\.eng', 'm\.ed', 'm\.phil', 'm\.sn', 'm\.ds', 'm\.ag', 'm\.sos',
        'm\.i\.kom', 'm\.tr', 'm\.m\.s\.i', 'm\.k\.m', 'mkm', 'm\.a', 'm\.hum', 'mba',
        // Doktoral / Luar Negeri
        'ph\.d', 'phd', 'd\.sc', 'dba', 'ed\.d',
        // Profesi & Spesialis
        'sp\.a', 'sp\.b', 'sp\.pd', 'sp\.og', 'sp\.m', 'sp\.tht', 'sp\.jp', 'sp\.s', 'sp\.an', 'sp\.rad',
        'sp\.pk', 'sp\.pa', 'sp\.kj', 'sp\.kk', 'sp\.ot', 'sp\.dv', 'sp\.n', 'sp\.f', 'sp\.gk',
        'sp\.kfr', 'sp\.and', 'sp\.ak', 'sp\.b\.kbd', 'ners', 'ns\.', 'ns', 'apt\.', 'apt', 'gr\.', 'gr',
        'psikolog', 'adv\.', 'cpa', 'ca', 'qia', 'cia', 'cisa', 'crma', 'cfr'
    ];

    /**
     * Sanitasi Nama Lengkap Sesuai KTP:
     * - Mengeliminasi gelar akademik (Prof, Dr, Ir, S.Kom, M.Si, Ph.D, dll.)
     * - Mengeliminasi gelar keagamaan (Haji, Hajah, Ustadz, Kiai Haji, Kiai, KH, H, Hj, dll.)
     * - Menghilangkan seluruh tanda baca titik (.) dan koma (,)
     * - Format murni nama lengkap Title Case sesuai standar KTP
     */
    public function cleanFullName(string $nama): string
    {
        $cleaned = trim($nama);
        if ($cleaned === '') {
            return '';
        }

        // 1. Jika terdapat koma, pisahkan nama utama sebelum koma (standar pemisah gelar belakang)
        if (str_contains($cleaned, ',')) {
            $parts = explode(',', $cleaned);
            $candidate = trim($parts[0]);
            if (!empty($candidate)) {
                $cleaned = $candidate;
            }
        }

        // 2. Bersihkan gelar depan (akademik & keagamaan) secara berulang
        $prefixPattern = '/^\s*(?:' . implode('|', $this->titlesToEliminate) . ')[\s\.\,]+/i';
        $maxIterations = 5;
        while ($maxIterations > 0 && preg_match($prefixPattern, $cleaned)) {
            $cleaned = preg_replace($prefixPattern, '', $cleaned);
            $maxIterations--;
        }

        // 3. Bersihkan gelar belakang jika ada yang ditulis tanpa koma
        $suffixPattern = '/(?:[\s\.\,]+(?:' . implode('|', $this->academicSuffixTitles) . ')\.?)+$/i';
        $maxIterations = 5;
        while ($maxIterations > 0 && preg_match($suffixPattern, $cleaned)) {
            $cleaned = preg_replace($suffixPattern, '', $cleaned);
            $maxIterations--;
        }

        // 4. Bersihkan token gelar di awalan atau akhiran kata
        foreach ($this->titlesToEliminate as $titlePattern) {
            $cleaned = preg_replace('/^\s*\b' . $titlePattern . '\b\.?\s*/i', '', $cleaned);
            $cleaned = preg_replace('/\b' . $titlePattern . '\b\.?\s*$/i', '', $cleaned);
        }
        foreach ($this->academicSuffixTitles as $titlePattern) {
            $cleaned = preg_replace('/\b' . $titlePattern . '\b\.?\s*$/i', '', $cleaned);
        }

        // 5. Hapus seluruh tanda baca titik (.) dan koma (,) mutlak sesuai permintaan
        $cleaned = str_replace(['.', ','], ' ', $cleaned);

        // 6. Bersihkan karakter non-huruf selain spasi, dash (-), dan apostrof (')
        $cleaned = preg_replace('/[^a-zA-Z\s\-\'\’]/u', ' ', $cleaned);

        // 7. Rapikan spasi berulang
        $cleaned = preg_replace('/\s+/', ' ', trim($cleaned));

        // Jika setelah sanitasi string menjadi kosong (misal input cuma gelar saja), fallback ke input awal dibersihkan
        if ($cleaned === '') {
            $cleaned = preg_replace('/[^a-zA-Z\s]/', '', $nama);
            $cleaned = trim(preg_replace('/\s+/', ' ', $cleaned));
        }

        // 8. Ubah menjadi Title Case murni
        return mb_convert_case($cleaned, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Ekstrak kata-kata nama untuk username email (huruf kecil).
     */
    public function extractNameWords(string $namaLengkap): array
    {
        $cleanName = $this->cleanFullName($namaLengkap);
        $words = preg_split('/[^a-zA-Z0-9]+/u', strtolower($cleanName), -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_filter($words, fn($w) => strlen($w) > 0));
    }

    /**
     * Sanitasi NIP: hanya ambil digit angka.
     */
    public function sanitizeNip(string $nip): string
    {
        return preg_replace('/[^0-9]/', '', $nip);
    }

    /**
     * Generate usulan email resmi berbasis aturan baru:
     * - Format utama: nama_3digitAwalNIP@cirebonkota.go.id
     * - Penanganan duplikasi: nama_3digitAkhirNIP@cirebonkota.go.id
     * - Fallback: kombinasi variasi nama & digit NIP
     */
    public function generate(string $namaLengkap, string $nip, ?int $excludePendaftaranId = null): string
    {
        $words = $this->extractNameWords($namaLengkap);
        $sanitizedNip = $this->sanitizeNip($nip);

        if (empty($words)) {
            $words = ['asn'];
        }

        $nipDigits = !empty($sanitizedNip) ? $sanitizedNip : '000000000000000000';
        $nip3Awal = substr($nipDigits, 0, 3);
        $nip3Akhir = substr($nipDigits, -3);

        if (strlen($nip3Awal) < 3) {
            $nip3Awal = str_pad($nip3Awal, 3, '0', STR_PAD_RIGHT);
        }
        if (strlen($nip3Akhir) < 3) {
            $nip3Akhir = str_pad($nip3Akhir, 3, '0', STR_PAD_LEFT);
        }

        // 1. Pola Usulan Standar: nama_3digitAwalNIP
        if (count($words) >= 2) {
            $standardProposal = "{$words[0]}_{$words[1]}_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
        } else {
            $standardProposal = "{$words[0]}_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
        }

        // Cek keunikan di basis data
        if (!$this->isEmailTaken($standardProposal, $excludePendaftaranId)) {
            return $standardProposal;
        }

        // 2. Penanganan Duplikasi: nama_3digitAkhirNIP
        if (count($words) >= 2) {
            $fallback1Proposal = "{$words[0]}_{$words[1]}_{$nip3Akhir}@" . self::DEFAULT_DOMAIN;
        } else {
            $fallback1Proposal = "{$words[0]}_{$nip3Akhir}@" . self::DEFAULT_DOMAIN;
        }

        if (!$this->isEmailTaken($fallback1Proposal, $excludePendaftaranId)) {
            return $fallback1Proposal;
        }

        // 3. Penanganan Duplikasi Tahap 2: variasi kombinasi elemen nama & angka NIP
        $candidates = [];

        if (count($words) >= 3) {
            $candidates[] = "{$words[0]}_{$words[2]}_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
            $candidates[] = "{$words[0]}_" . end($words) . "_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
            $candidates[] = "{$words[1]}_{$words[0]}_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
            $candidates[] = substr($words[0], 0, 1) . "_{$words[1]}_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
            $candidates[] = "{$words[0]}_" . substr($words[1], 0, 1) . "_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
        } elseif (count($words) === 2) {
            $candidates[] = "{$words[1]}_{$words[0]}_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
            $candidates[] = substr($words[0], 0, 1) . "_{$words[1]}_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
            $candidates[] = "{$words[0]}_" . substr($words[1], 0, 1) . "_{$nip3Awal}@" . self::DEFAULT_DOMAIN;
        } else {
            $candidates[] = "{$words[0]}_" . strrev($nip3Awal) . "@" . self::DEFAULT_DOMAIN;
        }

        foreach ($candidates as $cand) {
            if (!$this->isEmailTaken($cand, $excludePendaftaranId)) {
                return $cand;
            }
        }

        // Fallback acak terkontrol (suffiks acak 2 karakter)
        for ($i = 0; $i < 30; $i++) {
            $w1 = $words[0];
            $w2 = $words[1] ?? '';
            $suffix = strtolower(Str::random(2));

            if ($w2 !== '') {
                $randProposal = "{$w1}_{$w2}_{$nip3Awal}_{$suffix}@" . self::DEFAULT_DOMAIN;
            } else {
                $randProposal = "{$w1}_{$nip3Awal}_{$suffix}@" . self::DEFAULT_DOMAIN;
            }

            if (!$this->isEmailTaken($randProposal, $excludePendaftaranId)) {
                return $randProposal;
            }
        }

        // Fallback darurat jika seluruh permutasi terpakai
        return "{$words[0]}_{$nip3Awal}_" . random_int(10, 99) . "@" . self::DEFAULT_DOMAIN;
    }

    /**
     * Cek apakah email sudah terdaftar / collision di pendaftaran_tte atau sertifikat_tte.
     */
    public function isEmailTaken(string $email, ?int $excludePendaftaranId = null): bool
    {
        $normalized = strtolower(trim($email));

        // 1. Cek di tabel pendaftaran_tte
        $inPendaftaran = PendaftaranTte::where(function ($query) use ($normalized) {
                $query->whereRaw('LOWER(email_usulan) = ?', [$normalized])
                    ->orWhereRaw('LOWER(email_final_terverifikasi) = ?', [$normalized])
                    ->orWhereRaw('LOWER(email_existing) = ?', [$normalized]);
            })
            ->where('status', '!=', 'DITOLAK')
            ->when($excludePendaftaranId, fn($q) => $q->where('id', '!=', $excludePendaftaranId))
            ->exists();

        if ($inPendaftaran) {
            return true;
        }

        // 2. Cek di tabel sertifikat_tte
        $inSertifikat = SertifikatTte::whereRaw('LOWER(email_dinas) = ?', [$normalized])
            ->when($excludePendaftaranId, fn($q) => $q->where('pendaftaran_id', '!=', $excludePendaftaranId))
            ->exists();

        return $inSertifikat;
    }

    /**
     * Pemeriksaan Ringkas Tabrakan Email (Quick Collision Check) untuk Panel Superadmin.
     */
    public function checkCollisionDetail(string $email, ?int $excludePendaftaranId = null): array
    {
        $normalized = strtolower(trim($email));

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            return [
                'is_available' => false,
                'reason'       => 'invalid_format',
                'message'      => 'Format alamat email tidak valid.',
                'owner'        => null,
            ];
        }

        if (!$this->isOfficialDomain($normalized)) {
            return [
                'is_available' => false,
                'reason'       => 'invalid_domain',
                'message'      => 'Alamat email wajib menggunakan domain resmi @' . self::DEFAULT_DOMAIN,
                'owner'        => null,
            ];
        }

        // 1. Cek di tabel pendaftaran_tte
        $pendaftaran = PendaftaranTte::with('skpd')
            ->where(function ($query) use ($normalized) {
                $query->whereRaw('LOWER(email_final_terverifikasi) = ?', [$normalized])
                    ->orWhereRaw('LOWER(email_usulan) = ?', [$normalized])
                    ->orWhereRaw('LOWER(email_existing) = ?', [$normalized]);
            })
            ->where('status', '!=', 'DITOLAK')
            ->when($excludePendaftaranId, fn($q) => $q->where('id', '!=', $excludePendaftaranId))
            ->first();

        if ($pendaftaran) {
            return [
                'is_available' => false,
                'reason'       => 'pendaftaran_tte',
                'message'      => "Email ini sudah terdaftar atas nama ASN {$pendaftaran->nama_lengkap} (SKPD: " . ($pendaftaran->skpd->nama_skpd ?? '-') . ") dengan No. Ref: {$pendaftaran->nomor_referensi} [Status: {$pendaftaran->status}].",
                'owner'        => [
                    'nama'            => $pendaftaran->nama_lengkap,
                    'skpd'            => $pendaftaran->skpd->nama_skpd ?? '-',
                    'nomor_referensi' => $pendaftaran->nomor_referensi,
                    'status'          => $pendaftaran->status,
                ],
            ];
        }

        // 2. Cek di tabel sertifikat_tte
        $sertifikat = SertifikatTte::with('pendaftaran.skpd')
            ->whereRaw('LOWER(email_dinas) = ?', [$normalized])
            ->when($excludePendaftaranId, fn($q) => $q->where('pendaftaran_id', '!=', $excludePendaftaranId))
            ->first();

        if ($sertifikat) {
            $pemilik = $sertifikat->pendaftaran->nama_lengkap ?? 'ASN';
            $skpd = $sertifikat->pendaftaran->skpd->nama_skpd ?? '-';

            return [
                'is_available' => false,
                'reason'       => 'sertifikat_tte',
                'message'      => "Email ini telah diterbitkan resmi pada Sertifikat TTE aktif atas nama {$pemilik} (SKPD: {$skpd}).",
                'owner'        => [
                    'nama'              => $pemilik,
                    'skpd'              => $skpd,
                    'status_sertifikat' => $sertifikat->status_sertifikat,
                ],
            ];
        }

        return [
            'is_available' => true,
            'reason'       => null,
            'message'      => "Alamat email {$normalized} tersedia dan belum digunakan oleh pendaftar lain di basis data.",
            'owner'        => null,
        ];
    }

    /**
     * Validasi apakah email menggunakan domain resmi @cirebonkota.go.id.
     */
    public function isOfficialDomain(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);
        return strtolower($domain) === self::DEFAULT_DOMAIN;
    }
}
