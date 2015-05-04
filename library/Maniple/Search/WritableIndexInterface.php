<?php

interface Maniple_Search_WritableIndexInterface
    extends Maniple_Search_IndexInterface
{
    /**
     * Inserts a document into this index.
     *
     * If a document contains fields marked as unique, all documents
     * with the same values of these fields are removed from index.
     *
     * @param  Maniple_Search_DocumentInterface $document
     * @return mixed
     */
    public function insert(Maniple_Search_DocumentInterface $document);

    /**
     * Removes from index a document matching given field value.
     *
     * @param  Maniple_Search_FieldInterface $field
     * @return mixed
     */
    public function delete(Maniple_Search_FieldInterface $field);

    /**
     * Rebuilds or optimizes index structure.
     *
     * @return mixed
     */
    public function rebuild();
}
