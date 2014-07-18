<?php

class ManipleCore_Search_Lucene_Index implements ManipleCore_Search_IndexInterface
{
    /**
     * @var string
     */
    protected $_encoding = 'utf-8';

    /**
     * @var Zend_Search_Lucene_Interface
     */
    protected $_lucene;

    public function __construct(Zend_Search_Lucene_Interface $lucene)
    {
        $this->_lucene = $lucene;
    }

    public function setEncoding($encoding)
    {
        $this->_encoding = (string) $encoding;
        return $this;
    }

    public function getEncoding()
    {
        return $this->_encoding;
    }

    public function optimize()
    {
        $this->_lucene->optimize();
        return $this;
    }

    public function find($query, $limit = null, $offset = null)
    {
        $limit  = max(0, (int) $limit);
        $offset = max(0, (int) $offset);

        $oldLimit = Zend_Search_Lucene::getResultSetLimit();
        $newLimit = $limit > 0 ? $limit + $offset : 0;

        Zend_Search_Lucene::setResultSetLimit($newLimit);

        


        Zend_Search_Lucene::getResultSetLimit($oldLimit);
    }

    public function add(ManipleCore_Search_DocumentInterface $document)
    {
        $doc = new Zend_Search_Lucene_Document();
        $this->_populateLuceneDocument($doc, $document);

        $this->_lucene->addDocument($doc);
        return $this;
    }

    public function update($id, ManipleCore_Search_DocumentInterface $document)
    {
        $term = new Zend_Search_Lucene_Index_Term($docId, $idFieldName);
        $docIds  = $index->termDocs($term);
        foreach ($docIds as $id) {
            $doc = $index->getDocument($id);
            $title    = $doc->title;
            $contents = $doc->contents;
        }
        return $this;
    }

    protected function _populateLuceneDocument(Zend_Search_Lucene_Document $luceneDoc, ManipleCore_Search_DocumentInterface $doc)
    {
        foreach ($doc->getTokenized() as $key => $value) {
            $luceneDoc->addField(Zend_Search_Lucene_Field::Text($key, $value, $this->_encoding));
        }
        foreach ($doc->getKeywords() as $key => $value) {
            $luceneDoc->addField(Zend_Search_Lucene_Field::Keyword($key, $value, $this->_encoding));
        }
        foreach ($doc->getBinary() as $key => $value) {
            $luceneDoc->addField(Zend_Search_Lucene_Field::Binary($key, $value, $this->_encoding));
        }
        foreach ($doc->getUnstored() as $key => $value) {
            $luceneDoc->addField(Zend_Search_Lucene_Field::UnStored($key, $value, $this->_encoding));
        }
        foreach ($doc->getUnindexed() as $key => $value) {
            $luceneDoc->addField(Zend_Search_Lucene_Field::UnIndexed($key, $value, $this->_encoding));
        }
    }
}
