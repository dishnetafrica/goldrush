<?php

namespace App\Policies;

use App\Investor\Models\InvestorDocument;
use App\Models\User;

/**
 * Who may read an investor's financial documents.
 *
 * Deny by default: an investor sees their own and nothing else. This is the
 * whole defence against reading someone else's statement by changing a number
 * in a URL, so it is deliberately the only rule and it has no exceptions.
 *
 * Admin access runs through the admin guard on separate routes, not through
 * this policy.
 */
class InvestorDocumentPolicy
{
    public function view(User $user, InvestorDocument $document): bool
    {
        return $document->user_id === $user->id;
    }

    public function download(User $user, InvestorDocument $document): bool
    {
        return $this->view($user, $document);
    }
}
