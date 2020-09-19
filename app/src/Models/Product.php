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
        'main_table'                            => 'products',
        'route'                                 => '/admin/products',//this will use the ActiveRecordDefaultController for the CRUD operations
        //no dedicated controller is provided but thew generic one form Guzaba 2 is used

        'default_billy_organization_id'         => 'cwNMzNn1TOWhrYwyb6jdfA',//to be used if none is provided
        'default_billy_account_id'              => '4qAjMzZRRoO7sOAjzkorjw',
        'default_billy_sales_tax_ruleset_id'    => 'K5A89XDhQJeiyC9HtTX6Hw',

        'services'                              => [ //from the DI
            'Billy',
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
        if (!$this->product_billy_organization_id) {
            $this->product_billy_organization_id = self::CONFIG_RUNTIME['default_billy_organization_id'];
        }
        if (!$this->product_billy_account_id) {
            $this->product_billy_account_id = self::CONFIG_RUNTIME['default_billy_account_id'];
        }
        if (!$this->product_billy_sales_tax_ruleset_id) {
            $this->product_billy_sales_tax_ruleset_id = self::CONFIG_RUNTIME['default_billy_sales_tax_ruleset_id'];
        }

        $this->validate();//means there is no other product with the same name meaning that the transaction is expected to succeed (we already have a lock)

        /** @var BillyDk $Billy */
        $Billy = self::get_service('Billy');
        //if the below line does not throw an exception the product creation will continue
        $Response = $Billy->update_product($this);
        if ($this->is_new()) {
            $this->product_billy_id = $Response->products[0]->id;
        }
    }

    protected function _before_delete(): void
    {

        //TODO - validation of forreign keys pointing to this object are to be added here
        //first all such objects should be removed (or their FK keys changed) and only when it is ensured
        //that the deletion can complete successfully in the local DB delete at Billy
        //and then delete in the local DB
        //it is important to note that delete() invokes _before_delete() and _after_delete() and all these are in a
        //transaction, meaning that if the deletion at Billy fails the transaction will be rolled back and none of
        //the objects with FK to this one will be actually deleted (or updated)

        /** @var BillyDk $Billy */
        $Billy = self::get_service('Billy');
        //if the below line does not throw an exception the product deletion will continue
        $Response = $Billy->delete_product($this);
    }

    /**
     * It is needed to override the parent method in order to add an exclusive lock around the writing process.
     * The execution sequence is as follows:
     * 1. $this->write()
     * 2. acquire_lock()
     * 3. parent::write()
     * 4. $this->_before_write()
     * 5. validate()
     * 6. update_product() to Billy API
     * 7. the actual writing in DB occurs in parnet::write()
     * 8. _after_write() (not used)
     * 9. release lock (implicitly at the end of scope of $this->write())
     * @overrides
     * @return ActiveRecordInterface
     */
    public function write(): ActiveRecordInterface
    {
        //to guarantee that there is no record inserted between the validate() and the creation of the product at Billy (@see _before_write())
        //locking can be added here
        //otherwise it may happen the product to be added at Billy but to fail to be added here because there is already one with the same name (race condition)
        /** @var LockManagerInterface $LockManager */
        $LockManager = self::get_service('LockManager');
        //the acquire_lock method injects in the parent scope (by ref) a ScopeReference instance
        //the code execution will block at the acquire_lock line if there is another thread holding this lock
        //and when the other thread is finished the $this->validate() fail as there will already be an existing record with this product name
        $LockManager->acquire_lock('product:'.$this->product_name, LockInterface::LOCK_EX, $LOCK_REF);


        $ret = parent::write();//this will also execute _before_write() and _after_write() hooks and all that within one transaction

        //at the end of the scope the lock will be released automatically by the destructor of ScopeReference $LOCK_REF
        //no need of explicit release_lock() or unset($LOCK_REF)

        return $ret;
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
        return (bool) $this->product_is_archived;
    }


}