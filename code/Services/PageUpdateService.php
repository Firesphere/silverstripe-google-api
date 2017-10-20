<?php

/**
 * Class PageUpdateService is used to update the count on pages
 */
class PageUpdateService
{

    /**
     * @var bool
     */
    public $batched = true;

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
        if ($count === 0 || $count > 20) {
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
