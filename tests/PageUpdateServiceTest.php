<?php
/**
 * Created by PhpStorm.
 * User: simon
 * Date: 20-Oct-17
 * Time: 15:04
 */

class PageUpdateServiceTest extends SapphireTest
{

    /**
     * @var PageUpdateService
     */
    protected $service;

    public function setUp()
    {
        $this->service = new PageUpdateService();
        parent::setUp();
    }
    
    /**
     * Validate an array with two empty values finds the homepage
     */
    public function testFindHomePage()
    {
        $testArray = ['', ''];
        $page = $this->service->findPage($testArray);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('home', $page->URLSegment);
    }

    /**
     * Validate we're traversing correctly down in to the child pages
     */
    public function testFindChildPage()
    {
        /** @var Page $homePage */
        $homePage = Page::get()->filter(['URLSegment' => 'home'])->first();
        /** @var Page $homePage */
        $child = Page::create(['Title' => 'Test Page', 'ParentID' => $homePage->ID]);
        $child->write();
        $child->doPublish();
        $testArray = ['', '', 'test-page'];
        $page = $this->service->findPage($testArray);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('/home/test-page/', $page->Link());
    }

    /**
     * Make sure the get params are ignored
     */
    public function testFindChildPageWithQuery()
    {
        /** @var Page $homePage */
        $homePage = Page::get()->filter(['URLSegment' => 'home'])->first();
        /** @var Page $homePage */
        $child = Page::create(['Title' => 'Test Page', 'ParentID' => $homePage->ID]);
        $child->write();
        $child->doPublish();
        $testArray = ['', '', 'test-page', '?stage=Stage'];
        $page = $this->service->findPage($testArray);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('/home/test-page/', $page->Link());
    }

    /**
     * Make sure an empty last value is skipped
     */
    public function testFindPageWithEmptyLast()
    {
        /** @var Page $homePage */
        $homePage = Page::get()->filter(['URLSegment' => 'home'])->first();
        /** @var Page $homePage */
        $child = Page::create(['Title' => 'Test Page', 'ParentID' => $homePage->ID]);
        $child->write();
        $child->doPublish();
        $testArray = ['', '', 'test-page', ''];
        $page = $this->service->findPage($testArray);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertEquals('/home/test-page/', $page->Link());
    }

    /**
     * Validate updating the pages works
     */
    public function testUpdateVisits()
    {
        $result = $this->service->updateVisits([new AnalyticsResponseHomePageMock()]);
        $this->assertEquals(1, $result);
        $page = Page::get()->filter(['URLSegment' => 'home'])->first();
        $this->assertEquals(45477, $page->VisitCount);
    }

    /**
     * Validate that if no pages is found, nothing is done.
     */
    public function testUpdateNoVisits()
    {
        $result = $this->service->updateVisits([new AnalyticsResponseNoPageMock()]);
        $this->assertEquals(0, $result);
        $this->assertFalse($this->service->batched);
    }
}
