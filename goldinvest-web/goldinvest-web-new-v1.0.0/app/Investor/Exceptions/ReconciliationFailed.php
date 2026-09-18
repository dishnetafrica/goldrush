<?php

namespace App\Investor\Exceptions;

/**
 * Raised when a statement's own arithmetic disagrees with the ledger.
 *
 * Nothing catches this to carry on regardless. A statement that looks
 * professional and is wrong is worse than no statement, so the document is not
 * produced at all and the investor is told it is temporarily unavailable.
 */
class ReconciliationFailed extends LedgerException
{
    public function __construct(public readonly array $failures)
    {
        parent::__construct(
            'Statement refused: it does not reconcile against the ledger. '
            . implode(' ', $failures)
        );
    }
}
