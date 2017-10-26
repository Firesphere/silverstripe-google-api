<?php

/**
 * Class AnalyticsUpdateTaskTest
 * Add some dummy tests to up coverage O-)
 */
class AnalyticsUpdateTaskTest extends SapphireTest
{
    public function testGetTitle()
    {
        $task = AnalyticsUpdateTask::create();
        $this->assertEquals('Update Google Analytics information for the configured pages', $task->getTitle());
    }
}
