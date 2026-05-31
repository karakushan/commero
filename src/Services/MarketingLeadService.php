<?php

namespace Commero\Services;

use Commero\Models\MarketingLead;
use Commero\Models\User;
use Commero\Notifications\MarketingLeadReceivedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class MarketingLeadService
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes, ?Request $request = null): MarketingLead
    {
        $request ??= request();
        $sourceUrl = $this->resolveSourceUrl($request, $attributes['source_url'] ?? null);

        $lead = MarketingLead::query()->create([
            'type' => (string) $attributes['type'],
            'status' => (string) ($attributes['status'] ?? 'new'),
            'subject' => $this->nullableString($attributes['subject'] ?? null),
            'name' => $this->nullableString($attributes['name'] ?? null),
            'phone' => $this->nullableString($attributes['phone'] ?? null),
            'email' => $this->nullableString($attributes['email'] ?? null),
            'message' => $this->nullableString($attributes['message'] ?? null),
            'product_id' => $attributes['product_id'] ?? null,
            'locale' => $this->nullableString($attributes['locale'] ?? app()->getLocale()),
            'source_url' => $sourceUrl,
            'form_data' => $this->normalizeArray($attributes['form_data'] ?? []),
            'client_meta' => $this->buildClientMeta($request, $attributes['client_meta'] ?? [], $sourceUrl),
            'internal_note' => $this->nullableString($attributes['internal_note'] ?? null),
            'processed_at' => $attributes['processed_at'] ?? null,
        ]);

        $admins = User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'admin')->where('guard_name', 'web'))
            ->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new MarketingLeadReceivedNotification($lead));
        }

        return $lead;
    }

    /**
     * @param  array<string, mixed>  $extraMeta
     * @return array<string, mixed>
     */
    private function buildClientMeta(?Request $request, array $extraMeta = [], ?string $sourceUrl = null): array
    {
        $baseMeta = [
            'ip' => $request?->ip(),
            'forwarded_for' => $request?->header('X-Forwarded-For'),
            'user_agent' => $request?->userAgent(),
            'referer' => $request?->headers->get('referer'),
            'url' => $sourceUrl,
            'request_url' => $request?->fullUrl(),
            'path' => $request?->path(),
            'route_name' => $request?->route()?->getName(),
            'method' => $request?->method(),
            'locale' => app()->getLocale(),
        ];

        return array_filter(
            array_merge($baseMeta, $extraMeta),
            fn (mixed $value): bool => ! is_null($value) && $value !== ''
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function resolveSourceUrl(?Request $request, mixed $sourceUrl): ?string
    {
        $explicitUrl = $this->nullableString($sourceUrl);

        if ($explicitUrl !== null && ! $this->isLivewireEndpoint($explicitUrl)) {
            return $explicitUrl;
        }

        $referer = $this->nullableString($request?->headers->get('referer'));

        if ($referer !== null) {
            return $referer;
        }

        return $this->nullableString($request?->fullUrl());
    }

    private function isLivewireEndpoint(string $url): bool
    {
        return str_contains($url, '/livewire/') || str_contains($url, '/livewire-');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeArray(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $normalized[$key] = $this->normalizeArray($value);

                continue;
            }

            $normalized[$key] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }
}
