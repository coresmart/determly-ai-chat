<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery\Tests;

use Determly\Chat\Dispatch\Turn;
use Determly\Chat\Gallery\GalleryWidget;
use Determly\Chat\Gallery\ModelChat;
use Determly\Chat\Gallery\Tools\LookupOrder;
use Determly\Chat\Gallery\Tools\QueryOrders;
use Determly\Chat\Gallery\Tools\RefundOrder;
use Determly\Chat\Laravel\Ai\ConfigurationResponder;
use Determly\Chat\Laravel\Ai\LaravelAiResponder;
use Determly\Chat\Laravel\Ai\LaravelChatAgent;
use Determly\Chat\Laravel\Ai\PrismResponder;
use Determly\Chat\Run\MemoryExecutionStore;
use Determly\Chat\Run\Responder;
use Determly\Chat\Run\Runner;
use Determly\Chat\ToolRegistry;
use Determly\Chat\Wire\BufferWriter;
use Determly\Chat\Wire\Frame;
use Determly\Chat\Wire\Session;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Laravel\Ai\AiServiceProvider;
use Laravel\Ai\Responses\Data\ToolCall as LaravelToolCall;
use PHPUnit\Framework\TestCase;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\PrismServiceProvider;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\Text\Step as PrismStep;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\ToolCall as PrismToolCall;
use Prism\Prism\ValueObjects\Usage;

final class ModelChatTest extends TestCase
{
    private const SENTENCE = "Did Jordan Blake's order ship?";

    private static ?Application $app = null;

    public static function setUpBeforeClass(): void
    {
        $app = new Application(dirname(__DIR__, 3));
        $app->instance('config', new Repository([
            'ai' => require $app->basePath('vendor/laravel/ai/config/ai.php'),
            'prism' => require $app->basePath('vendor/prism-php/prism/config/prism.php'),
        ]));
        Facade::setFacadeApplication($app);
        $app->register(AiServiceProvider::class);
        $app->register(PrismServiceProvider::class);
        $app->boot();
        self::$app = $app;
    }

    protected function tearDown(): void
    {
        $this->key('OPENAI_API_KEY', null);
    }

    public function test_laravel_ai_sends_the_sentence_and_returns_the_model_answer(): void
    {
        $this->key('OPENAI_API_KEY', 'test-key');
        $tools = $this->tools();
        $answer = "Jordan Blake's order is still open, so it has not shipped.";
        LaravelChatAgent::fake([
            new LaravelToolCall('call_1', 'query_orders', ['status' => 'all']),
            $answer,
        ]);

        $responder = ModelChat::make(ModelChat::LARAVEL, $tools, 'openai', 'gpt-4o-mini');
        $this->assertInstanceOf(LaravelAiResponder::class, $responder);

        $frames = $this->play($responder, $tools, self::SENTENCE);

        $this->assertSame(['query_orders'], $this->toolNames($frames));
        $this->assertSame('all', $this->event($frames, 'tool_start')->data['parameters']['status']);
        $this->assertTrue($this->event($frames, 'tool_complete')->data['success']);
        $this->assertSame($answer, trim($this->text($frames)));
        $this->assertStringNotContainsString('Order 1002 for Jordan Blake is open.', $this->text($frames));
    }

    public function test_prism_sends_the_sentence_and_returns_the_model_answer(): void
    {
        $this->key('OPENAI_API_KEY', 'test-key');
        $tools = $this->tools();
        $answer = 'I checked the order book. Jordan Blake has not shipped.';
        $fake = Prism::fake([
            $this->prismResponse('', FinishReason::ToolCalls, [
                new PrismToolCall('call_1', 'query_orders', ['status' => 'all']),
            ]),
            $this->prismResponse($answer, FinishReason::Stop),
        ]);

        $responder = ModelChat::make(ModelChat::PRISM, $tools, 'openai', 'gpt-4o-mini');
        $this->assertInstanceOf(PrismResponder::class, $responder);

        $frames = $this->play($responder, $tools, self::SENTENCE);

        $this->assertSame(['query_orders'], $this->toolNames($frames));
        $this->assertSame($answer, trim($this->text($frames)));
        $fake->assertRequest(function (array $requests): void {
            $this->assertSame('openai', $requests[0]->provider());
            $this->assertSame('gpt-4o-mini', $requests[0]->model());
            $this->assertSame(1, $requests[0]->maxSteps());
            $contents = array_map(fn ($message): string => $message->content ?? '', $requests[0]->messages());
            $this->assertContains(self::SENTENCE, $contents);
            $names = array_map(fn ($tool): string => $tool->name(), $requests[0]->tools());
            $this->assertEqualsCanonicalizing(['lookup_order', 'query_orders'], $names);
            $this->assertNotContains('refund_order', $names);
        });
    }

    public function test_a_missing_key_does_not_fall_back_to_the_script(): void
    {
        $this->key('OPENAI_API_KEY', null);
        $responder = ModelChat::make(ModelChat::LARAVEL, $this->tools(), 'openai', 'gpt-4o-mini');
        $this->assertInstanceOf(ConfigurationResponder::class, $responder);

        $frames = $this->play($responder, $this->tools(), self::SENTENCE);

        $message = (string) $this->event($frames, 'execution_error')->data['error']['message'];
        $this->assertStringContainsString('OPENAI_API_KEY', $message);
        $this->assertStringContainsString('not scripted', $message);
        $this->assertSame([], $this->toolNames($frames));
        $this->assertStringNotContainsString('Jordan Blake is open', $this->text($frames));
    }

    public function test_widget_offers_a_question_the_script_cannot_parse(): void
    {
        $html = GalleryWidget::html('prism');

        $this->assertStringContainsString('/examples/prism/dispatch', $html);
        $this->assertStringContainsString('Who still has an open order?', $html);
        $this->assertStringNotContainsString('autoFocusInput', $html);
    }

    private function tools(): ToolRegistry
    {
        $tools = new ToolRegistry;
        $tools->register(new QueryOrders, 'gallery');
        $tools->register(new RefundOrder, 'gallery');
        $tools->register(new LookupOrder, 'gallery');

        return $tools;
    }

    private function key(string $name, ?string $value): void
    {
        if ($value === null) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);

            return;
        }
        putenv($name.'='.$value);
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }

    /**
     * @param  list<PrismToolCall>  $toolCalls
     */
    private function prismResponse(string $text, FinishReason $reason, array $toolCalls = []): TextResponse
    {
        $step = new PrismStep(
            text: $text,
            finishReason: $reason,
            toolCalls: $toolCalls,
            toolResults: [],
            providerToolCalls: [],
            usage: new Usage(1, 1),
            meta: new Meta('fake', 'gpt-4o-mini'),
            messages: [],
            systemPrompts: [],
        );

        return new TextResponse(
            steps: collect([$step]),
            text: $text,
            finishReason: $reason,
            toolCalls: $toolCalls,
            toolResults: [],
            usage: new Usage(1, 1),
            meta: new Meta('fake', 'gpt-4o-mini'),
            messages: collect([]),
        );
    }

    /**
     * @return list<Frame>
     */
    private function play(Responder $responder, ToolRegistry $tools, string $sentence): array
    {
        $runner = new Runner($responder, $tools, new MemoryExecutionStore);
        $writer = new BufferWriter;
        Session::run($writer, function ($emit) use ($runner, $sentence): void {
            $runner->dispatch(Turn::fromArray([
                'messages' => [['role' => 'user', 'content' => $sentence]],
            ]), $emit);
        });

        return Frame::parse($writer->body);
    }

    /**
     * @param  list<Frame>  $frames
     * @return list<string>
     */
    private function toolNames(array $frames): array
    {
        $names = [];
        foreach ($frames as $frame) {
            if ($frame->event === 'tool_start' && is_string($frame->data['toolName'] ?? null)) {
                $names[] = $frame->data['toolName'];
            }
        }

        return $names;
    }

    /**
     * @param  list<Frame>  $frames
     */
    private function text(array $frames): string
    {
        $text = '';
        foreach ($frames as $frame) {
            if ($frame->event === 'text_delta') {
                $text .= (string) $frame->data['delta'];
            }
        }

        return $text;
    }

    /**
     * @param  list<Frame>  $frames
     */
    private function event(array $frames, string $name): Frame
    {
        foreach ($frames as $frame) {
            if ($frame->event === $name) {
                return $frame;
            }
        }
        $this->fail('Missing event '.$name);
    }
}
