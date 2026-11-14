<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use \AnourValar\EloquentValidation\Tests\ValidationTrait;
    use \Tests\BusinessTrait;
}
