<?php
/**
 * An embedded document that may contain attributes. Unlike EMongoSoftDocument, if
 * the soft attribute value's is null, the attribute will not be persisted.
 * Additionally, soft attributes are predefined in the class, not dynamically added.
 *
 * PHP version 5.2+
 *
 * @author    Steven Hadfield <steven.hadfield@business.com>
 * @copyright 2014 Business.com Media Inc
 * @license   http://www.yiiframework.com/license/ BSD license
 * @version   1.4.0
 * @category  ext
 * @package   ext.YiiMongoDbSuite
 * @since     v1.4.0
 */

/**
 * EMongoSoftEmbeddedDocument class
 * @since v1.4.0
 */
abstract class EMongoSoftEmbeddedDocument extends EMongoEmbeddedDocument
{
    /**
     * Array that holds initialized soft attributes
     * @var array $softAttributes
     * @since v1.4.0
     */
    protected $softAttributes = array();

    /**
     * Adds soft attributes support to magic __get method
     * @see EMongoEmbeddedDocument::__get()
     * @since v1.4.0
     */
    public function __get($name)
    {
        if (array_key_exists($name, $this->softAttributes)) {
            return $this->softAttributes[$name];
        } else {
            return parent::__get($name);
        }
    }

    /**
     * Adds soft attributes support to magic __set method
     * @see EMongoEmbeddedDocument::__set()
     * @since v1.4.0
     */
    public function __set($name, $value)
    {
        if (array_key_exists($name, $this->softAttributes)) {
            $this->softAttributes[$name] = $value;
        } else {
            parent::__set($name, $value);
        }
    }

    /**
     * Adds soft attributes support to magic __isset method
     * @see EMongoEmbeddedDocument::__isset()
     * @since v1.4.0
     */
    public function __isset($name)
    {
        if (array_key_exists($name, $this->softAttributes)) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * Adds soft attributes support to magic __unset method
     * @see CComponent::__unset()
     * @since v1.4.0
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->softAttributes)) {
            unset($this->softAttributes[$name]);
        } else {
            parent::__unset($name);
        }
    }

    /**
     * Return the list of attribute names of this model, with respect of initialized soft attributes
     *
     * @see EMongoEmbeddedDocument::attributeNames()
     * @since v1.4.0
     */
    public function attributeNames()
    {
        return array_merge(
            array_keys($this->softAttributes), parent::attributeNames()
        );
    }

    /**
     * This method does the actual convertion to an array. Includes all non-null soft
     * attributes.
     * Does not fire any events
     *
     * @return array an associative array of the contents of this object
     * @since v1.4.0
     */
    protected function _toArray()
    {
        $arr = parent::_toArray();
        foreach ($this->softAttributes as $key => $value) {
            if (null !== $value) {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

    /**
     * Return the actual list of soft attributes being used by this model
     *
     * @return array list of initialized soft attributes
     * @since v1.4.0
     */
    public function getSoftAttributeNames()
    {
        return array_keys($this->softAttributes);
    }
}