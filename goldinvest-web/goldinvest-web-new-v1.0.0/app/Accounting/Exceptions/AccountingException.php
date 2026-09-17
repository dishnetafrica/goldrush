<?php

namespace App\Accounting\Exceptions;

use RuntimeException;

/** Something was asked of the general ledger that would have made it untrue. */
class AccountingException extends RuntimeException
{
}
