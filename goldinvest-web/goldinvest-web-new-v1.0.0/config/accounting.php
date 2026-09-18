<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bank reconciliation: segregation of duties
    |--------------------------------------------------------------------------
    |
    | By default the person who imported a bank statement may not also sign off
    | the reconciliation covering it. A reconciliation checked by the only pair
    | of eyes that has seen the evidence is not a control.
    |
    | A single-admin environment cannot satisfy that, so the rule can be relaxed
    | deliberately. When it is, only a Super Admin may take the exception, a
    | reason is required, and the exception is recorded on the reconciliation —
    | the control is waived visibly, not bypassed.
    |
    | Leave this false in production.
    |
    */

    'reconciliation' => [
        'allow_self_signoff' => env('ACCOUNTING_ALLOW_SELF_SIGNOFF', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Expenses: segregation of duties
    |--------------------------------------------------------------------------
    |
    | By default nobody may approve an expense they submitted themselves. An
    | expense checked only by the person claiming it is not a control, it is a
    | formality.
    |
    | As with reconciliation, a single-admin environment cannot satisfy that, so
    | the rule can be relaxed deliberately. When it is, only a Super Admin may
    | take the exception, a reason is required, and the exception is recorded on
    | the expense itself — waived visibly, not bypassed.
    |
    | Leave this false in production.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Investor allocation policy (Phase 3F)
    |--------------------------------------------------------------------------
    |
    | The investor pool for a period is the sum of the period's finalized,
    | recorded deal results, each split under that deal's own recorded terms.
    | Nothing here is a return: it is a share of what the company actually
    | realized, and when the company realized nothing the share is nothing.
    |
    | reserve_percent is the company's retention before allocation. It is zero
    | by policy and exists only so that a retention, if one is ever agreed, has
    | a single place to be declared rather than being wired into a calculation.
    |
    */

    'allocation' => [
        'reserve_percent' => (float) env('ACCOUNTING_ALLOCATION_RESERVE_PERCENT', 0),
    ],

    'expenses' => [
        'allow_self_approval' => env('ACCOUNTING_ALLOW_SELF_APPROVAL', false),

        // The largest evidence file that may be attached to an expense.
        'max_evidence_bytes' => (int) env('ACCOUNTING_MAX_EVIDENCE_BYTES', 10 * 1024 * 1024),

        // What evidence may be. A receipt is a picture or a PDF of one; anything
        // that could execute has no business being filed as proof of payment.
        'evidence_mime_types' => [
            'application/pdf', 'image/jpeg', 'image/png', 'image/heic', 'image/webp', 'image/tiff',
        ],
    ],

];
