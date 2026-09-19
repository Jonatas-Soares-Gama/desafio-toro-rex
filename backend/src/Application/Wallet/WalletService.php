<?php

declare(strict_types=1);

namespace App\Application\Wallet;

use App\Infrastructure\Persistence\SalesRepository;

final class WalletService
{
    public function __construct(private readonly SalesRepository $sales)
    {
    }

    /** @return array{balance: int, entries: list<array{id: int, campaign_id: int, sale_id: int, type: string, points: int, description: string, created_at: string}>} */
    public function show(int $sellerId): array
    {
        $entries = $this->sales->findWalletEntries($sellerId);
        $balance = 0;

        foreach ($entries as $entry) {
            $balance += $entry['type'] === 'credit' ? $entry['points'] : -$entry['points'];
        }

        return [
            'balance' => $balance,
            'entries' => $entries,
        ];
    }
}
