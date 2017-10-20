<?php

class AnalyticsResponseNoPageMock extends Google_Service_AnalyticsReporting_ReportRow
{
    public $response;

    public function getDimensions()
    {
        return [0 => '/i-do-not-exist'];
    }

    public function getMetrics()
    {
        $return = new Google_Service_AnalyticsReporting_DateRangeValues();
        $return->setValues([0 => 1]);
        return [$return];
    }
}
