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
            '/admin/products/export-to-erp'       => [
                Method::HTTP_POST                       => [self::class, 'export_to_erp']
            ],
            '/admin/products/import-from-erp'     => [
                Method::HTTP_POST                       => [self::class, 'import_from_erp']
            ],
        ]
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Pushes the current products to ERP.
     * Considering that any change in this system is immediately reflected at ERP this method will just delete the
     * products at ERP that do not exist in this system.
     * Since there are currently pre-existing products there this method is not implemented
     * @return ResponseInterface
     */
    public function export_to_erp(): ResponseInterface
    {
        $message = sprintf(t::_('Pushing to ERP is not implemented as this will delete products at ERP.'));
        throw new NotImplementedException($message);
    }

    /**
     * Retrieves all products from ERP and imports the unknown ones, updates the known ones.
     * Does not delete products found locally but not found in the ERP.
     * @return ResponseInterface
     */
    public function import_from_erp(): ResponseInterface
    {
        $imported_products = \Kenashkov\Braiiny\Products\Models\Products::import_from_erp();

        $struct = [
            'imported_products' => $imported_products,
            'message'           => sprintf(t::_('%1$s products were imported from the ERP.'), $imported_products),
        ];
        return self::get_structured_ok_response($struct);
    }
}