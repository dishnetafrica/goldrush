<?php

namespace App\Accounting\Exceptions;

/**
 * A journal was refused before anything was written.
 *
 * Refusals are not failures of the system; they are the system working. An
 * unbalanced journal, a posting into a closed period, or an actor without the
 * permission to post are all things that must not be recorded rather than
 * things to be recorded and fixed later.
 */
class PostingRefused extends AccountingException
{
}
