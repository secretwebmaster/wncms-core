<?php

namespace Wncms\Tests\Unit\Api\V2;

use Wncms\Api\V2\Risk\RiskContextException;
use Wncms\Api\V2\Risk\WebsiteBindingResolver;
use Wncms\Models\Channel;
use Wncms\Models\Website;
use Wncms\Tests\TestCase;

class WebsiteBindingResolverTest extends TestCase
{
    public function test_scoped_patch_omission_preserves_existing_binding(): void
    {
        config(['wncms.models.channel.website_mode' => 'multi']);
        $website = Website::query()->firstOrFail();
        $channel = Channel::create(['name' => 'Binding', 'slug' => 'binding-'.uniqid()]);
        $channel->websites()->sync([$website->getKey()]);

        $binding = app(WebsiteBindingResolver::class)->resolve([], Channel::class, 'update', $channel);

        $this->assertFalse($binding->supplied);
        $this->assertFalse($binding->shouldSync);
        $this->assertSame([(int) $website->getKey()], $binding->websiteIds);
    }

    public function test_scoped_mutation_rejects_explicit_empty_and_conflicting_selectors(): void
    {
        config(['wncms.models.channel.website_mode' => 'multi']);
        $website = Website::query()->firstOrFail();
        $other = Website::create([
            'site_name' => 'Binding conflict',
            'domain' => 'binding-conflict-'.uniqid().'.test',
            'user_id' => $website->user_id,
        ]);
        $channel = Channel::create(['name' => 'Binding', 'slug' => 'binding-'.uniqid()]);
        $channel->websites()->sync([$website->getKey()]);
        $resolver = app(WebsiteBindingResolver::class);

        foreach ([
            ['website_ids' => []],
            ['website_ids' => null],
            ['website_ids' => ''],
            ['website_id' => $website->getKey(), 'website_key' => 'website:'.$other->getKey()],
            ['website_id' => $website->getKey(), 'website_ids' => [$other->getKey()]],
        ] as $input) {
            try {
                $resolver->resolve($input, Channel::class, 'update', $channel);
                $this->fail('Expected invalid website binding.');
            } catch (RiskContextException $exception) {
                $this->assertSame('validation.failed', $exception->errorCode);
                $this->assertSame(422, $exception->httpStatus);
            }
        }
    }

    public function test_global_mutation_keeps_omission_and_explicit_empty_non_scoping_semantics(): void
    {
        config(['wncms.models.channel.website_mode' => 'global']);
        $resolver = app(WebsiteBindingResolver::class);

        $omitted = $resolver->resolve([], Channel::class, 'update');
        $empty = $resolver->resolve(['website_ids' => []], Channel::class, 'update');

        $this->assertSame([], $omitted->websiteIds);
        $this->assertFalse($omitted->shouldSync);
        $this->assertSame([], $empty->websiteIds);
        $this->assertFalse($empty->shouldSync);
    }

    public function test_single_mode_rejects_multiple_values_but_accepts_each_single_selector(): void
    {
        config(['wncms.models.channel.website_mode' => 'single']);
        $first = Website::query()->firstOrFail();
        $second = Website::create([
            'site_name' => 'Single binding second',
            'domain' => 'single-binding-'.uniqid().'.test',
            'user_id' => $first->user_id,
        ]);
        $resolver = app(WebsiteBindingResolver::class);

        try {
            $resolver->resolve(['website_ids' => [$first->getKey(), $second->getKey()]], Channel::class, 'store');
            $this->fail('Expected multiple single-mode websites to be rejected.');
        } catch (RiskContextException $exception) {
            $this->assertSame('validation.failed', $exception->errorCode);
            $this->assertSame(422, $exception->httpStatus);
        }

        $this->assertSame([(int) $first->getKey()], $resolver->resolve(['website_id' => $first->getKey()], Channel::class, 'store')->websiteIds);
        $this->assertSame([(int) $second->getKey()], $resolver->resolve(['website_key' => 'website:'.$second->getKey()], Channel::class, 'update')->websiteIds);
    }

    public function test_single_mode_patch_omission_requires_exactly_one_existing_binding(): void
    {
        config(['wncms.models.channel.website_mode' => 'single']);
        $first = Website::query()->firstOrFail();
        $second = Website::create([
            'site_name' => 'Single omitted second',
            'domain' => 'single-omitted-'.uniqid().'.test',
            'user_id' => $first->user_id,
        ]);
        $resolver = app(WebsiteBindingResolver::class);

        $valid = Channel::create(['name' => 'Single valid', 'slug' => 'single-valid-'.uniqid()]);
        $valid->websites()->sync([$first->getKey()]);
        $this->assertSame([(int) $first->getKey()], $resolver->resolve([], Channel::class, 'update', $valid)->websiteIds);

        foreach ([[], [$first->getKey(), $second->getKey()]] as $websiteIds) {
            $invalid = Channel::create(['name' => 'Single invalid', 'slug' => 'single-invalid-'.uniqid()]);
            $invalid->websites()->sync($websiteIds);

            try {
                $resolver->resolve([], Channel::class, 'update', $invalid);
                $this->fail('Expected invalid omitted single-site binding.');
            } catch (RiskContextException $exception) {
                $this->assertSame('validation.failed', $exception->errorCode);
                $this->assertSame(422, $exception->httpStatus);
            }
        }
    }
}
