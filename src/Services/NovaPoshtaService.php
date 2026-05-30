<?php

namespace Commero\Services;

use Commero\Exceptions\NovaPoshtaException;
use Commero\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NovaPoshtaService
{
    private const API_URL = 'https://api.novaposhta.ua/v2.0/json/';

    public function hasApiKey(): bool
    {
        return filled($this->getApiKey());
    }

    /**
     * @return list<array{ref: string, name: string}>
     */
    public function searchSettlements(string $query, int $limit = 20): array
    {
        $normalizedQuery = trim($query);

        if (mb_strlen($normalizedQuery) < 2) {
            return [];
        }

        return Cache::remember(
            'nova_poshta:settlements:v2:'.md5(mb_strtolower($normalizedQuery).'|'.$limit),
            now()->addMinutes(10),
            function () use ($normalizedQuery, $limit): array {
                $data = $this->request('Address', 'searchSettlements', [
                    'CityName' => $normalizedQuery,
                    'Limit' => (string) $limit,
                    'Page' => '1',
                ]);

                return collect($data)
                    ->flatMap(fn (array $item): array => $item['Addresses'] ?? [])
                    ->map(function (array $item): ?array {
                        $ref = trim((string) ($item['Ref'] ?? $item['DeliveryCity'] ?? ''));
                        $name = trim((string) ($item['Present'] ?? $item['MainDescription'] ?? ''));

                        if ($ref === '' || $name === '') {
                            return null;
                        }

                        return [
                            'ref' => $ref,
                            'name' => $name,
                        ];
                    })
                    ->filter()
                    ->unique('ref')
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * @return list<array{ref: string, name: string}>
     */
    public function getWarehouses(string $settlementRef): array
    {
        $normalizedSettlementRef = trim($settlementRef);

        if ($normalizedSettlementRef === '') {
            return [];
        }

        return Cache::remember(
            'nova_poshta:warehouses:v1:'.$normalizedSettlementRef,
            now()->addHours(6),
            function () use ($normalizedSettlementRef): array {
                $data = $this->request('Address', 'getWarehouses', [
                    'SettlementRef' => $normalizedSettlementRef,
                ]);

                return collect($data)
                    ->map(function (array $item): ?array {
                        $ref = trim((string) ($item['Ref'] ?? ''));
                        $name = trim((string) ($item['Description'] ?? $item['ShortAddress'] ?? ''));

                        if ($ref === '' || $name === '') {
                            return null;
                        }

                        return [
                            'ref' => $ref,
                            'name' => $name,
                        ];
                    })
                    ->filter()
                    ->sortBy(fn (array $warehouse): string => mb_strtolower($warehouse['name']))
                    ->values()
                    ->all();
            }
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function request(string $modelName, string $calledMethod, array $methodProperties = []): array
    {
        $apiKey = $this->getApiKey();

        if (blank($apiKey)) {
            throw new NovaPoshtaException(__('Nova Poshta API key is not configured.'));
        }

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(10)
            ->post(self::API_URL, [
                'apiKey' => $apiKey,
                'modelName' => $modelName,
                'calledMethod' => $calledMethod,
                'methodProperties' => $methodProperties,
            ]);

        if ($response->failed()) {
            throw new NovaPoshtaException(__('Nova Poshta service is temporarily unavailable.'));
        }

        /** @var array{success?: bool, data?: array<int, array<string, mixed>>, errors?: array<int, string>, warnings?: array<int, string>} $payload */
        $payload = $response->json();

        if (! ($payload['success'] ?? false)) {
            $message = collect([
                ...($payload['errors'] ?? []),
                ...($payload['warnings'] ?? []),
            ])->filter()->implode(' ');

            throw new NovaPoshtaException($message !== '' ? $message : __('Unable to load Nova Poshta data.'));
        }

        return $payload['data'] ?? [];
    }

    private function getApiKey(): ?string
    {
        return SiteSetting::query()->first()?->nova_poshta_api_key;
    }
}
