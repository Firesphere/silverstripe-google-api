<?php

/**
 * Class GoogleAnalyticsReportServiceTest
 *
 * Test the report service it's parts
 */
class GoogleAnalyticsReportServiceTest extends SapphireTest
{
    /**
     * @var GoogleClientService
     */
    protected $client;

    /**
     * @var GoogleAnalyticsReportService
     */
    protected $service;

    public function setUp()
    {
        parent::setUp();
        $this->client = new GoogleClientService();
        $this->service = new GoogleAnalyticsReportService($this->client);
    }

    public function testSetClient()
    {
        $this->service->setClient($this->client);
        $this->assertInstanceOf(GoogleClientService::class, $this->service->getClient());
    }

    public function testGetAnalytics()
    {
        $this->assertInstanceOf(Google_Service_AnalyticsReporting::class, $this->service->getAnalytics());
    }

    public function testGetMetrics()
    {
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_Metric::class, $this->service->getMetrics());
    }

    public function testDateRange()
    {
        /* Set the daterange to 60 days */
        $this->service->setDateRange(60);
        /** @var Google_Service_AnalyticsReporting_DateRange $dateRange */
        $dateRange = $this->service->getDateRange();

        $this->assertInstanceOf(Google_Service_AnalyticsReporting_DateRange::class, $dateRange);

        $this->assertEquals('60daysAgo', $dateRange->getStartDate());
    }

    public function testGetStartDimensionFilters()
    {
        Config::inst()->update(GoogleAnalyticsReportService::class, 'starts_with', '/home');

        $filters = $this->service->getDimensionFilters();

        $this->assertTrue(is_array($filters));
        foreach ($filters as $filter) {
            $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilter::class, $filter);
        }
    }

    public function testGetEndDimensionFilters()
    {
        Config::inst()->update(GoogleAnalyticsReportService::class, 'ends_with', '/home');

        $filters = $this->service->getDimensionFilters();

        $this->assertTrue(is_array($filters));
        foreach ($filters as $filter) {
            $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilter::class, $filter);
        }
    }

    public function testGetPageDimensionFilters()
    {
        $filters = $this->service->getDimensionFilters();

        $this->assertTrue(is_array($filters));
        foreach ($filters as $filter) {
            $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilter::class, $filter);
        }
    }

    public function testGetDimensionFilterClauses()
    {
        $dimensionsClauses = $this->service->getDimensionFilterClauses();
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilterClause::class, $dimensionsClauses);
        $this->assertEquals('OR', $dimensionsClauses->getOperator());
    }

    public function testGetPages()
    {
        $pages = $this->service->getPages();
        $this->assertTrue(is_array($pages));
        $this->assertLessThanOrEqual(20, count($pages));
    }

    public function testFilterLength()
    {
        for ($i = 1; $i <= 30; $i++) {
            /** @var Page` $page */
            $page = Page::create(['Title' => $i . ' test']);
            $page->write();
            $page->doPublish();
        }
        $pages = $this->service->getPages();
        $this->assertTrue(is_array($pages));
        $this->assertEquals(20, count($pages));
        $this->assertTrue($this->service->batched);
    }

    public function testFindHomePage()
    {
        $testArray = ['', ''];
        $page = $this->service->findPage($testArray);
        $this->assertInstanceOf(Page::class, $page);
    }

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

    public function testGetBlacklistedPages()
    {
        Config::inst()->update(GoogleAnalyticsReportService::class, 'blacklist', [ErrorPage::class]);
        $pages = $this->service->getPages();
        $this->assertTrue(is_array($pages));
        $this->assertNotContains('page-not-found', $pages);
    }

    public function testGetWhitelistedPages()
    {
        Config::inst()->update(GoogleAnalyticsReportService::class, 'whitelist', [Page::class]);
        $pages = $this->service->getPages();
        $this->assertTrue(is_array($pages));
        // There seems to be some chaining in effect, causing the array to be empty
        $this->assertEquals(20, count($pages));
        // Error Pages are children of Page, so they should be included
        $this->assertContains('page-not-found', $pages);
    }

    public function testUpdateVisits()
    {
        $result = $this->service->updateVisits([]);
        $this->assertEquals(0, $result);
    }
}
