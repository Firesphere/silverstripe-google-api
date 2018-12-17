<?php

namespace Firesphere\GoogleAPI\Services;

use Google_Service_AnalyticsReporting;
use Google_Service_AnalyticsReporting_DateRange;
use Google_Service_AnalyticsReporting_Dimension;
use Google_Service_AnalyticsReporting_DimensionFilter;
use Google_Service_AnalyticsReporting_DimensionFilterClause;
use Google_Service_AnalyticsReporting_GetReportsRequest;
use Google_Service_AnalyticsReporting_GetReportsResponse;
use Google_Service_AnalyticsReporting_Metric;
use Google_Service_AnalyticsReporting_ReportRequest;
use Page;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * Class GoogleAnalyticsReportService
 *
 * Base service for getting the Analytics report per page from Google.
 */
class GoogleAnalyticsReportService
{

    /**
     * @var GoogleClientService
     */
    protected $client;

    /**
     * @var Google_Service_AnalyticsReporting_DateRange
     */
    protected $dateRange;

    /**
     * @var Google_Service_AnalyticsReporting
     */
    protected $analytics;

    /**
     * @var Google_Service_AnalyticsReporting_Metric
     */
    protected $metrics;

    /**
     * Should the filter being batched
     *
     * @var bool
     */
    public $batched = false;

    /**
     * GoogleReportService constructor.
     * @param GoogleClientService $client
     */
    public function __construct($client)
    {
        $config = SiteConfig::current_site_config();
        $this->client = $client;
        $this->setDateRange($config->DateRange);
        $this->setAnalytics(new Google_Service_AnalyticsReporting($client->getClient()));
        $this->setMetrics($config->Metric);
    }

    /**
     * @return Google_Service_AnalyticsReporting_GetReportsResponse
     */
    public function getReport()
    {
        $request = $this->getReportRequest();

        $body = $this->getGetReportRequest($request);

        return $this->analytics->reports->batchGet($body);
    }

    /**
     * @return Google_Service_AnalyticsReporting_ReportRequest
     */
    public function getReportRequest()
    {
        $config = SiteConfig::current_site_config();

        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $dimension = $this->getDimensions();
        $request->setDimensions($dimension);
        $request->setViewId($config->Viewid);
        $request->setDimensionFilterClauses($this->getDimensionFilterClauses());
        $request->setDateRanges($this->getDateRange());
        $request->setMetrics([$this->getMetrics()]);

        return $request;
    }

    /**
     * Yes, thanks to Google's naming, we're doubling up the get here
     *
     * @param $request
     * @return Google_Service_AnalyticsReporting_GetReportsRequest
     */
    public function getGetReportRequest($request)
    {
        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        return $body;
    }

    /**
     * @return array|Google_Service_AnalyticsReporting_Dimension[]
     */
    public function getDimensions()
    {
        $dimension = new Google_Service_AnalyticsReporting_Dimension();
        $dimension->setName('ga:pagePath');

        return [$dimension];
    }

    /**
     * @return mixed
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param mixed $client
     */
    public function setClient($client)
    {
        $this->client = $client;
    }

    /**
     * @return Google_Service_AnalyticsReporting_DateRange
     */
    public function getDateRange()
    {
        return $this->dateRange;
    }

    /**
     * @param int $dateRange
     */
    public function setDateRange($dateRange)
    {
        $analyticsRange = new Google_Service_AnalyticsReporting_DateRange();
        $analyticsRange->setStartDate($dateRange . 'daysAgo');
        $analyticsRange->setEndDate('today');
        $this->dateRange = $analyticsRange;
    }

    /**
     * @return Google_Service_AnalyticsReporting
     */
    public function getAnalytics()
    {
        return $this->analytics;
    }

    /**
     * @param Google_Service_AnalyticsReporting $analytics
     */
    public function setAnalytics($analytics)
    {
        $this->analytics = $analytics;
    }

    /**
     * @return mixed
     */
    public function getMetrics()
    {
        return $this->metrics;
    }

    /**
     * @param mixed $metrics
     */
    public function setMetrics($metrics)
    {
        $metric = new Google_Service_AnalyticsReporting_Metric();
        $metric->setExpression($metrics);
        $this->metrics = $metric;
    }

    /**
     * Set up the Dimension Filters
     *
     * @return array|Google_Service_AnalyticsReporting_DimensionFilter[]
     */
    public function getDimensionFilters()
    {
        $startWith = Config::inst()->get(static::class, 'starts_with');
        $endWith = Config::inst()->get(static::class, 'ends_with');
        if ($startWith) {
            return $this->createFilter('BEGINS_WITH', $startWith);
        }
        if ($endWith) {
            return $this->createFilter('ENDS_WITH', $endWith);
        }

        return $this->getPageDimensionFilters();
    }

    /**
     * @return array|Google_Service_AnalyticsReporting_DimensionFilter[]
     */
    public function getPageDimensionFilters()
    {
        $filters = [];
        // Operators are not constants, see here: https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet#Operator
        $operator = 'ENDS_WITH';
        $pages = $this->getPages();

        foreach ($pages as $page) {
            // Home is recorded as just a slash in GA, we need an
            // exception for that
            if ($page === 'home') {
                $page = '/';
                $operator = 'EXACT';
            }

            $filters = $this->createFilter($operator, $page, $filters);
            // Reset the operator
            if ($operator === 'EXACT') {
                $operator = 'ENDS_WITH';
            }
        }

        return $filters;
    }

    /**
     * @return Google_Service_AnalyticsReporting_DimensionFilterClause
     */
    public function getDimensionFilterClauses()
    {
        $dimensionFilters = $this->getDimensionFilters();
        $dimensionClauses = new Google_Service_AnalyticsReporting_DimensionFilterClause();
        $dimensionClauses->setFilters($dimensionFilters);
        $dimensionClauses->setOperator('OR');

        return $dimensionClauses;
    }

    /**
     * Get the pages filtered by optional blacklisting and whitelisting
     * Return an array of the URLSegments, to limit memory abuse
     * @return array
     */
    public function getPages()
    {
        $blacklist = Config::inst()->get(static::class, 'blacklist');
        $whitelist = Config::inst()->get(static::class, 'whitelist');
        /** @var DataList|Page[] $pages */
        $pages = SiteTree::get();
        if ($blacklist) {
            $pages = $this->getBlacklistPages($blacklist, $pages);
        }
        if ($whitelist) {
            $pages = $this->getWhitelistPages($whitelist, $pages);
        }

        if ($pages->count() > 20) {
            $this->batched = true;
        }

        // Google limits to 20 reports per complex filter setup
        return $pages->limit(20)->column('URLSegment');
    }

    /**
     * @param array $blacklist
     * @param DataList $pages
     * @return DataList
     */
    protected function getBlacklistPages($blacklist, $pages)
    {
        $ids = [[]];
        foreach ($blacklist as $class) {
            $blacklistpages = $class::get();
            $ids[] = $blacklistpages->column('ID');
        }

        $ids = array_merge(...$ids);
        $pages = $pages->exclude(['ID' => $ids]);

        return $pages;
    }

    /**
     * @param array $whitelist
     * @param DataList $pages
     * @return DataList
     */
    protected function getWhitelistPages($whitelist, $pages)
    {
        $ids = [[]];
        foreach ($whitelist as $class) {
            $nowDate = date('Y-m-d');
            /** @var DataList|DataObject[] $whitelistpages */
            $whitelistpages = $class::get()
                // This needs to be a where because of `IS NULL`
                ->where("(`SiteTree`.`LastAnalyticsUpdate` < $nowDate)
                        OR (`SiteTree`.`LastAnalyticsUpdate` IS NULL)");
            $ids[] = $whitelistpages->column('ID');
        }

        $ids = array_merge(...$ids);

        $pages = $pages->filter(['ID' => $ids]);

        return $pages;
    }

    /**
     * @param $operator
     * @param $page
     * @param $filters
     * @return array|Google_Service_AnalyticsReporting_DimensionFilter[]
     */
    protected function createFilter($operator, $page, $filters = [])
    {
        $filter = new Google_Service_AnalyticsReporting_DimensionFilter();
        $filter->setOperator($operator);
        $filter->setCaseSensitive(false);
        $filter->setExpressions([$page]);
        $filter->setDimensionName('ga:pagePath');
        $filters[] = $filter;

        return $filters;
    }
}
