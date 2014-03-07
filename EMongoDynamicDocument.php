<?php

/**
 * An EMongoDocument that supports polymorphic embedded documents.
 *
 * @since v1.4.1
 */
abstract class EMongoDynamicDocument extends EMongoDocument
{
    /**
     * Ensure dynamic embedded documents are correctly configured
     */
    protected function initEmbeddedDocuments()
    {
        if (!$this->hasEmbeddedDocuments() || !$this->beforeEmbeddedDocsInit()) {
            return false;
        }

        $this->_embedded = new CMap;

        $this->afterEmbeddedDocsInit();
    }

    /**
     * Check to see if an embedded document has been defined with the given attribute name.
     *
     * @param string $name Embedded document attribute name
     *
     * @return boolean Whether an embedded document is defined with the given name.
     */
    public function hasEmbeddedDocument($name)
    {
        $docs = $this->embeddedDocuments();

        // If an embedded document has been defined since the intialization,
        // call initEmbeddedDocuments
        if ($docs && null === $this->_embedded) {
            $this->initEmbeddedDocuments();
        }

        return isset($docs[$name]);
    }

    /**
     * Determine if this model has defined embedded documents.
     *
     * @see embeddedDocuments()
     * @return boolean Whether any embedded documents are configured for this model.
     */
    public function hasEmbeddedDocuments()
    {
        return count($this->embeddedDocuments()) > 0;
    }
}
