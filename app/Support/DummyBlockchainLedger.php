<?php

namespace App\Support;

use App\Models\HarvestBatch;
use App\Models\HarvestBatchEvent;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DummyBlockchainLedger
{
    public const NETWORK = 'dummy-ledger';
    public const STATUS = 'simulated';

    /**
     * Build a stable proof payload without mutating storage.
     *
     * @return array<string, mixed>
     */
    public static function proof(HarvestBatch $batch): array
    {
        $events = self::events($batch);
        $eventProofs = $events->map(fn (HarvestBatchEvent $event): array => self::eventProof($batch, $event))->values();

        $payload = [
            'batch_code' => $batch->code,
            'batch_status' => $batch->status,
            'farmer_user_id' => $batch->farmer_user_id,
            'farm_id' => $batch->farm_id,
            'farm_name_snapshot' => $batch->farm_name_snapshot,
            'variety' => $batch->variety,
            'grade' => $batch->grade,
            'quantity_kg' => $batch->quantity_kg === null ? null : (float) $batch->quantity_kg,
            'unit' => $batch->unit,
            'fruit_count' => $batch->fruit_count,
            'harvest_date' => $batch->harvest_date?->toDateString(),
            'event_chain' => $eventProofs->all(),
        ];

        $hash = self::hash($payload);
        $blockNumber = 100000 + (int) $batch->id + $events->count();

        return [
            'network' => self::NETWORK,
            'status' => self::STATUS,
            'anchor_ref' => 'DRT-' . Str::upper(Str::substr($hash, 0, 12)),
            'tx_hash' => '0x' . $hash,
            'block_number' => $blockNumber,
            'event_count' => $events->count(),
            'last_event_hash' => $eventProofs->last()['ledger_hash'] ?? null,
            'payload' => $payload,
        ];
    }

    /**
     * Persist the dummy proof on the batch record.
     *
     * @return array<string, mixed>
     */
    public static function sync(HarvestBatch $batch): array
    {
        $proof = self::proof($batch);

        $batch->forceFill([
            'blockchain_network' => $proof['network'],
            'blockchain_status' => $proof['status'],
            'blockchain_anchor_ref' => $proof['anchor_ref'],
            'blockchain_tx_hash' => $proof['tx_hash'],
            'blockchain_block_number' => $proof['block_number'],
            'blockchain_payload' => $proof['payload'],
            'blockchain_synced_at' => now(),
        ])->save();

        return $proof;
    }

    /**
     * Prepare the append-only ledger fields for a new event.
     *
     * @return array<string, mixed>
     */
    public static function prepareEvent(HarvestBatch $batch, string $title, ?User $actor = null, array $metadata = [], ?string $actorLabel = null): array
    {
        $events = self::events($batch);
        $previousEvent = $events->last();
        $ledgerHeight = ($previousEvent?->ledger_height ?? $events->count()) + 1;
        $eventAt = now();

        $payload = [
            'batch_code' => $batch->code,
            'batch_status' => $batch->status,
            'title' => $title,
            'actor_user_id' => $actor?->id,
            'actor_label' => $actorLabel ?? $actor?->full_name ?? null,
            'event_at' => $eventAt->toISOString(),
            'metadata' => self::normalizeMetadata($metadata),
            'previous_ledger_hash' => $previousEvent?->ledger_hash,
            'ledger_height' => $ledgerHeight,
        ];

        $ledgerHash = self::hash($payload);

        return [
            'event_at' => $eventAt,
            'ledger_height' => $ledgerHeight,
            'previous_ledger_hash' => $previousEvent?->ledger_hash,
            'ledger_hash' => $ledgerHash,
            'metadata' => $metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function eventProof(HarvestBatch $batch, HarvestBatchEvent $event): array
    {
        $payload = [
            'batch_code' => $batch->code,
            'batch_status' => $batch->status,
            'title' => $event->title,
            'actor_user_id' => $event->actor_user_id,
            'actor_label' => $event->actor_label,
            'event_at' => $event->event_at?->toISOString(),
            'metadata' => self::normalizeMetadata($event->metadata ?? []),
            'previous_ledger_hash' => $event->previous_ledger_hash,
            'ledger_height' => $event->ledger_height,
        ];

        return [
            'ledger_hash' => $event->ledger_hash ?: self::hash($payload),
            'previous_ledger_hash' => $event->previous_ledger_hash,
            'ledger_height' => $event->ledger_height,
            'event_at' => $event->event_at?->toISOString(),
        ];
    }

    /**
     * @return Collection<int, HarvestBatchEvent>
     */
    private static function events(HarvestBatch $batch): Collection
    {
        if ($batch->relationLoaded('events')) {
            return $batch->events->sortBy('id')->values();
        }

        return $batch->events()->orderBy('id')->get();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function hash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private static function normalizeMetadata(array $metadata): array
    {
        ksort($metadata);

        foreach ($metadata as $key => $value) {
            if (is_array($value)) {
                $metadata[$key] = self::normalizeMetadata($value);
            }
        }

        return $metadata;
    }
}
