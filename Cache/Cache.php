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
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function test($key)
    {
        return $this->adapter->hasItem($key);
    }
    
    /**
     * Get an item
     * 
     * @param  string $key
     * @return mixed Data on success and false on failure
     * @throws \Zend\Cache\Exception
     */
    public function load($key)
    {
        $data = $this->adapter->getItem($key, $success);
        
        if($success && $this->automatic_serialization)
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
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function save($key, $value)
    {
        if($this->automatic_serialization)
        {
            $value = serialize($value);
        }
        
        return $this->adapter->setItem($key, $value);
    }
    
    /**
     * Remove an item
     * 
     * @param  string $key
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function remove($key)
    {
        return $this->adapter->removeItem($key);
    }
    
    /**
     * Flush the whole storage
     * 
     * @return boolean
     * @throws \Zend\Cache\Exception
     */
    public function flush()
    {
        return $this->adapter->flush();
    }
}