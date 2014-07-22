<?php

class Maniple_Search_Lucene_Index
    implements Maniple_Search_WritableIndexInterface
{
    /**
     * @var Zend_Search_Lucene_Interface
     */
    protected $_lucene;

    public function __construct($path)
    {
        $path = $this->_checkIndexStorageDir(dirname($path)) . '/' . basename($path);

        try {
            $lucene = Zefram_Search_Lucene::open($path);

        } catch (Zend_Search_Lucene_Exception $e) {
            // Lucene index was not found or is unreadable
        }

        if (empty($lucene)) {
            $lucene = Zefram_Search_Lucene::create($path);
        }

        $this->_lucene = $lucene;
    }

    public function setAnalyzer(Zend_Search_Lucene_Analysis_Analyzer $analyzer = null)
    {
        $this->_lucene->setAnalyzer($analyzer);
        return $this;
    }

    protected function _checkIndexStorageDir($dir)
    {
        if (!is_dir($dir) || !is_readable($dir) || !is_writable($dir)) {
            throw new InvalidArgumentException(sprintf('Invalid index storage directory (%s)', $dir));
        }
        return realpath($dir);
    }

    public function rebuild()
    {
        $this->_lucene->optimize();
        return $this;
    }

    /**
     * @param  string|Maniple_Search_Document|Maniple_Search_Field $query
     * @param  int $limit OPTIONAL
     * @param  int $offset OPTIONAL
     */
    public function search($query, $limit = null, $offset = null)
    {
        $oldLimit = Zend_Search_Lucene::getResultSetLimit();
        Zend_Search_Lucene::setResultSetLimit(0);

        if (is_string($query)) {
            $query = Zend_Search_Lucene_Search_QueryParser::parse($query, 'UTF-8');
        }

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
            $h[] = (object) array(
                'score' => $hit->score,
                'document' => $hit->getDocument(),
            );
            if (count($h) === $limit) {
                break;
            }
            next($hits);
        }

        return (object) array(
            'hitCount'      => count($h),
            'totalHitCount' => count($hits),
            'hits'          => $h,
        );
    }

    public function insert(Maniple_Search_DocumentInterface $document)
    {
        // remove from index all documents with the same values in uniqe fields
        foreach ($document->getFields() as $field) {
            if ($field->isUnique()) {
                $this->delete($field);
            }
        }

        $doc = new Zend_Search_Lucene_Document();
        $this->setFields($doc, $document);

        $this->_lucene->addDocument($doc);

        return $this;
    }

    public function delete(Maniple_Search_FieldInterface $field)
    {
        $term = new Zend_Search_Lucene_Index_Term($field->getValue(), $field->getName());
        $docIds = $this->_lucene->termDocs($term);
        foreach ($docIds as $docId) {
            $this->_lucene->delete($docId);
        }
        $this->_lucene->commit();
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

    public function getFieldQuery($field, $value = null)
    {
        if ($field instanceof Maniple_Search_FieldInterface) {
            $value = $field->getValue();
            $field = $field->getName();
        }
        return sprintf('%s:"%s"', $field, $value);
    }
}
