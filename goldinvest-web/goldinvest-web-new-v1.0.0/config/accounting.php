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

];
