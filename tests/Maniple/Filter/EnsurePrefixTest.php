<?php

class Maniple_Filter_EnsurePrefixTest extends PHPUnit_Framework_TestCase
{
    public function testConstructorWithDefaultOptions()
    {
        $filter = new Maniple_Filter_EnsurePrefix();

        $this->assertEquals('', $filter->getPrefix());
        $this->assertTrue($filter->getMatchCase());
    }

    public function testConstructorWithOptions()
    {
        $filter = new Maniple_Filter_EnsurePrefix(array(
            'prefix' => 'foo',
            'matchCase' => false,
        ));

        $this->assertEquals('foo', $filter->getPrefix());
        $this->assertFalse($filter->getMatchCase());
    }

    public function testGetAndSetPrefix()
    {
        $filter = new Maniple_Filter_EnsurePrefix();

        $filter->setPrefix('foo');
        $this->assertEquals('foo', $filter->getPrefix());

        $filter->setPrefix(array('foo', 'bar'));
        $this->assertEquals(array('foo', 'bar'), $filter->getPrefix());
    }

    public function testGetAndSetMatchCase()
    {
        $filter = new Maniple_Filter_EnsurePrefix();

        $filter->setMatchCase(false);
        $this->assertFalse($filter->getMatchCase());

        $filter->setMatchCase(true);
        $this->assertTrue($filter->getMatchCase());
    }

    public function testFilter()
    {
        $filter = new Maniple_Filter_EnsurePrefix();
        $filter->setPrefix('foo');

        $this->assertEquals('foobar', $filter->filter('bar'));
        $this->assertEquals('foobar', $filter->filter('foobar'));
    }

    public function testFilterCaseInsensitive()
    {
        $filter = new Maniple_Filter_EnsurePrefix();
        $filter->setPrefix('foo');
        $filter->setMatchCase(false);

        $this->assertEquals('foobar', $filter->filter('bar'));
        $this->assertEquals('Foobar', $filter->filter('Foobar'));
    }

    public function testFilterEmptyString()
    {
        $filter = new Maniple_Filter_EnsurePrefix();
        $filter->setPrefix('foo');

        $this->assertEquals('', $filter->filter(''));
    }

    public function testFilterMultiplePrefixes()
    {
        $filter = new Maniple_Filter_EnsurePrefix();
        $filter->setPrefix(array('foo', 'bar'));

        $this->assertEquals('foobar', $filter->filter('foobar'));
        $this->assertEquals('barbaz', $filter->filter('barbaz'));
        $this->assertEquals('foobaz', $filter->filter('baz'));
    }

    public function testFilterStatic()
    {
        $options = array(
            'prefix' => 'foo',
            'matchCase' => true,
        );
        $this->assertEquals('foobar', Maniple_Filter_EnsurePrefix::filterStatic('bar', $options));
        $this->assertEquals('foobar', Maniple_Filter_EnsurePrefix::filterStatic('foobar', $options));
    }
}
