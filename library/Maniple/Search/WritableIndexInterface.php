<?php

interface Maniple_Search_WritableIndexInterface
    extends Maniple_Search_IndexInterface
{
    /**
     * Inserts an entry into this index.
     *
     * @param  Maniple_Search_IndexableInterface $document
     * @return mixed
     */
    public function insert(Maniple_Search_DocumentInterface $document);

    /**
     * Updates an index entry at a given ID.
     *
     * @param  mixed $id
     * @param  Maniple_Search_IndexableInterface $document
     * @return mixed
     */
    public function update($id, Maniple_Search_DocumentInterface $document);

    /**
     * Removes an entry at a given ID from index.
     *
     * @param  mixed $id
     * @return mixed
     */
    public function delete($id);

    /**
     * Rebuilds or optimizes index structure.
     *
     * @return mixed
     */
    public function rebuild();
}
