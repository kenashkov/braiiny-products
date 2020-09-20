<?php

declare(strict_types=1);

namespace Kenashkov\Braiiny\Products\Models;


use Guzaba2\Base\Base;
use Kenashkov\ErpApi\Interfaces\ErpInterface;

class Products extends Base
{

    protected const CONFIG_DEFAULTS = [
        'services'      => [
            ErpInterface::class,
        ],
        'import_batch_size' => 10,//per page
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * Retrieves all products from ERP and imports them locally
     * The import is done in batches (pages), not in one go.
     * @return array
     */
    public static function import_from_erp(): void
    {
        /** @var ErpInterface $Erp */
        $Erp = self::get_service(ErpInterface::class);
        $page = 1;
        $page_size = self::CONFIG_RUNTIME['import_batch_size'];
        
        $products = $Erp->get_products();

    }
}