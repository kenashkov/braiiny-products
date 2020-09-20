<?php

declare(strict_types=1);

namespace Kenashkov\Braiiny\Products\Models;


use Guzaba2\Base\Base;
use Guzaba2\Orm\Exceptions\RecordNotFoundException;
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
    public static function import_from_erp(): int
    {
        /** @var ErpInterface $Erp */
        $Erp = self::get_service(ErpInterface::class);
        $page = 1;
        $page_size = self::CONFIG_RUNTIME['import_batch_size'];
        $imported_products = 0;
        do {
            $erp_products = $Erp->get_products($page, $page_size);
            $page++;
            foreach ($erp_products as $ErpProduct) {

                //check is there already a product with this name (there could be duplicates coming from the API (different Orgnaization for example)
                //the products with the same name are NOT imported
                //TODO - add support for ogranizations in the app
                try {
                    $Product = new Product( ['product_name' => $ErpProduct->get_erp_name()] );
                    continue;//duplicate
                } catch (RecordNotFoundException $Exception) {
                    //OK, proceed
                }
                try {
                    $Product = new Product( ['product_erp_id' => $ErpProduct->get_erp_id()] );
                } catch (RecordNotFoundException $Exception) {
                    $Product = new Product();
                    foreach ($Product::METHOD_PROPERTY_MAP as $method => $property) {
                        $Product->{$property} = $ErpProduct->{$method}();
                    }
                    //$Product->product_erp_id = $ErpProduct->get_erp_id();
                    //$Product->product_erp_organization_id = $ErpProduct->get_erp_organization();
                    //$Product->product_name = $ErpProduct->get_erp_name();
                    //$Product->product_description = $ErpProduct->get_erp_description();
                    //...
                    $Product->write();
                    $imported_products++;
                } //the permissions are not enforced in this app
            }
            if (count($erp_products) < $page_size) {
                break;
            }
        } while (true);
        return $imported_products;
    }
}