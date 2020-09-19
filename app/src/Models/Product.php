<?php

declare(strict_types=1);

namespace Kenashkov\Braiiny\Products\Models;

use GuzabaPlatform\Platform\Application\BaseActiveRecord;
use Kenashkov\BillyDk\ProductInterface;
use Kenashkov\BillyDk\Traits\ProductTrait;

/**
 * Class Product
 * @package Kenashkov\Braiiny\Products\Models
 *
 * Overloaded properties based on the database structure:
 * @property int            product_id
 * @property string         product_name
 * @property string|null    product_organization_id FK to organization in the application (not implemented thus always null)
 * @property string         product_billy_organization_id Billy specific FK
 * @property string         product_description
 * @property string         product_billy_account_id Billy specific FK
 * @property string         product_number
 * @property string         product_suppliers_number
 * @property string         product_billy_sales_tax_ruleset_id
 * @property string         product_is_archived
 */
class Product extends BaseActiveRecord implements ProductInterface
{
    use ProductTrait;

    public function get_organization(): string
    {
        return $this->product_billy_organization_id;
    }

    public function get_name(): string
    {
        return $this->product_name;
    }

    public function get_description(): string
    {
        return $this->product_description;
    }

    public function get_account(): string
    {
        return $this->product_billy_account_id;
    }

    public function get_product_number(): string
    {
        return $this->product_number;
    }

    public function get_suppliers_product_number(): string
    {
        return $this->product_suppliers_number;
    }

    public function get_sales_tax_ruleset(): string
    {
        return $this->product_billy_sales_tax_ruleset_id;
    }

    public function get_is_archived(): bool
    {
        return $this->product_is_archived;
    }
}