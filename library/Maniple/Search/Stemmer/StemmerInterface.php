<?php

interface Maniple_Search_Stemmer_StemmerInterface
{
    /**
     * Reduces given word to its stem form.
     *
     * @param  string $word
     * @return string
     */
    public function stem($word);
}
