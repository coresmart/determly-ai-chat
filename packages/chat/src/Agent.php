<?php

declare(strict_types=1);

namespace Determly\Chat;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Wire\Emitter;

interface Agent
{
    public function respond(Turn $turn, Emitter $emit): void;
}
