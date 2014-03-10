<?php

/**
 * EMongoValidator verifies if the attribute is of the type specified by {@link type}.
 * Will also perform filtering if enabled.
 *
 * The following data types are supported:
 * <ul>
 * <li><b>id</b> A MongoId data type.</li>
 * <li><b>date</b> A MongoDate data type.</li>
 * </ul>
 *
 * @author      Steven Hadfield <steven.hadfield@business.com>
 * @copyright   2014 Business.com Media Inc
 * @license     http://www.yiiframework.com/license/ BSD license
 * @package     ext.YiiMongoDbSuite.extra
 * @since       v1.4.0
 */
class EMongoValidator extends CValidator
{
    /**
     * Validate for a MongoId object
     * @var string
     */
    const TYPE_OBJECT_ID = 'id';

    /**
     * Validate for a MongoDate object
     * @var string
     */
    const TYPE_DATE = 'date';

    /**
     * The data type that the attribute should be. Defaults to TYPE_OBJECT_ID.
     * Valid values include TYPE_OBJECT_ID, TYPE_DATE.
     * @var string
     */
    public $type = self::TYPE_OBJECT_ID;

    /**
     * Whether the attribute value can be null or empty. Defaults to true.
     * @var boolean
     */
    public $allowEmpty = true;

    /**
     * If the value can be converted to a MongoDB object type, force the value to be
     * an object. Defaults to true
     * @var boolean
     */
    public $filter = true;

    /**
     * Ensure the attribute is a Mongo object. If filtering is enabled and the value
     * can be converted to a Mongo object, the value will be updated in the object.
     *
     * @param CModel $object    Model to be validated
     * @param string $attribute Attribute name to be validated
     *
     * @throws CException If an unknown type is specified
     */
    protected function validateAttribute($object, $attribute)
    {
        $msg = false;

        $value = $object->{$attribute};
        if (empty($value)) {
            if (!$this->allowEmpty) {
                $msg = '{attribute} may not be empty.';
            }
        } else {
            switch ($this->type) {
                case self::TYPE_OBJECT_ID:
                    if ($this->filter && is_string($value) && 24 === strlen($value)
                        && preg_match('/^[a-fA-F0-9]{24}$/', $value)
                    ) {
                        $value = new MongoId($value);
                    }

                    if (!$value instanceof MongoId) {
                        $msg = '{attribute} must be an instance of MongoId.';
                    }
                    break;
                case self::TYPE_DATE:
                    if ($this->filter && is_numeric($value)) {
                        $value = new MongoDate($value);
                    } elseif ($this->filter && $value instanceof DateTime) {
                        $value = new MongoDate($value->getTimestamp());
                    } elseif ($this->filter && false !== strtotime($value)) {
                        // If able to parse date, use as a last result
                        $value = new MongoDate(strtotime($value));
                    }

                    if (!$value instanceof MongoDate) {
                        $msg = '{attribute} must be an instance of MongoDate.';
                    }
                    break;
                default:
                    throw new CException('Invalid validation type: ' . $this->type);
                    break;
            }
        }

        if (false !== $msg) {
            $message = $this->message !== null
                ? $this->message : Yii::t('yii', $msg);
            $this->addError($object, $attribute, $message);
        } elseif ($this->filter && $object->{$attribute} !== $value) {
            // If filter is enabled, make sure the object is updated
            $object->{$attribute} = $value;
        }
    }

    /**
     * Shortcut to test if a value is a MongoId
     *
     * @param MongoId|string $id       Value to check
     * @param boolean        $instance Check whether the variable is already an
     *                                 instance of MongoId
     *
     * @return boolean true if is an instance of a MongoId or could be the string
     *                      equivalent
     * @since v1.4.0
     */
    public static function isMongoId($id, $instance = true)
    {
        return ($instance && $id instanceof MongoId)
            || (is_scalar($id) && preg_match('/^[a-fA-F0-9]{24}$/', $id));
    }
}
