<?php

namespace Firesphere\GoogleAPI\Tests\Mock;

use Google_Service_AnalyticsReporting_DateRangeValues;
use Google_Service_AnalyticsReporting_ReportRow;

class AnalyticsResponseHomePageMock extends Google_Service_AnalyticsReporting_ReportRow
{
    public $response;

    public function getDimensions()
    {
        return [0 => '/'];
    }

    public function getMetrics()
    {
        $return = new Google_Service_AnalyticsReporting_DateRangeValues();
        $return->setValues([0 => 45477]);
        return [$return];
    }
}
