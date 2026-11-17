<?php

declare(strict_types=1);

namespace Determly\Chat\Laravel\Ai;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;

/**
 * One model step per call. The Determly runner executes tools, pauses for approval, and pauses for page tools.
 * A higher step limit would let Laravel AI run the tool itself and race that runner.
 */
#[MaxSteps(1)]
final class LaravelChatAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  iterable<int, mixed>  $messages
     * @param  iterable<int, mixed>  $tools
     */
    public function __construct(
        private readonly string $agentInstructions,
        private readonly iterable $agentMessages,
        private readonly iterable $agentTools,
    ) {}

    public function instructions(): string
    {
        return $this->agentInstructions;
    }

    public function messages(): iterable
    {
        return $this->agentMessages;
    }

    public function tools(): iterable
    {
        return $this->agentTools;
    }
}
