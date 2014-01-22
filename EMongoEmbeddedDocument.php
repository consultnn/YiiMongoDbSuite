<?php
/**
 * EMongoEmbeddedDocument.php
 *
 * PHP version 5.2+
 *
 * @author		Dariusz Górecki <darek.krk@gmail.com>
 * @author		Invenzzia Group, open-source division of CleverIT company http://www.invenzzia.org
 * @copyright	2011 CleverIT http://www.cleverit.com.pl
 * @license		http://www.yiiframework.com/license/ BSD license
 * @version		1.3
 * @category	ext
 * @package		ext.YiiMongoDbSuite
 * @since		v1.0.8
 */

/**
 * @since v1.0.8
 */
abstract class EMongoEmbeddedDocument extends CModel
{
	private static $_attributes=array();

	/**
	 * CMap of embedded documents
	 * @var CMap $_embedded
	 * @since v1.0.8
	 */
	protected $_embedded=null;

	/**
	 * Cached values for embeddedDocuments() method
	 * @var array $_embeddedConfig
	 * @since v1.3.2
	 */
	protected static $_embeddedConfig = array();

	/**
	 * Hold down owner pointer (if any)
	 *
	 * @var EMongoEmbeddedDocument $_owner
	 * @since v1.0.8
	 */
	protected $_owner=null;

	/**
	 * Constructor.
	 * @param string $scenario name of the scenario that this model is used in.
	 * See {@link CModel::scenario} on how scenario is used by models.
	 * @see getScenario
	 * @since v1.0.8
	 */
	public function __construct($scenario='insert')
	{
		$this->setScenario($scenario);
		$this->init();
		$this->attachBehaviors($this->behaviors());
		$this->afterConstruct();

		$this->initEmbeddedDocuments();
	}

	/**
	 * Initializes this model.
	 * This method is invoked in the constructor right after {@link scenario} is set.
	 * You may override this method to provide code that is needed to initialize the model (e.g. setting
	 * initial property values.)
	 * @since 1.0.8
	 */
	public function init(){}

	/**
	 * @since v1.0.8
	 */
	protected function initEmbeddedDocuments()
	{
		if(!$this->hasEmbeddedDocuments() || !$this->beforeEmbeddedDocsInit())
			return false;

		$this->_embedded = new CMap;
		if(!isset(self::$_embeddedConfig[get_class($this)]))
			self::$_embeddedConfig[get_class($this)] = $this->embeddedDocuments();
		$this->afterEmbeddedDocsInit();
	}

	/**
	 * @since v1.0.8
	 */
	public function onBeforeEmbeddedDocsInit($event)
	{
		$this->raiseEvent('onBeforeEmbeddedDocsInit', $event);
	}

	/**
	 * @since v1.0.8
	 */
	public function onAfterEmbeddedDocsInit($event)
	{
		$this->raiseEvent('onAfterEmbeddedDocsInit', $event);
	}

	/**
	 * @since v1.0.8
	 */
	public function onBeforeToArray($event)
	{
		$this->raiseEvent('onBeforeToArray', $event);
	}

	/**
	 * @since v1.0.8
	 */
	public function onAfterToArray($event)
	{
		$this->raiseEvent('onAfterToArray', $event);
	}

    /**
     * Method to determine whether an array conversion should be allowed to continue
     *
     * @since v1.0.8
     * @return boolean Whether the array conversion should continue.
     */
    protected function beforeToArray()
    {
        $event = new CModelEvent($this);
        $this->onBeforeToArray($event);
        return $event->isValid;
    }

	/**
	 * @since v1.0.8
	 */
	protected function afterToArray()
	{
		$this->onAfterToArray(new CModelEvent($this));
	}

    /**
     * Method to determine whether a call to initialize embedded documents should be
     * allowed to continue.
     *
     * @since v1.0.8
     * @return boolean Whether the embeddedDocsInit event should be preformed
     */
    protected function beforeEmbeddedDocsInit()
    {
        $event = new CModelEvent($this);
        $this->onBeforeEmbeddedDocsInit($event);
        return $event->isValid;
    }

	/**
	 * @since v1.0.8
	 */
	protected function afterEmbeddedDocsInit()
	{
		$this->onAfterEmbeddedDocsInit(new CModelEvent($this));
	}

	/**
	 * @since v1.0.8
	 */
	public function __get($name)
	{
		if($this->hasEmbeddedDocuments() && isset(self::$_embeddedConfig[get_class($this)][$name])) {
			// Late creation of embedded documents on first access
			if (is_null($this->_embedded->itemAt($name))) {
				$docClassName = self::$_embeddedConfig[get_class($this)][$name];
				$doc = new $docClassName($this->getScenario());
				$doc->setOwner($this);
				$this->_embedded->add($name, $doc);
			}
			return $this->_embedded->itemAt($name);
		}
		else
			return parent::__get($name);
	}

	/**
	 * @since v1.0.8
	 */
	public function __set($name, $value)
	{
		if($this->hasEmbeddedDocuments() && isset(self::$_embeddedConfig[get_class($this)][$name]))
		{
			if(is_array($value)) {
				// Late creation of embedded documents on first access
				if (is_null($this->_embedded->itemAt($name))) {
					$docClassName = self::$_embeddedConfig[get_class($this)][$name];
					$doc = new $docClassName($this->getScenario());
					$doc->setOwner($this);
					$this->_embedded->add($name, $doc);
				}
				return $this->_embedded->itemAt($name)->attributes=$value;
			}
			else if($value instanceof EMongoEmbeddedDocument)
				return $this->_embedded->add($name, $value);
		}
		else
			parent::__set($name, $value);
	}

	/**
	 * @since v1.3.2
	 * @see CComponent::__isset()
	 */
	public function __isset($name) {
		if($this->hasEmbeddedDocuments() && isset(self::$_embeddedConfig[get_class($this)][$name]))
		{
			return isset($this->_embedded[$name]);
		}
		else
			return parent::__isset($name);
	}

	/**
	 * @since v1.0.8
	 */
	public function afterValidate()
	{
		if($this->hasEmbeddedDocuments())
			foreach($this->_embedded as $doc)
			{
				if(!$doc->validate())
				{
					$this->addErrors($doc->getErrors());
				}
			}
	}

    /**
     * Embedded document definitions. Defined as an array of name to class mapping.
     *
     * @example array('property' => 'EMongoEmbeddedDocumentClass')
     * @since v1.0.8
     * @return array
     */
    public function embeddedDocuments()
    {
        return array();
    }

    /**
     * Determine if this mjodel as defined embedded documents.
     *
     * @since v1.0.8
     * @see embeddedDocuments()
     * @return boolean Whether any embedded documents are configured for this model.
     */
    public function hasEmbeddedDocuments()
    {
        if (isset(self::$_embeddedConfig[get_class($this)])) {
            return true;
        }

        return count($this->embeddedDocuments()) > 0;
    }

    /**
     * Returns the list of attribute names.
     * By default, this method returns all public properties of the class and any
     * configured embedded documents.
     * You may override this method to change the default.
     *
     * @return array list of attribute names. Defaults to all public properties of the class.
     * @since v1.0.8
     */
    public function attributeNames()
    {
        $className = get_class($this);
        if (!isset(self::$_attributes[$className])) {
            $class = new ReflectionClass($className);
            $names = array();
            foreach ($class->getProperties() as $property) {
                $name = $property->getName();
                if ($property->isPublic() && !$property->isStatic()) {
                    $names[] = $name;
                }
            }
            if ($this->hasEmbeddedDocuments()) {
                $names = array_merge(
                    $names, array_keys(self::$_embeddedConfig[$className])
                );
            }
            self::$_attributes[$className] = $names;
        }

        return self::$_attributes[$className];
    }

	/**
	 * Returns the given object as an associative array
	 * Fires beforeToArray and afterToArray events
	 * @return array an associative array of the contents of this object
	 * @since v1.0.8
	 */
	public function toArray()
	{
		if($this->beforeToArray())
		{
			$arr = $this->_toArray();
			$this->afterToArray();
			return $arr;
		}
		else
			return array();
	}

    /**
     * This method does the actual convertion to an array.
     * Includes all defined attributes in {@link attributeNames()}. If an embedded
     * document has not been loaded, it will have a return value of null.
     * Does not fire any events.
     *
     * @return array an associative array of the contents of this object
     * @since v1.3.4
     */
    protected function _toArray()
    {
        $arr = array();
        $embeddedDocs = $this->embeddedDocuments();
        foreach ($this->attributeNames() as $name) {
            if (isset($embeddedDocs[$name])) {
                // Only populate embedded document if not null
                if (null !== $this->_embedded->itemAt($name)) {
                    $arr[$name] = $this->_embedded[$name]->toArray();
                } else {
                    $arr[$name] = null;
                }
            } else {
                $arr[$name] = $this->{$name};
            }
        }

        return $arr;
    }

    /**
     * Return owner of this document if one has been set
     *
     * @return EMongoEmbeddedDocument|null Owner object
     * @since v1.0.8
     */
    public function getOwner()
    {
        return $this->_owner;
    }

    /**
     * Set owner of this document
     *
     * @param EMongoEmbeddedDocument $owner
     * @since v1.0.8
     */
    public function setOwner(EMongoEmbeddedDocument $owner)
    {
        $this->_owner = $owner;
    }

	/**
	 * Override default seScenario method for populating to embedded records
	 * @see CModel::setScenario()
	 * @since v1.0.8
	 */
	public function setScenario($value)
	{
		if($this->hasEmbeddedDocuments() && $this->_embedded !== null)
		{
			foreach($this->_embedded as $doc)
				$doc->setScenario($value);
		}
		parent::setScenario($value);
	}

    /**
     * Validate current document and all embedded documents.
     *
     * @param array|null $attributes  Attributes to validate, or null for all
     * @param boolean    $clearErrors Whether any previous errors should be cleared
     *                                before performing validation.
     *
     * @return boolean Whether the model is considered valid for the given parameters
     */
    public function validate($attributes = null, $clearErrors = true)
    {
        $valid = parent::validate($attributes, $clearErrors);
        if ($this->hasEmbeddedDocuments()) {
            foreach (array_keys($this->embeddedDocuments()) as $attribute) {
                if (null !== $this->_embedded->itemAt($attribute)
                    && (null === $attributes || in_array($attribute, $attributes))
                ) {
                    $valid &= $this->$attribute->validate(null, $clearErrors);
                    // Populate error message with embedded document messages
                    if ($this->$attribute->hasErrors()) {
                        $this->addErrors($this->$attribute->getErrors());
                    }
                }
            }
        }

        return $valid;
    }

    /**
     * Override parent getAttributes to ensure embedded documents without values are
     * not created
     *
     * @param array|null $names Attribute names to include. (default null for all)
     *
     * @return array
     * @see CModel::getAttributes()
     */
    public function getAttributes($names = null)
    {
        $values = array();
        $embeddedDocs = $this->embeddedDocuments();
        foreach ($this->attributeNames() as $name) {
            // Check if attribute is an embedded document and whether it has a value
            // or not
            if (! array_key_exists($name, $embeddedDocs)
                || (array_key_exists($name, $embeddedDocs)
                && null !== $this->_embedded->itemAt($name))
            ) {
                $values[$name] = $this->$name;
            } else {
                $values[$name] = null;
            }
        }

        if (is_array($names)) {
            $values2 = array();
            foreach ($names as $name) {
                $values2[$name] = isset($values[$name]) ? $values[$name] : null;
            }

            return $values2;
        } else {
            return $values;
        }
    }

    /**
     * When de-serializing the document, ensure static variables are setup (embedded
     * documents) and the model is initialized.
     */
    public function __wakeup()
    {
        // Ensure model setup
        $this->init();

        // Ensure the embedded document configuration is setup for this class
        if (! isset(self::$_embeddedConfig[get_class($this)])) {
            self::$_embeddedConfig[get_class($this)] = $this->embeddedDocuments();
        }
    }
}
