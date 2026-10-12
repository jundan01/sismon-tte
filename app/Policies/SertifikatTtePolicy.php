<?php

namespace App\Policies;

use App\Domain\Sertifikat\Models\SertifikatTte;
use App\Models\User;

/**
 * Policy Otorisasi Eksplisit Superadmin pada Modul Sertifikat TTE.
 * Ref: SECURITY.md §6.1, ARCHITECTURE.md §6.3
 */
class SertifikatTtePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function view(User $user, SertifikatTte $sertifikat): bool
    {
        return $user->isSuperadmin();
    }

    public function terbitkanSertifikat(User $user): bool
    {
        return $user->isSuperadmin();
    }

    public function updateStatus(User $user, SertifikatTte $sertifikat): bool
    {
        return $user->isSuperadmin();
    }
}
