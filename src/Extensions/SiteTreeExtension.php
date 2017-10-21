<?php

namespace Firesphere\GoogleAPI\Extensions;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\ORM\DataExtension;

/**
 * Class PageExtension
 *
 * @property \SilverStripe\CMS\Model\SiteTree|\Firesphere\GoogleAPI\Extensions\SiteTreeExtension $owner
 * @property int $VisitCount
 * @property string $LastAnalyticsUpdate
 */
class SiteTreeExtension extends DataExtension
{
    private static $db = [
        'VisitCount'          => 'Int',
        'LastAnalyticsUpdate' => 'Date'
    ];

    public function updateCMSFields(FieldList $fields)
    {
        if ($this->owner->LastAnalyticsUpdate) {
            $fields->addFieldToTab('Root.Analytics', ReadonlyField::create('VisitCount', 'Visit count'));
            $fields->addFieldToTab('Root.Analytics', ReadonlyField::create('LastAnalyticsUpdate', 'Last update from Google'));
        } else {
            $fields->removeByName(array_keys(static::$db));
        }
    }
}
