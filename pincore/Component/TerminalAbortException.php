<?php

namespace Pinoox\Component;

use RuntimeException;

/**
 * Stops a CLI command without terminating the PHP process (test-safe).
 */
class TerminalAbortException extends RuntimeException
{
}
