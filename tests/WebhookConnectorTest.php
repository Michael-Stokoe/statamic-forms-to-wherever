<?php

namespace Stokoe\FormsToWherever\Tests;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Stokoe\FormsToWherever\Connectors\WebhookConnector;
use Statamic\Forms\Submission;

class WebhookConnectorTest extends TestCase
{
    public function test_it_handles_http_exceptions_gracefully()
    {
        Log::shouldReceive('error')->once();
        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('post')->andThrow(new \Exception('Connection failed'));

        $connector = new WebhookConnector;
        $submission = $this->createMockSubmission();
        
        // Should not throw exception
        $connector->process($submission, ['url' => 'https://example.com/webhook']);
        
        $this->assertTrue(true); // Test passes if no exception thrown
    }

    public function test_it_validates_urls()
    {
        Log::shouldReceive('warning')->once();
        
        $connector = new WebhookConnector;
        $submission = $this->createMockSubmission();
        
        // Should log warning and return early for invalid URL
        $connector->process($submission, ['url' => 'invalid-url']);
        
        $this->assertTrue(true);
    }

    private function createMockSubmission()
    {
        $submission = \Mockery::mock(Submission::class);
        $form = \Mockery::mock(\Statamic\Forms\Form::class);
        
        $form->shouldReceive('handle')->andReturn('test_form');
        $submission->shouldReceive('form')->andReturn($form);
        $submission->shouldReceive('id')->andReturn('test_id');
        $submission->shouldReceive('date')->andReturn(now());
        $submission->shouldReceive('data')->andReturn([]);
        
        return $submission;
    }
}
