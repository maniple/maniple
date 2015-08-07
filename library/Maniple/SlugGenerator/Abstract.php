<?php

/**
 * Class Maniple_SlugGenerator_Abstract
 *
 * @uses Zefram_Filter_Slug
 */
abstract class Maniple_SlugGenerator_Abstract
{
    /**
     * @param string $slug
     * @return bool
     */
    abstract public function slugExists($slug);

    /**
     * @param string $string
     * @return string
     * @throws Exception
     */
    public function create($string)
    {
        $origString = $string;
        $count = 0;

        while ($this->slugExists($slug = $this->slugify($string))) {
            if ($count >= 100) {
                throw new Exception('Unable to generate slug, loop limit reached');
            }
            $string = $origString . ' ' . $count++;
        }

        return $slug;
    }

    /**
     * @param string $string
     * @return string
     */
    public function slugify($string)
    {
        return Zefram_Filter_Slug::filterStatic($string);
    }
}