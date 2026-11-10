<?php

namespace App\Exceptions;

/** A user-facing reason a Workflow "send to another tab" receive could not run (rolled back). */
class WorkflowHandoffException extends \RuntimeException
{
}
