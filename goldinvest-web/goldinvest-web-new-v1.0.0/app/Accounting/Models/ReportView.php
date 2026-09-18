<?php

namespace App\Accounting\Models;

use App\Accounting\Exceptions\AccountingException;
use Illuminate\Database\Eloquent\Model;

/**
 * Who looked at which report, and what the controls said when they did.
 *
 * Append only. The point of an audit row is that it still says the same thing
 * later; a report view that can be edited afterwards records nothing.
 */
class ReportView extends Model
{
    protected $table = 'accounting_report_views';

    protected $fillable = [
        'report', 'viewer_type', 'viewer_id', 'scope', 'interim', 'controls',
        'controls_passed', 'export_format', 'file_hash', 'rendered_at',
    ];

    protected $casts = [
        'scope'           => 'array',
        'controls'        => 'array',
        'interim'         => 'boolean',
        'controls_passed' => 'boolean',
        'rendered_at'     => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $view) {
            throw new AccountingException('Report audit row #' . $view->id . ' is append only and cannot be changed.');
        });

        static::deleting(function (self $view) {
            throw new AccountingException('Report audit row #' . $view->id . ' cannot be deleted.');
        });
    }
}
