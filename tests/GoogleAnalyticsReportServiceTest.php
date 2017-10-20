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

    /**
     * Create the Services
     */
    public function setUp()
    {
        parent::setUp();
        $this->client = new GoogleClientService();
        $this->service = new GoogleAnalyticsReportService($this->client);
    }

    /**
     * Check if we can set and get the client
     */
    public function testSetClient()
    {
        $this->service->setClient($this->client);
        $this->assertInstanceOf(GoogleClientService::class, $this->service->getClient());
    }

    /**
     * See if we have a new Service registered
     */
    public function testGetAnalytics()
    {
        $this->assertInstanceOf(Google_Service_AnalyticsReporting::class, $this->service->getAnalytics());
    }

    /**
     * Check if the Metric is set
     */
    public function testGetMetrics()
    {
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_Metric::class, $this->service->getMetrics());
    }

    /**
     * Check if setting the daterange updates the Analytics_DateRange
     */
    public function testDateRange()
    {
        /* Set the daterange to 60 days */
        $this->service->setDateRange(60);
        /** @var Google_Service_AnalyticsReporting_DateRange $dateRange */
        $dateRange = $this->service->getDateRange();

        $this->assertInstanceOf(Google_Service_AnalyticsReporting_DateRange::class, $dateRange);

        $this->assertEquals('60daysAgo', $dateRange->getStartDate());
    }

    /**
     * Validate start_with filter
     */
    public function testGetStartDimensionFilters()
    {
        Config::inst()->update(GoogleAnalyticsReportService::class, 'starts_with', '/');

        $filters = $this->service->getDimensionFilters();

        $this->assertTrue(is_array($filters));
        /** @var  $filter */
        foreach ($filters as $filter) {
            $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilter::class, $filter);
            $expressions = $filter->getExpressions();
            $this->assertEquals('/', $expressions[0]);
            $this->assertEquals('BEGINS_WITH', $filter->getOperator());
        }
    }

    /**
     * validate end_with filter
     */
    public function testGetEndDimensionFilters()
    {
        Config::inst()->update(GoogleAnalyticsReportService::class, 'ends_with', '/');

        $filters = $this->service->getDimensionFilters();

        $this->assertTrue(is_array($filters));
        foreach ($filters as $filter) {
            $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilter::class, $filter);
            $expressions = $filter->getExpressions();
            $this->assertEquals('/', $expressions[0]);
            $this->assertEquals('ENDS_WITH', $filter->getOperator());
        }
    }

    /**
     * Test page filters are created
     */
    public function testGetPageDimensionFilters()
    {
        $filters = $this->service->getDimensionFilters();

        $this->assertTrue(is_array($filters));
        foreach ($filters as $filter) {
            $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilter::class, $filter);
            $this->assertEquals('ENDS_WITH', $filter->getOperator());
        }
    }

    /**
     * Validate our DimensionClause is set with OR
     */
    public function testGetDimensionFilterClauses()
    {
        $dimensionsClauses = $this->service->getDimensionFilterClauses();
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilterClause::class, $dimensionsClauses);
        $this->assertEquals('OR', $dimensionsClauses->getOperator());
    }

    /**
     * Make sure the filter-list does not exceed 20
     */
    public function testGetPages()
    {
        $pages = $this->service->getPages();
        $this->assertTrue(is_array($pages));
        $this->assertLessThanOrEqual(20, count($pages));
    }

    /**
     * Make sure we're not adding more than 20 filters, despite more pages being available
     */
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

    /**
     * Validate an array with two empty values finds the homepage
     */
    public function testFindHomePage()
    {
        $testArray = ['', ''];
        $page = $this->service->findPage($testArray);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertTrue('home', $page->URLSegment);
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
     * Validate blacklisted pages don't show up
     */
    public function testGetBlacklistedPages()
    {
        Config::inst()->update(GoogleAnalyticsReportService::class, 'blacklist', [ErrorPage::class]);
        $pages = $this->service->getPages();
        $this->assertTrue(is_array($pages));
        $this->assertNotContains('page-not-found', $pages);
    }

    /**
     * Make sure we only get whitelisted pages
     */
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
