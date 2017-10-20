<?php

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
     * @return mixed
     */
    public function getReport()
    {
        $config = SiteConfig::current_site_config();

        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setDimensions(['name' => 'ga:pagePath']);
        $request->setViewId($config->Viewid);
        $request->setDimensionFilterClauses($this->getDimensionFilterClauses());
        $request->setDateRanges($this->getDateRange());

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests([$request]);

        return $this->analytics->reports->batchGet($body);
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
        $filters = [];
        $startWith = config::inst()->get(static::class, 'starts_with');
        $endWith = config::inst()->get(static::class, 'ends_with');
        if ($startWith) {
            return $this->createFilter('BEGINS_WITH', $startWith, $filters);
        }
        if ($endWith) {
            return $this->createFilter('ENDS_WITH', $endWith, $filters);
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
            $pages = $this->getBlacklist($blacklist, $pages);
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
    protected function getBlacklist($blacklist, $pages)
    {
        $ids = [];
        foreach ($blacklist as $class) {
            $blacklistpages = $class::get();
            $ids = array_merge($ids, $blacklistpages->column('ID'));
        }
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
        $ids = [];
        foreach ($whitelist as $class) {
            $nowDate = date('Y-m-d');
            $whitelistpages = $class::get()
                // This needs to be a where because of `IS NULL`
                ->where("(`Page`.`LastAnalyticsUpdate` < $nowDate)
                        OR (`Page`.`LastAnalyticsUpdate` IS NULL)");
            $ids = array_merge($ids, $whitelistpages->column('ID'));
        }
        $pages = $pages->filter(['ID' => $ids]);

        return $pages;
    }

    /**
     * @param $operator
     * @param $page
     * @param $filters
     * @return array|Google_Service_AnalyticsReporting_DimensionFilter[]
     */
    protected function createFilter($operator, $page, $filters)
    {
        $filter = new Google_Service_AnalyticsReporting_DimensionFilter();
        $filter->setOperator($operator);
        $filter->setCaseSensitive(false);
        $filter->setExpressions([$page]);
        $filter->setDimensionName('ga:pagePath');
        $filters[] = $filter;

        return $filters;
    }

    /**
     * @param array $rows
     * @return int
     * @throws \ValidationException
     */
    public function updateVisits($rows)
    {
        $count = 0;
        foreach ($rows as $row) {
            $metrics = $row->getMetrics();
            $values = $metrics[0]->getValues();
            $dimensions = $row->getDimensions();
            $dimensions = explode('/', $dimensions[0]);
            $page = $this->findPage($dimensions);
            // Only write if a page is found
            if ($page) {
                $count++;
                $this->updatePageVisit($page, $values);
            }
        }
        // If we're not getting any results back, we're out of data from Google.
        // Stop the batching process.
        if ($count === 0) {
            $this->batched = false;
        }
        return $count;
    }

    /**
     * @param array $dimensions
     * @return Page
     */
    public function findPage($dimensions)
    {
        // Because analytics returns a page link that starts with a forward slash
        // The first element of the array needs to be removed as it's empty.
        array_shift($dimensions);
        $firstItem = array_shift($dimensions);
        // If dimensions is 2 empty keys, we're looking at the homepage
        if (!$firstItem) {
            $firstItem = 'home';
        }
        // Start at the root of the site page
        /** @var Page $page */
        $page = Page::get()->filter(['URLSegment' => $firstItem])->first();
        // If there are items left in the dimensions, continue traversing down
        if (count($dimensions) && $page) {
            $page = $this->findPageChildren($dimensions, $page);
        }

        return $page;
    }

    /**
     * @param array $dimensions
     * @param Page $page
     * @return mixed
     */
    protected function findPageChildren($dimensions, $page)
    {
        foreach ($dimensions as $segment) {
            if (strpos($segment, '?') === 0 || !$page || !$segment) {
                continue;
            }
            /** @var DataList|Page[] $children */
            $children = $page->AllChildren();
            $page = $children->filter(['URLSegment' => $segment])->first();
        }

        return $page;
    }

    /**
     * @param Page $page
     * @param array $values
     * @throws \ValidationException
     */
    protected function updatePageVisit($page, $values)
    {
        $rePublish = $page->isPublished();
        $page->VisitCount = $values[0];
        $page->LastAnalyticsUpdate = date('Y-m-d');
        $page->write();
        // If the page was published before write, republish or the change won't be applied
        if ($rePublish) {
            $page->publish('Stage', 'Live');
        }
        $page->destroy();
    }
}
