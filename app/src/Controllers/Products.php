<?php

declare(strict_types=1);

namespace Kenashkov\Braiiny\Products\Controllers;


use Guzaba2\Base\Exceptions\NotImplementedException;
use Guzaba2\Http\Method;
use Guzaba2\Translator\Translator as t;
use GuzabaPlatform\Platform\Application\BaseController;
use Psr\Http\Message\ResponseInterface;

class Products extends BaseController
{

    protected const CONFIG_DEFAULTS = [
        'routes'        => [
            '/admin/products/push-to-billy'     => [
                Method::HTTP_POST                   => [self::class, 'push_to_billy']
            ],
            '/admin/products/get-from-billy'    => [
                Method::HTTP_POST                   => [self::class, 'get_from_billy']
            ],
        ]
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Pushes the current products to billy.
     * Considering that any change in this system is immediately reflected at Billy this method will just delete the
     * products at Billy that do not exist in this system.
     * Since there are currently pre-existing products there this method is not implemented
     * @return ResponseInterface
     */
    public function push_to_billy(): ResponseInterface
    {
        $message = sprintf(t::_('Pushing to Billy is not implemented as this will delete products at Billy.'));
        throw new NotImplementedException($message);
    }

    /**
     * Retrieves all products from billy and imports the unknown ones, updates the known ones and deletes any local
     * product that is not found at Billy (this may happen if the product was deleted at billy manually, not thorugh
     * this app).
     * @return ResponseInterface
     */
    public function get_from_billy(): ResponseInterface
    {
        //Pro
    }
}