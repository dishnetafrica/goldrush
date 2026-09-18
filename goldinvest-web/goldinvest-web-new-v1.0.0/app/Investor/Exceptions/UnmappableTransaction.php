<?php

namespace App\Investor\Exceptions;

/**
 * A transaction exists that the ledger does not know how to classify. Raised
 * rather than guessed at: an investor statement built on a guess is worse than
 * no statement at all.
 */
class UnmappableTransaction extends LedgerException
{
    public function __construct(public readonly string $type, public readonly ?string $trxId = null)
    {
        parent::__construct(
            'No ledger mapping for transaction type "' . $type . '"'
            . ($trxId ? ' (' . $trxId . ')' : '')
            . '. Add a mapping before generating statements for this investor.'
        );
    }
}
