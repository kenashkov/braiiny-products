<?php

declare(strict_types=1);

namespace Kenashkov\Braiiny\Products\Models;


use Guzaba2\Base\Base;

class Products extends Base
{

    protected const CONFIG_DEFAULTS = [
        'services'      => [
            'Billy',//in future implementation the name of the service will be actually the interface name which the service implements like BillyDkInterface::class
        ]
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Retrieves all products from Billy
     * @return array
     */
    public static function get_from_billy(): array
    {
        $Billy = self::get_service('Billy');
    }
}