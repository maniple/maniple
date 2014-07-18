<?php

interface Maniple_Search_IndexInterface
{
    public function add(Maniple_Search_DocumentInterface $document);

    public function update($id, Maniple_Search_DocumentInterface $document);

    public function optimize();

    public function find($query, $limit = null, $offset = null);
}
