<?php

namespace App\Investor\Exceptions;

use RuntimeException;

/** Something was asked of the ledger that would have made it untrue. */
class LedgerException extends RuntimeException
{
}
