<?php

namespace App\Support;

use App\Models\HarvestBatch;
use App\Models\HarvestBatchEvent;
use App\Models\User;

class Traceability
{
    public static function recordEvent(
        HarvestBatch $batch,
        string $title,
        ?User $actor = null,
        array $metadata = [],
        ?string $actorLabel = null,
    ): HarvestBatchEvent {
        $ledger = DummyBlockchainLedger::prepareEvent($batch, $title, $actor, $metadata, $actorLabel);

        $event = HarvestBatchEvent::create([
            'harvest_batch_id' => $batch->id,
            'title' => $title,
            'actor_label' => $actorLabel ?? $actor?->full_name ?? null,
            'actor_user_id' => $actor?->id,
            'event_at' => $ledger['event_at'],
            'metadata' => $metadata,
            'previous_ledger_hash' => $ledger['previous_ledger_hash'],
            'ledger_hash' => $ledger['ledger_hash'],
            'ledger_height' => $ledger['ledger_height'],
        ]);

        DummyBlockchainLedger::sync(
            $batch->newQuery()->with(['events' => fn ($query) => $query->orderBy('id')])->findOrFail($batch->id)
        );

        return $event;
    }
}
