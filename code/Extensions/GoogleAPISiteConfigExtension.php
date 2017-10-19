<?php


/**
 * Class GoogleAPISiteConfigExtension
 *
 * @property SiteConfig|GoogleAPISiteConfigExtension $owner
 * @property string $Viewid
 * @property string $DateRange
 * @property string $Metric
 */
class GoogleAPISiteConfigExtension extends DataExtension
{
    private static $db = [
        'Viewid'    => 'Varchar(50)',
        'DateRange' => 'Enum("7,30,60,90")',
        'Metric' => 'Enum("ga:pageviews")'
    ];

    protected static $date_range = [
        7  => '7 Days',
        30 => '30 Days',
        60 => '60 Days',
        90 => '90 Days'
    ];

    protected static $metrics = [
        'ga:pageviews' => 'Pageviews',
    ];

    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.GoogleAPI', [
            TextField::create('Viewid', 'View ID from Analytics'),
            DropdownField::create('DateRange', 'Amount of days to get', static::$date_range),
            DropdownField::create('Metric', 'Metrics', static::$metrics)
        ]);
    }
}
