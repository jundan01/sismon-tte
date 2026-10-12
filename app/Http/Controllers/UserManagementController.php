<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Security\TotpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller Manajemen Akun Superadmin IAM & Aktivasi TOTP MFA.
 * Ref: PRD.md FR-20 & FR-21, SECURITY.md §6.1 & §6.2
 */
class UserManagementController extends Controller
{
    public function __construct(
        protected TotpService $totpService
    ) {}

    /**
     * Tampilkan daftar akun Superadmin internal.
     */
    public function index(Request $request): JsonResponse|\Illuminate\View\View|Response
    {
        $users = User::select(['id', 'uuid', 'name', 'email', 'role', 'is_mfa_enabled', 'last_login_at', 'created_at'])
            ->orderBy('name', 'asc')
            ->paginate(15);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'data'    => $users,
            ]);
        }

        return view('admin.master.users.index', [
            'users' => $users,
        ]);
    }

    /**
     * Tambah akun Superadmin baru.
     */
    public function store(Request $request): JsonResponse|Response
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                Password::min(8)->letters()->numbers()->symbols(),
            ],
        ]);

        $user = User::create([
            'name'           => trim($validated['name']),
            'email'          => strtolower(trim($validated['email'])),
            'password'       => $validated['password'], // Hash otomatis oleh model
            'role'           => 'superadmin',
            'is_mfa_enabled' => false,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Akun Superadmin berhasil dibuat.',
                'data'    => $user->only(['id', 'uuid', 'name', 'email', 'role', 'is_mfa_enabled']),
            ], 201);
        }

        return redirect()->route('admin.master.users.index')->with('success', 'Akun Superadmin berhasil dibuat.');
    }

    /**
     * Generate Secret Key & URI QR Code untuk Aktivasi TOTP MFA (Google Authenticator).
     */
    public function setupMfa(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $secret = $this->totpService->generateSecret(16);
        $otpUrl = $this->totpService->getOtpAuthUrl($user->email, $secret);
        $recoveryCodes = $this->totpService->generateRecoveryCodes(8);

        // Simpan sementara secret di session sebelum dikonfirmasi oleh kode OTP pertama
        $request->session()->put('mfa_setup_secret', $secret);
        $request->session()->put('mfa_recovery_codes', $recoveryCodes);

        return response()->json([
            'success'        => true,
            'secret'         => $secret,
            'qr_uri'         => $otpUrl,
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Konfirmasi Aktivasi TOTP MFA dengan verifikasi 6 digit token pertama.
     */
    public function confirmMfa(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $secret = $request->session()->get('mfa_setup_secret');
        if (!$secret) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi setup MFA telah kedaluwarsa. Harap muat ulang QR code setup.',
            ], 422);
        }

        $isValid = $this->totpService->verifyCode($secret, $request->input('code'));
        if (!$isValid) {
            return response()->json([
                'success' => false,
                'message' => 'Kode OTP 6-digit yang Anda masukkan salah atau telah kedaluwarsa.',
            ], 422);
        }

        /** @var User $user */
        $user = auth()->user();
        $recoveryCodes = $request->session()->get('mfa_recovery_codes', []);

        $user->update([
            'mfa_secret'         => $secret, // Di-encrypt otomatis oleh model cast
            'mfa_recovery_codes' => json_encode($recoveryCodes), // Di-encrypt otomatis oleh model cast
            'is_mfa_enabled'     => true,
        ]);

        $request->session()->forget(['mfa_setup_secret', 'mfa_recovery_codes']);

        return response()->json([
            'success' => true,
            'message' => 'Multi-Factor Authentication (TOTP) berhasil diaktifkan.',
        ]);
    }

    /**
     * Nonaktifkan MFA untuk akun Superadmin dengan verifikasi password & otorisasi.
     */
    public function disableMfa(Request $request, string|int $id): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
        ], [
            'current_password.required'         => 'Konfirmasi kata sandi Anda saat ini wajib diisi.',
            'current_password.current_password' => 'Kata sandi saat ini yang Anda masukkan tidak valid.',
        ]);

        $user = \Illuminate\Support\Str::isUuid($id)
            ? User::where('uuid', $id)->firstOrFail()
            : User::findOrFail(is_numeric($id) ? (int)$id : 0);

        // Otorisasi: Hanya user bersangkutan atau superadmin utama
        if (auth()->id() !== $user->id && !auth()->user()->isSuperadmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki wewenang untuk menonaktifkan MFA akun ini.');
        }

        $user->update([
            'mfa_secret'         => null,
            'mfa_recovery_codes' => null,
            'is_mfa_enabled'     => false,
        ]);

        \App\Jobs\ProcessAuditLogJob::dispatch([
            'user_id'     => auth()->id(),
            'action_type' => 'DISABLE_MFA_USER',
            'entity_name' => 'users',
            'entity_id'   => $user->id,
            'ip_address'  => $request->ip() ?? '127.0.0.1',
            'user_agent'  => $request->userAgent(),
            'payload_before' => [
                'is_mfa_enabled' => true,
            ],
            'payload_after'  => [
                'is_mfa_enabled' => false,
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MFA berhasil dinonaktifkan untuk akun ' . $user->email . '.',
        ]);
    }
}
