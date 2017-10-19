<?php

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
     * @param SS_HTTPRequest $request
     * @throws \Google_Exception
     * @throws \LogicException
     */
    public function run($request)
    {
        $clientService = new GoogleClientService();

        $this->getReport($clientService);
    }

    protected function getReport($client)
    {
        $service = new GoogleAnalyticsReportService($client);

        $reports = $service->getReport();
        $count = 0;
        /** @var array $rows */
        foreach ($reports as $report) {
            $rows = $report->getData()->getRows();
            $count += $service->updateVisits($rows);
        }
        echo "<p>$count Pages updated with Google Analytics visit count</p>";
    }
}
