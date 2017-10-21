<?php

namespace Firesphere\GoogleAPI\Tasks;

use Firesphere\GoogleAPI\Services\GoogleAnalyticsReportService;
use Firesphere\GoogleAPI\Services\GoogleClientService;
use Firesphere\GoogleAPI\Services\PageUpdateService;
use Google_Exception;
use LogicException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\ValidationException;

class AnalyticsUpdateTask extends BuildTask
{
    /**
     * @return string Title
     */
    public function getTitle()
    {
        return 'Update Google Analytics information for the configured pages';
    }

    /**
     * Start booting up
     * @param HTTPRequest $request
     * @throws Google_Exception
     * @throws LogicException
     * @throws ValidationException
     */
    public function run($request)
    {
        $clientService = new GoogleClientService();

        $this->getReport($clientService);
    }

    /**
     * Get the report and ask the service to update neatly
     *
     * @param GoogleClientService $client
     * @throws ValidationException
     */
    protected function getReport($client)
    {
        $service = new GoogleAnalyticsReportService($client);

        $reports = $service->getReport();
        $count = 0;

        $updateService = new PageUpdateService();
        /** @var array $rows */
        foreach ($reports as $report) {
            /** @var array $rows */
            $rows = $report->getData()->getRows();
            $count += $updateService->updateVisits($rows);
        }
        echo "<p>$count Pages updated with Google Analytics visit count</p>";
    }
}
