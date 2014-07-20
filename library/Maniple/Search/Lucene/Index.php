<?php

class Maniple_Search_Lucene_Index
    implements Maniple_Search_WritableIndexInterface
{
    /**
     * @var Zend_Search_Lucene_Interface
     */
    protected $_lucene;

    /**
     * @var string
     */
    protected $_idField;

    public function __construct(Zend_Search_Lucene_Interface $lucene, $idField = 'id')
    {
        $this->_lucene = $lucene;
        $this->_idField = $idField;
    }

    public function rebuild()
    {
        $this->_lucene->optimize();
        return $this;
    }

    public function search($query, $limit = null, $offset = null)
    {
        $oldLimit = Zend_Search_Lucene::getResultSetLimit();
        Zend_Search_Lucene::setResultSetLimit(0);

        $hits = $this->_lucene->find($query);

        Zend_Search_Lucene::getResultSetLimit($oldLimit);

        reset($hits);
        if ($limit > 0 && $offset > 0) {
            while (next($hits)) {
                --$offset;
            }
        }

        $h = array();
        while ($hit = current($hits)) {
            $h[] = $hit; // hit getDocument, getScore
            if (count($h) === $limit) {
                break;
            }
            next($hits);
        }

        foreach ($h as $x) {
            echo '[';
            echo $x->getDocument()->getFieldValue('file_id'), ', ';
            echo $x->getDocument()->getFieldValue('name'), ', ';
            echo $x->getDocument()->getFieldValue('md5sum'), ', ';
            echo ']<br/>';
        }

        return $h;
    }

    public function insert(Maniple_Search_DocumentInterface $document)
    {
        $idField = $document->getField($this->_idField);

        if (empty($idField)) {
            throw new Exception(sprintf('Document ID (%s) must be non-empty', $this->_idField));
        }

        $this->delete($idField->getValue());

        $doc = new Zend_Search_Lucene_Document();
        $this->setFields($doc, $document);

        $this->_lucene->addDocument($doc);

        return $this;
    }

    public function update($id, Maniple_Search_DocumentInterface $document)
    {
        $term = new Zend_Search_Lucene_Index_Term($id, $this->_idField);
        $docIds = $this->_lucene->termDocs($term);
        $updated = false;

        // update only first matched document, remove the rest (there should
        // be no other documents, as ID field is expected to be unique)
        foreach ($docIds as $docId) {
            $doc = $index->getDocument($docId);
            $this->_lucene->delete($docId);

            if (!$updated) {
                $this->setFields($doc, $document);
                $this->_lucene->addDocument($doc);
                $updated = true;
            }
        }

        if (!$updated) {
            throw new Exception(sprintf('No document with given ID found (%s)', $id));
        }

        return $this;
    }

    public function delete($id)
    {
        $term = new Zend_Search_Lucene_Index_Term($id, $this->_idField);
        $docIds = $this->_lucene->termDocs($term);
        foreach ($docIds as $docId) {
            $this->_lucene->delete($docId);
        }

        return $this;
    }

    protected function setFields(Zend_Search_Lucene_Document $luceneDoc, Maniple_Search_DocumentInterface $doc)
    {
        foreach ($doc->getFields() as $field) {
            if ($field->isTokenizable()) {
                $luceneDoc->addField(Zend_Search_Lucene_Field::Text(
                    $field->getName(), $field->getValue(), 'utf-8'
                ));
            } else {
                $luceneDoc->addField(Zend_Search_Lucene_Field::Keyword(
                    $field->getName(), $field->getValue(), 'utf-8'
                ));
            }
        }
    }
}
