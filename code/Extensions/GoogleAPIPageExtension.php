<?php

/**
 * Class PageExtension
 *
 * @property Page|GoogleAPIPageExtension $owner
 * @property int $VisitCount
 * @property string $LastAnalyticsUpdate
 */
class GoogleAPIPageExtension extends DataExtension
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
