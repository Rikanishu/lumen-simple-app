<?php

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;

abstract class TestCase extends \Laravel\Lumen\Testing\TestCase
{


    /**
     * Creates the application.
     *
     * @return \Laravel\Lumen\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';
        $this->clearDB();
        return $app;
    }

    protected function clearDB()
    {
        \Illuminate\Support\Facades\DB::table('clients')->delete();
        \Illuminate\Support\Facades\DB::table('currencies')->delete();
        \Illuminate\Support\Facades\DB::table('events')->delete();
        \Illuminate\Support\Facades\DB::table('rates_on_dates')->delete();
    }

    protected function seeJsonHasValidationErrors($errors)
    {
        $json = $this->getResponseJson();
        $this->assertTrue(isset($json['success']), 'The response has no success key');
        $this->assertFalse($json['success'], 'The response has success key equal true, should be false');

        $this->assertTrue(!empty($json['errors']));
        foreach ($errors as $error) {
            $this->assertTrue(!empty($json['errors'][$error]), 'The response has no key for error ' . $error);
        }
    }

    protected function seeJsonHasSuccessResponse()
    {
        $json = $this->getResponseJson();
        $this->assertTrue(isset($json['success']), 'The response has no success key');
        $this->assertTrue($json['success'], 'The response has success key equal false, should be true');
    }

    protected function seeJsonHasException($message = null)
    {
        $json = $this->getResponseJson();
        $this->assertTrue(isset($json['success']), 'The response has no success key');
        $this->assertFalse($json['success'], 'The response has success key equal true, should be false');
        $this->assertTrue(isset($json['exception']), 'The response has no exception key');
        if ($message !== null) {
            $this->assertTrue($message === $json['exception'], 'Exception messages are not equal, got ' . $json['exception']);
        }
    }
    protected function seeJsonHasExceptionSubstr($message = null)
    {
        $json = $this->getResponseJson();
        $this->assertTrue(isset($json['success']), 'The response has no success key');
        $this->assertFalse($json['success'], 'The response has success key equal true, should be false');
        $this->assertTrue(isset($json['exception']), 'The response has no exception key');
        if ($message !== null) {
            $this->assertTrue(stripos($json['exception'], $message) !== false, 'Exception has no such substring, got ' . $json['exception']);
        }
    }

    protected function getResponseJson()
    {
        $json = json_decode($this->response->getContent(), true);
        return $json;
    }
}
