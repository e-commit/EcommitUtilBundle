<?php

/*
 * This file is part of the EcommitUtilBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\UtilBundle\Cache;

use Zend\Cache\StorageFactory;

class Cache
{
    /**
     * @var Zend\Cache\Storage\Adapter 
     */
    protected $adapter;
    
    protected $automatic_serialization = true;
    
    public function __construct($options = array())
    {
        if(isset($options['automatic_serialization']))
        {
            $this->automatic_serialization = $options['automatic_serialization'];
            unset($options['automatic_serialization']);
        }
        $this->adapter = StorageFactory::factory($options);
    }
    
    /**
     * Returns adapter
     * @return Zend\Cache\Storage\Adapter 
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
    
    /**
     * Test if an item exists
     * 
     * @param  string $key
     * @param  array $options
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function test($key, array $options = array())
    {
        return $this->adapter->hasItem($key, $options);
    }
    
    /**
     * Get an item
     * 
     * @param  string $key
     * @param  array $options
     * @return mixed Data on success and false on failure
     * @throws \Zend\Cache\Exception
     */
    public function load($key, array $options = array())
    {
        $data = $this->adapter->getItem($key, $options);
        
        if($this->automatic_serialization)
        {
            $data = @unserialize($data);
            
            if($data === false)
            {
                $this->remove($key);
            }
        }
        
        return $data;
    }
    
    /**
     * Store an item
     * Attention: false value is not supported!
     * 
     * @param  string $key
     * @param  mixed $value
     * @param  array $options
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function save($key, $value, array $options = array())
    {
        if($this->automatic_serialization)
        {
            $value = serialize($value);
        }
        
        return $this->adapter->setItem($key, $value, $options);
    }
    
    /**
     * Remove an item
     * 
     * @param  string $key
     * @param  array $options
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function remove($key, array $options = array())
    {
        return $this->adapter->removeItem($key, $options);
    }
    
    /**
     * Clear items off all namespaces
     * 
     * @param  int $mode Matching mode (Value of Adapter::MATCH_*)
     * @param  array $options
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function clean($mode = Zend\Cache\Storage\Adapter::MATCH_EXPIRED, array $options = array())
    {
        return $this->adapter->clear($mode, $options);
    }
    
    /**
     * Clear items by namespace
     * 
     * @param  int $mode Matching mode (Value of Adapter::MATCH_*)
     * @param  array $options
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function cleanByNamespace($mode = Zend\Cache\Storage\Adapter::MATCH_EXPIRED, array $options = array())
    {
        return $this->adapter->clearByNamespace($mode, $options);
    }
}