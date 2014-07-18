<?php

interface Maniple_Search_DocumentInterface
{
    /**
     * Adds a tokenized string value that will be stored and indexed.
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function addTokenized($key, $value);

    /**
     * Adds a non-tokenized string value that will be stored and indexed.
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function addKeyword($key, $value);

    /**
     * Adds a non-tokenized binary value that will be stored and indexed.
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function addBinary($key, $value);

    /**
     * Adds a non-tokenized string value that will be indexed but not
     * stored in the index.
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function addUnstored($key, $value);

    /**
     * Adds a non-tokenized string value that will be indexed but not
     * stored in the index.
     *
     * @param  string $key
     * @param  string $value
     * @return mixed
     */
    public function addUnindexed($key, $value);

    /**
     * Retrieves all tokenized string values.
     *
     * @return array
     */
    public function getTokenized();

    /**
     * @return array
     */
    public function getKeywords();

    /**
     * @return array
     */
    public function getBinary();

    /**
     * @return array
     */
    public function getUnstored();

    /**
     * @return array
     */
    public function getUnindexed();
}
