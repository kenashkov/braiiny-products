<?php

declare(strict_types=1);

namespace Kenashkov\Braiiny\Products\Models;

use Azonmedia\Lock\Interfaces\LockInterface;
use Azonmedia\Lock\Interfaces\LockManagerInterface;
use Guzaba2\Authorization\Exceptions\PermissionDeniedException;
use Guzaba2\Orm\Exceptions\RecordNotFoundException;
use Guzaba2\Orm\Interfaces\ActiveRecordInterface;
use Guzaba2\Orm\Interfaces\ValidationFailedExceptionInterface;
use GuzabaPlatform\Platform\Application\BaseActiveRecord;
use Kenashkov\BillyDk\Interfaces\ProductInterface;
use Kenashkov\BillyDk\Traits\ProductTrait;
use Kenashkov\ErpApi\Interfaces\ErpInterface;

/**
 * Class Product
 * @package Kenashkov\Braiiny\Products\Models
 *
 * Overloaded properties based on the database structure:
 * @property int            product_id
 * @property string         product_erp_id
 * @property string         product_name
 * @property string|null    product_organization_id FK to organization in the application (not implemented thus always null)
 * @property string         product_erp_organization_id Billy specific FK
 * @property string         product_description
 * @property string         product_erp_account_id Billy specific FK
 * @property string         product_number
 * @property string         product_suppliers_number
 * @property string         product_erp_sales_tax_ruleset_id
 * @property bool           product_is_archived
 */
class Product extends BaseActiveRecord implements ProductInterface
{
    use ProductTrait;

    protected const CONFIG_DEFAULTS = [
        'main_table'                            => 'products',
        'route'                                 => '/admin/products',//this will use the ActiveRecordDefaultController for the CRUD operations
        //no dedicated controller is provided but thew generic one form Guzaba 2 is used

        'default_erp_organization_id'           => 'cwNMzNn1TOWhrYwyb6jdfA',//to be used if none is provided
        'default_erp_account_id'                => '4qAjMzZRRoO7sOAjzkorjw',
        'default_erp_sales_tax_ruleset_id'      => 'K5A89XDhQJeiyC9HtTX6Hw',

        'services'                              => [ //from the DI
            //'Erp',
            ErpInterface::class,
            'LockManager',
        ],
        'validation'                            => [
            'product_name'                          => [
                'required'                              => true,
                'max_length'                            => 200,
            ],
            //more validations can be added here
        ],
    ];

    protected const CONFIG_RUNTIME = [];

    /**
     * A mapping between the methods ofthe object and the corresponding properties of this object.
     */
    public const METHOD_PROPERTY_MAP = [
        'get_erp_id'                          => 'product_erp_id',
        'get_erp_organization'                => 'product_erp_organization_id',
        'get_erp_name'                        => 'product_name',
        'get_erp_description'                 => 'product_description',
        'get_erp_account'                     => 'product_erp_account_id',
        'get_erp_product_number'              => 'product_number',
        'get_erp_suppliers_product_number'    => 'product_suppliers_number',
        'get_erp_sales_tax_ruleset'           => 'product_erp_sales_tax_ruleset_id',
        'get_erp_is_archived'                 => 'product_is_archived',
    ];

    /**
     * Enforces unique product name (product_name)
     * The hook is called by parent::validate()
     * @return ValidationFailedExceptionInterface|null
     * @throws \Azonmedia\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\LogicException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Coroutine\Exceptions\ContextDestroyedException
     * @throws \Guzaba2\Kernel\Exceptions\ConfigurationException
     * @throws \ReflectionException
     */
    protected function _validate_proruct_name(): ?ValidationFailedExceptionInterface
    {
        $ret = null;
        if ($this->is_new() || $this->is_property_modified('product_name')) {
            try {
                $AnotherProduct = new static(['product_name' => $this->product_name]);
                $ret = new ValidationFailedException($this, 'product_name', sprintf(t::_('There is already a product with the given product_name "%1$s".'), $this->product_name));
            } catch (RecordNotFoundException $Exception) {
                //it is OK... there is no other product with this name
            } catch (PermissionDeniedException $Exception) {
                $ret = new ValidationFailedException($this, 'product_name', sprintf(t::_('There is already a product with the given product_name "%1$s".'), $this->product_name));
            }
        }
        return $ret;
    }

    //more validation hooks like _validate_product_number() etc can be added here

    /**
     * will be executed by parent::write()
     * @throws \Guzaba2\Base\Exceptions\InvalidArgumentException
     * @throws \Guzaba2\Base\Exceptions\RunTimeException
     * @throws \Guzaba2\Orm\Exceptions\MultipleValidationFailedException
     * @throws \ReflectionException
     */
    protected function _before_write(): void
    {
        //set some defaults if not set
        if (!$this->product_erp_organization_id) {
            $this->product_erp_organization_id = self::CONFIG_RUNTIME['default_erp_organization_id'];
        }
        if (!$this->product_erp_account_id) {
            $this->product_erp_account_id = self::CONFIG_RUNTIME['default_erp_account_id'];
        }
        if (!$this->product_erp_sales_tax_ruleset_id) {
            $this->product_erp_sales_tax_ruleset_id = self::CONFIG_RUNTIME['default_erp_sales_tax_ruleset_id'];
        }

        $this->validate();//means there is no other product with the same name meaning that the transaction is expected to succeed (we already have a lock)

        /** @var ErpInterface $Erp */
        $Erp = self::get_service(ErpInterface::class);
        //if the below line does not throw an exception the product creation will continue
        $erp_id = $Erp->update_product($this);
        if ($this->is_new()) {
            $this->product_erp_id = $erp_id;
        }
    }

    protected function _before_delete(): void
    {

        //TODO - validation of forreign keys pointing to this object are to be added here
        //first all such objects should be removed (or their FK keys changed) and only when it is ensured
        //that the deletion can complete successfully in the local DB delete at ERP
        //and then delete in the local DB
        //it is important to note that delete() invokes _before_delete() and _after_delete() and all these are in a
        //transaction, meaning that if the deletion at ERP fails the transaction will be rolled back and none of
        //the objects with FK to this one will be actually deleted (or updated)

        /** @var ErpInterface $Erp */
        $Erp = self::get_service(ErpInterface::class);
        //if the below line does not throw an exception the product deletion will continue
        $Erp->delete_product($this);
    }

    /**
     * It is needed to override the parent method in order to add an exclusive lock around the writing process.
     * The execution sequence is as follows:
     * 1. $this->write()
     * 2. acquire_lock()
     * 3. parent::write()
     * 4. $this->_before_write()
     * 5. validate()
     * 6. update_product() to ERP API
     * 7. the actual writing in DB occurs in parnet::write()
     * 8. _after_write() (not used)
     * 9. release lock (implicitly at the end of scope of $this->write())
     * @overrides
     * @return ActiveRecordInterface
     */
    public function write(): ActiveRecordInterface
    {
        //to guarantee that there is no record inserted between the validate() and the creation of the product at ERP (@see _before_write())
        //locking can be added here
        //otherwise it may happen the product to be added at ERP but to fail to be added here because there is already one with the same name (race condition)
        /** @var LockManagerInterface $LockManager */
        $LockManager = self::get_service('LockManager');
        //the acquire_lock method injects in the parent scope (by ref) a ScopeReference instance
        //the code execution will block at the acquire_lock line if there is another thread holding this lock
        //and when the other thread is finished the $this->validate() fail as there will already be an existing record with this product name
        //$LockManager->acquire_lock('product:'.$this->product_name, LockInterface::LOCK_EX, $LOCK_REF);
        //the product name may be too long for a lock key so lets md5() it
        $LockManager->acquire_lock('product:'.md5($this->product_name), LockInterface::LOCK_EX, $LOCK_REF);


        $ret = parent::write();//this will also execute _before_write() and _after_write() hooks and all that within one transaction

        //at the end of the scope the lock will be released automatically by the destructor of ScopeReference $LOCK_REF
        //no need of explicit release_lock() or unset($LOCK_REF)

        return $ret;
    }

    /**
     * @implements ProductInterface
     * @return string
     */
    public function get_erp_id(): string
    {
        //return $this->{self::METHOD_PROPERTY_MAP[__FUNCTION__]};//alternative
        return $this->product_erp_id;
    }


    public function get_erp_organization(): string
    {
        return $this->product_erp_organization_id;
    }

    public function get_erp_name(): string
    {
        return $this->product_name;
    }

    public function get_erp_description(): string
    {
        return $this->product_description;
    }

    public function get_erp_account(): string
    {
        return $this->product_erp_account_id;
    }

    public function get_erp_product_number(): string
    {
        return $this->product_number;
    }

    public function get_erp_suppliers_product_number(): string
    {
        return $this->product_suppliers_number;
    }

    public function get_erp_sales_tax_ruleset(): string
    {
        return $this->product_erp_sales_tax_ruleset_id;
    }

    public function get_erp_is_archived(): bool
    {
        return (bool) $this->product_is_archived;
    }


}