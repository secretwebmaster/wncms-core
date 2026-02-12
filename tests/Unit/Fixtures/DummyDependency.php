<?php

namespace Wncms\Tests\Unit\Fixtures;

class DummyDependency
{
    public function __construct(public string $value = 'injected')
    {
    }
}
