<?php

declare(strict_types=1);

namespace Kenashkov\Braiiny\Products\Models;

use Guzaba2\Authorization\Exceptions\PermissionDeniedException;
use Guzaba2\Orm\Exceptions\RecordNotFoundException;
use Guzaba2\Orm\Interfaces\ValidationFailedExceptionInterface;
use GuzabaPlatform\Platform\Application\BaseActiveRecord;
use Kenashkov\BillyDk\Interfaces\ProductInterface;
use Kenashkov\BillyDk\Traits\ProductTrait;

/**
 * Class Product
 * @package Kenashkov\Braiiny\Products\Models
 *
 * Overloaded properties based on the database structure:
 * @property int            product_id
 * @property string         product_billy_id
 * @property string         product_name
 * @property string|null    product_organization_id FK to organization in the application (not implemented thus always null)
 * @property string         product_billy_organization_id Billy specific FK
 * @property string         product_description
 * @property string         product_billy_account_id Billy specific FK
 * @property string         product_number
 * @property string         product_suppliers_number
 * @property string         product_billy_sales_tax_ruleset_id
 * @property bool           product_is_archived
 */
class Product extends BaseActiveRecord implements ProductInterface
{
    use ProductTrait;

    protected const CONFIG_DEFAULTS = [
        'main_table'            => 'products',
        'route'                 => '/admin/products',
        'services'              => [ //from the DI
            'Billy'
        ],
        'validation'                => [
            'product_name'                 => [
                'required'              => true,
                'max_length'            => 200,
            ],
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    protected function _validate_proruct_name(): ?ValidationFailedExceptionInterface
    {
        try {
            $AnotherProduct = new static( ['product_name' => $this->product_name] );
            return new ValidationFailedException($this, 'product_name', sprintf(t::_('There is already a product with the given product_name "%1$s".'), $this->product_name));
        } catch (RecordNotFoundException $Exception) {
            //it is OK... there is no other product with this name
        } catch (PermissionDeniedException $Exception) {
            return new ValidationFailedException($this, 'product_name', sprintf(t::_('There is already a product with the given product_name "%1$s".'), $this->product_name));
        }
    }

    protected function _before_write(): void
    {

        $this->validate();

        /** @var BillyDk $Billy */
        $Billy = self::get_service('Billy');

        $Response = $Billy->update_product($this);
    }

    /**
     * @implements ProductInterface
     * @return string
     */
    public function get_billy_id(): string
    {
       return $this->product_billy_id;
    }


    public function get_billy_organization(): string
    {
        return $this->product_billy_organization_id;
    }

    public function get_billy_name(): string
    {
        return $this->product_name;
    }

    public function get_billy_description(): string
    {
        return $this->product_description;
    }

    public function get_billy_account(): string
    {
        return $this->product_billy_account_id;
    }

    public function get_billy_product_number(): string
    {
        return $this->product_number;
    }

    public function get_billy_suppliers_product_number(): string
    {
        return $this->product_suppliers_number;
    }

    public function get_billy_sales_tax_ruleset(): string
    {
        return $this->product_billy_sales_tax_ruleset_id;
    }

    public function get_billy_is_archived(): bool
    {
        return $this->product_is_archived;
    }


}