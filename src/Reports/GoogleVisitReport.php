<?php

namespace Firesphere\GoogleAPI\Reports;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\DataList;
use SilverStripe\Reports\Report;

/**
 * Class GoogleVisitReport is a very simplistic list of just visits of a page
 * It is not a replacement for Google Analytics!
 */
class GoogleVisitReport extends Report
{
    public function title()
    {
        return _t(__CLASS__.'.GOOGLEVISITS', 'Visitcount from Google Analytics');
    }

    public function group()
    {
        return _t(__CLASS__.'.GoogleVisitsTitle', 'Google Analytics Visits report');
    }

    /**
     * @param null $params
     * @return DataList|SiteTree[]
     */
    public function sourceRecords($params = null)
    {
        return SiteTree::get()->sort('VisitCount DESC');
    }

    public function columns()
    {
        return [
            'Title'      => [
                'title' => 'Title', // todo: use NestedTitle(2)
                'link'  => true,
            ],
            'VisitCount' => 'Visits'
        ];
    }
}
