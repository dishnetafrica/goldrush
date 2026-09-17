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
