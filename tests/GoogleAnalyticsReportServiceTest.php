<?php

/**
 * Class GoogleAnalyticsReportServiceTest
 *
 * Test the report service it's parts
 */
class GoogleAnalyticsReportServiceTest extends SapphireTest
{
    const VIEWID = 12345;

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
        $siteConfig = SiteConfig::current_site_config();
        $siteConfig->Viewid = static::VIEWID;
        $siteConfig->write();
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
     * Confirm the report request is correctly build
     */
    public function testGetReportRequest()
    {
        $reportRequest = $this->service->getReportRequest();

        /** @var array|Google_Service_AnalyticsReporting_Metric[] $metrics */
        $metrics = $reportRequest->getMetrics();
        /** @var array|Google_Service_AnalyticsReporting_Dimension[] $dimensions */
        $dimensions = $reportRequest->getDimensions();

        $this->assertInstanceOf(Google_Service_AnalyticsReporting_ReportRequest::class, $reportRequest);
        $this->assertTrue(is_array($metrics));
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_Metric::class, $metrics[0]);
        $this->assertTrue(is_array($dimensions));
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_Dimension::class, $dimensions[0]);
        $this->assertEquals('ga:pagePath', $dimensions[0]->getName());
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_DimensionFilterClause::class, $reportRequest->getDimensionFilterClauses());
        $this->assertInstanceOf(Google_Service_AnalyticsReporting_DateRange::class, $reportRequest->getDateRanges());
        $this->assertEquals(static::VIEWID, $reportRequest->getViewId());
    }

    public function testGetDimension()
    {
        /** @var array|Google_Service_AnalyticsReporting_Dimension[] $dimensions */
        $dimensions = $this->service->getDimensions();
        $this->assertTrue(is_array($dimensions));
        foreach ($dimensions as $dimension) {
            $this->assertEquals('ga:pagePath', $dimension->getName());
        }
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
            $this->assertTrue(in_array($filter->getOperator(), ['ENDS_WITH', 'EXACT'], true));
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
        $this->assertCount(20, $pages);
        $this->assertTrue($this->service->batched);
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
}
