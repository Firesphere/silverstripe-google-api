<?php

namespace Firesphere\GoogleAPI\Extensions;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataExtension;

/**
 * Class GoogleAPISiteConfigExtension
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\Firesphere\GoogleAPI\Extensions\SiteConfigExtension $owner
 * @property string $Viewid
 * @property string $DateRange
 * @property string $Metric
 */
class SiteConfigExtension extends DataExtension
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
