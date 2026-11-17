<?php

declare(strict_types=1);

namespace Determly\Chat\Gallery\Http;

use Determly\Chat\Gallery\Activity;
use Determly\Chat\Gallery\Catalog;
use Determly\Chat\Gallery\GalleryWidget;
use Determly\Chat\Gallery\ModelChat;
use Determly\Chat\Gallery\Orders;
use Determly\Chat\Gallery\Refunds;
use Determly\Chat\Laravel\Ai\AiChoice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class GalleryController
{
    public function home(): View
    {
        return view('home', [
            'examples' => Catalog::all(),
            'skipped' => Catalog::skipped(),
        ]);
    }

    public function show(string $example): View
    {
        abort_unless(Catalog::has($example), 404);

        $data = [
            'example' => Catalog::get($example),
            'widget' => GalleryWidget::html($example),
            'orders' => Orders::query('all'),
            'refunds' => Refunds::all(),
            'activity' => Activity::all(),
        ];
        if (ModelChat::handles($example)) {
            $data['model'] = ModelChat::describe(
                $example,
                AiChoice::present(config('determly-chat.provider')),
                AiChoice::present(config('determly-chat.model')),
            );

            return view('examples.model', $data);
        }

        return view('examples.'.$example, $data);
    }

    public function orders(Request $request): View
    {
        $status = (string) $request->query('status', 'all');

        return view('partials.orders', [
            'orders' => Orders::query($status),
            'status' => strtolower(trim($status)) === '' ? 'all' : strtolower(trim($status)),
        ]);
    }

    public function refunds(): View
    {
        return view('partials.refunds', ['refunds' => Refunds::all()]);
    }

    public function activity(): View
    {
        return view('partials.activity', ['activity' => Activity::all()]);
    }

    public function feedback(Request $request): View
    {
        $payload = $this->payload($request);
        Activity::record('message:feedback', [
            'type' => (string) ($payload['type'] ?? ''),
            'messageId' => (string) ($payload['messageId'] ?? ''),
        ]);

        return view('partials.activity', ['activity' => Activity::all()]);
    }

    public function copied(Request $request): View
    {
        $payload = $this->payload($request);
        Activity::record('message:copy', [
            'messageId' => (string) ($payload['id'] ?? $payload['messageId'] ?? ''),
        ]);

        return view('partials.activity', ['activity' => Activity::all()]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $json = $request->json()->all();
        if ($json !== []) {
            return $json;
        }
        $all = $request->all();

        return is_array($all) ? $all : [];
    }
}
