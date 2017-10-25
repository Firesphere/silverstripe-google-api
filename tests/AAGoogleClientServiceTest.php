<?php

class AAGoogleClientServiceTest extends SapphireTest
{
    /**
     * We want this process to be run in a separate process
     * for constant definition reasons
     * @var bool
     */
    protected $runTestInSeparateProcess = true;
    protected $preserveGlobalState = true;


    /**
     * @expectedException LogicException
     */
    public function testException()
    {
        new GoogleClientService();
    }

    /**
     * Validate it's constructed
     */
    public function testCreation()
    {
        define('SS_ANALYTICS_KEY', Director::baseFolder() . DIRECTORY_SEPARATOR . 'google-api/tests/fixtures/test.json');
        $client = new GoogleClientService();
        $this->assertInstanceOf(Google_Client::class, $client->getClient());
    }
}
