<?php

interface Maniple_Search_SearchResultsInterface
{
    public function getHitCount();

    public function getHits();

    public function getTotalHitCount();
}
