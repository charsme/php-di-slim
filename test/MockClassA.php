<?php

namespace Resilient\Test;

class MockClassA
{
    public function sayHi()
    {
        return self::class;
    }
}
