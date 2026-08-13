<?php

namespace Wncms\Services\Security;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class SecurityEventRetentionService
{
    /**
     * Prune expired security events in bounded batches.
     *
     * This is the only normal deletion path for the append-only security-event store.
     *
     * @param  \Carbon\CarbonImmutable  $cutoff
     * @param  int  $batchSize
     *
     * @return int
     */
    public function prune(CarbonImmutable $cutoff, int $batchSize = 500): int
    {
        if ($batchSize < 1 || $batchSize > 500) {
            throw new \InvalidArgumentException('Security event prune batch size must be between 1 and 500.');
        }

        $deleted = 0;

        do {
            $ids = DB::table('api_security_events')
                ->where('occurred_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($batchSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $deleted += DB::table('api_security_events')->whereIn('id', $ids)->delete();
        } while ($ids->count() === $batchSize);

        return $deleted;
    }
}
