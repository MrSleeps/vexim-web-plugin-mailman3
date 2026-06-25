<?php

namespace VEximweb\Plugin\VEximMailman3\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \VEximweb\Plugin\VEximMailman3\VEximMailman3
 */
class VEximMailman3 extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \VEximweb\Plugin\VEximMailman3\VEximMailman3::class;
    }
}
