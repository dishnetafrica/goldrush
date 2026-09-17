<?php

namespace App\Accounting\Security;

use App\Accounting\Exceptions\PostingRefused;
use App\Models\Admin\Admin;

/**
 * Who may do what in the general ledger.
 *
 * The tokens are admin route names because that is what this application
 * already stores permissions as: admin_role_has_permissions.route. Using the
 * same vocabulary means that when the admin screens arrive they are protected
 * by the same grants the services check here, rather than by a second,
 * divergent permission system.
 *
 * Super Admin passes everything, matching the behaviour of the existing route
 * guard. A console command with no actor is trusted, because reaching a shell
 * on the server is already a greater privilege than any of these grants.
 */
final class AccountingPermission
{
    public const JOURNAL_VIEW    = 'admin.accounting.journal.view';
    public const JOURNAL_POST    = 'admin.accounting.journal.post';
    public const JOURNAL_REVERSE = 'admin.accounting.journal.reverse';
    public const CHART_MANAGE    = 'admin.accounting.chart.manage';
    public const PERIOD_REVIEW   = 'admin.accounting.period.review';
    public const PERIOD_CLOSE    = 'admin.accounting.period.close';
    public const PERIOD_REOPEN   = 'admin.accounting.period.reopen';
    public const REPORT_VIEW     = 'admin.accounting.report.view';

    public const CASH_MANAGE         = 'admin.accounting.cash.manage';
    public const CASH_POST           = 'admin.accounting.cash.post';
    public const BANK_IMPORT         = 'admin.accounting.bank.import';
    public const BANK_MATCH          = 'admin.accounting.bank.match';
    public const BANK_RECONCILE      = 'admin.accounting.bank.reconcile';

    public static function all(): array
    {
        return [
            self::JOURNAL_VIEW, self::JOURNAL_POST, self::JOURNAL_REVERSE,
            self::CHART_MANAGE, self::PERIOD_REVIEW, self::PERIOD_CLOSE,
            self::PERIOD_REOPEN, self::REPORT_VIEW,
            self::CASH_MANAGE, self::CASH_POST, self::BANK_IMPORT,
            self::BANK_MATCH, self::BANK_RECONCILE,
        ];
    }

    public static function allows(?Admin $actor, string $token): bool
    {
        if ($actor === null) {
            return true;
        }

        if ($actor->isSuperAdmin()) {
            return true;
        }

        return in_array($token, self::granted($actor), true);
    }

    public static function assert(?Admin $actor, string $token): void
    {
        if (! self::allows($actor, $token)) {
            throw new PostingRefused(
                ($actor?->username ?? $actor?->email ?? 'This admin')
                . ' does not hold the permission "' . $token . '".'
            );
        }
    }

    /** Every route name granted to this admin through any of their roles. */
    public static function granted(Admin $actor): array
    {
        $granted = [];

        foreach ($actor->roles as $assignment) {
            $permission = $assignment->permission ?? null;

            if ($permission === null || $permission->hasPermissions === null) {
                continue;
            }

            foreach ($permission->hasPermissions as $entry) {
                $granted[] = $entry->route;
            }
        }

        return array_values(array_unique($granted));
    }
}
