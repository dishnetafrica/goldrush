<?php

namespace App\Accounting\Reports;

use App\Accounting\Models\ReportView;
use App\Models\Admin\Admin;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * The one write a report makes: who rendered it, for what scope, and what
 * the controls said. Nothing financial is ever written from here.
 */
class ReportAudit
{
    public function record(
        string $report, array $scope, array $controls, bool $interim,
        Admin|User|null $viewer = null, ?string $exportFormat = null, ?string $fileHash = null
    ): ReportView {
        [$type, $id] = match (true) {
            $viewer instanceof Admin => ['admin', $viewer->id],
            $viewer instanceof User  => ['user', $viewer->id],
            default                  => ['console', null],
        };

        return ReportView::create([
            'report'          => $report,
            'viewer_type'     => $type,
            'viewer_id'       => $id,
            'scope'           => $scope,
            'interim'         => $interim,
            'controls'        => array_map(fn ($c) => ['key' => $c['key'], 'status' => $c['status'], 'difference' => $c['difference']], $controls),
            'controls_passed' => Control::allPassed($controls),
            'export_format'   => $exportFormat,
            'file_hash'       => $fileHash,
            'rendered_at'     => Carbon::now(),
        ]);
    }
}
