<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery;

final class Catalog
{
    /**
     * Voice, custom voice, server TTS, text-reveal animations, custom loader
     * graphics, and file attachments are named once on the home page.
     *
     * @return list<string>
     */
    public static function skipped(): array
    {
        return [
            'Voice input and output',
            'Bring-your-own voice',
            'Server TTS',
            'Text-reveal animations',
            'Custom loader graphics',
            'File attachments',
        ];
    }

    /**
     * @return array<string, array{title: string, subtitle: string, summary: string, starters: list<string>}>
     */
    public static function all(): array
    {
        return [
            'data' => [
                'title' => 'Talk to Your Data',
                'subtitle' => 'Orders, a tool, and a table',
                'summary' => 'query_orders runs on the server, then the reply opens a markdown table. filter_orders is a read-only page tool that refreshes this table.',
                'starters' => ['Show the orders', 'Filter the table to shipped orders'],
            ],
            'ask' => [
                'title' => 'Ask User Question',
                'subtitle' => 'A question sheet, then chips',
                'summary' => 'ask_user_question pauses the turn. After you answer, suggest_replies shows follow-up chips.',
                'starters' => ['Ask me which orders to focus on'],
            ],
            'artifacts' => [
                'title' => 'Artifact Sidebar',
                'subtitle' => 'Markdown and a product card',
                'summary' => 'One turn opens a markdown note and a ProductCard in the artifact pane.',
                'starters' => ['Open the orders note and a product card'],
            ],
            'components' => [
                'title' => 'Dynamic Components',
                'subtitle' => 'JSON replies in the thread',
                'summary' => 'The reply is only JSON. The stream parser mounts DynamicForm, ProductCard, or Notice.',
                'starters' => ['Show a contact form', 'Show a product card', 'Show a notice'],
            ],
            'agent' => [
                'title' => 'Agent Loop',
                'subtitle' => 'Reason, use a tool, answer',
                'summary' => 'The turn reasons, calls lookup_order, then answers from that result. This page is scripted.',
                'starters' => ['Where is order 1001?', 'Where is order 1234?'],
            ],
            'laravel-ai' => [
                'title' => 'Laravel AI',
                'subtitle' => 'The model reads the sentence',
                'summary' => 'The sentence goes to Laravel AI. The model chooses lookup_order or query_orders, and it writes the answer.',
                'starters' => ['Did Jordan Blake\'s order ship?', 'Who still has an open order?', 'What did Sam Okonkwo pay?'],
            ],
            'prism' => [
                'title' => 'Prism',
                'subtitle' => 'The model reads the sentence',
                'summary' => 'The sentence goes to Prism. The model chooses lookup_order or query_orders, and it writes the answer.',
                'starters' => ['Did Jordan Blake\'s order ship?', 'Who still has an open order?', 'What did Sam Okonkwo pay?'],
            ],
            'approval' => [
                'title' => 'Tool Approval',
                'subtitle' => 'refund_order waits for you',
                'summary' => 'refund_order pauses with requiresApproval(). handle() runs only after you approve.',
                'starters' => ['Refund order 1001'],
            ],
            'actions' => [
                'title' => 'Tool Action Handlers',
                'subtitle' => 'A page tool changes the cart',
                'summary' => 'add_to_cart is not read-only, so the confirm bubble shows, then the cart on this page updates.',
                'starters' => ['Add the trail shoe to the cart'],
            ],
            'feedback' => [
                'title' => 'Feedback Events',
                'subtitle' => 'Votes and copies hit Laravel',
                'summary' => 'message:feedback and message:copy post to Laravel routes and show up in the list.',
                'starters' => ['Send a message I can rate'],
            ],
            'focus' => [
                'title' => 'Programmatic Input Focus',
                'subtitle' => 'focusInput from the page',
                'summary' => 'The buttons call determlyChat.focusInput() and persona:focusInput. autoFocusInput stays off.',
                'starters' => ['What does focus do here?'],
            ],
            'forms' => [
                'title' => 'Form Field Reference',
                'subtitle' => 'Every DynamicForm field',
                'summary' => 'One form covers text, email, tel, url, number, date, time, textarea, width, required, and helper text.',
                'starters' => ['Show the full contact form'],
            ],
            'events' => [
                'title' => 'Event Inspector',
                'subtitle' => 'Handle events on the page',
                'summary' => 'The log lists persona handle events as the turn runs. The stock widget does not turn on its inspector toggle.',
                'starters' => ['Say hello so I can watch the events'],
            ],
            'shadow' => [
                'title' => 'Shadow-aware Page Context',
                'subtitle' => 'Read inside a shadow root',
                'summary' => 'The product card lives in a shadow root. read_product walks that root from the page tool.',
                'starters' => ['What product is on this page?'],
            ],
            'tables' => [
                'title' => 'Streaming Markdown Tables',
                'subtitle' => 'A table in text deltas',
                'summary' => 'The orders table arrives as markdown text deltas, one row at a time.',
                'starters' => ['Stream the orders table'],
            ],
            'loaders' => [
                'title' => 'Tool and Reasoning Loaders',
                'subtitle' => 'Reasoning, then a tool',
                'summary' => 'A reasoning row stays on screen, then query_orders runs. The pause is long enough to see both.',
                'starters' => ['Check the open orders'],
            ],
        ];
    }

    public static function has(string $slug): bool
    {
        return isset(self::all()[$slug]);
    }

    /**
     * @return array{title: string, subtitle: string, summary: string, starters: list<string>}
     */
    public static function get(string $slug): array
    {
        return self::all()[$slug];
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
