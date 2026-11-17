<?php

declare(strict_types=1);

namespace Determly\Chat\Run;

use Determly\Chat\Dispatch\Turn;

interface Responder
{
    /**
     * Next steps for this turn. After a tool result is appended, the runner calls this again.
     *
     * @return iterable<Step>
     */
    public function respond(Turn $turn): iterable;
}
