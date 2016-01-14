<?php

/**
 * String length adapter for rich text strings, i.e. text with HTML markup.
 */
class Maniple_StringUtils_StringLength_RichText extends Maniple_StringUtils_StringLength_Standard
{
    /**
     * @var Zend_Filter_StripTags
     */
    protected $_filter;

    /**
     * @param Zend_Filter_StripTags $filter
     * @return $this
     */
    public function setFilter($filter)
    {
        $this->_filter = $filter;
        return $this;
    }

    /**
     * @return Zend_Filter_StripTags
     */
    public function getFilter()
    {
        if ($this->_filter === null) {
            $this->_filter = new Zend_Filter_StripTags();
        }
        return $this->_filter;
    }

    /**
     * @param $string
     * @return int
     */
    public function getLength($string)
    {
        // string is cleared out of all HTML tags, entities are converted to
        // corresponding chars, consecutive white spaces are merged, and whole
        // string is trimmed.
        $string = $this->getFilter()->filter($string);
        $string = html_entity_decode($string);
        $string = preg_replace('/\s+/', ' ', $string);
        $string = trim($string);

        return parent::getLength($string);
    }
}
