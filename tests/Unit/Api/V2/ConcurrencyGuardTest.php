<?php

namespace Wncms\Tests\Unit\Api\V2;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Wncms\Api\V2\ConcurrencyGuard;
use Wncms\Api\V2\Exceptions\ApiConflictException;
use Wncms\Tests\TestCase;

class ConcurrencyGuardTest extends TestCase
{
    public function test_it_accepts_matching_quoted_and_weak_etags(): void
    {
        $guard = new ConcurrencyGuard;
        $model = $this->model('2026-08-11T10:30:00+08:00');
        $revision = $guard->revision($model);

        $guard->assertMatches($model, '"'.$revision.'"');
        $guard->assertMatches($model, 'W/"'.$revision.'"');

        $this->assertSame('"'.$revision.'"', $guard->responseEtag($model));
    }

    public function test_it_uses_a_stable_utc_updated_at_serialization_for_revisions(): void
    {
        $guard = new ConcurrencyGuard;

        $first = $this->model('2026-08-11T10:30:00+08:00');
        $second = clone $first;
        $second->setRawAttributes([
            'id' => 42,
            'updated_at' => Carbon::parse('2026-08-11T02:30:00Z'),
        ]);

        $this->assertSame($guard->revision($first), $guard->revision($second));
    }

    public function test_it_rejects_missing_if_match_values(): void
    {
        $this->expectException(ApiConflictException::class);

        (new ConcurrencyGuard)->assertMatches($this->model('2026-08-11T02:30:00Z'), null);
    }

    public function test_it_rejects_stale_if_match_values(): void
    {
        $this->expectException(ApiConflictException::class);

        (new ConcurrencyGuard)->assertMatches($this->model('2026-08-11T02:30:00Z'), '"stale"');
    }

    /**
     * Create an Eloquent model fixture with a stable route key and revision time.
     */
    private function model(string $updatedAt): Model
    {
        $model = new ApiV2ConcurrencyModel;

        $model->setRawAttributes([
            'id' => 42,
            'updated_at' => Carbon::parse($updatedAt),
        ]);

        return $model;
    }
}

class ApiV2ConcurrencyModel extends Model
{
    protected $table = 'api_v2_concurrency_models';
}
