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

    public function test_field_mapping_skips_null_values_for_conditional_fields()
    {
        $capturedData = null;
        
        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('withHeaders')->andReturnSelf();
        Http::shouldReceive('post')->andReturnUsing(function ($url, $data) use (&$capturedData) {
            $capturedData = $data;
            $response = \Mockery::mock(\Illuminate\Http\Client\Response::class);
            $response->shouldReceive('successful')->andReturn(true);
            $response->shouldReceive('status')->andReturn(200);
            $response->transferStats = null;
            return $response;
        });
        
        Log::shouldReceive('info')->once();

        $connector = new WebhookConnector;
        $submission = $this->createMockSubmissionWithData([
            'residential_service' => 'lawn_care',
            'commercial_service' => null,
            'retirement_service' => '',
        ]);
        
        // Map all three conditional fields to the same webhook key
        // Only the non-empty value should be used
        $connector->process($submission, [
            'url' => 'https://example.com/webhook',
            'field_mapping' => [
                ['form_field' => 'residential_service', 'webhook_key' => 'service_type'],
                ['form_field' => 'commercial_service', 'webhook_key' => 'service_type'],
                ['form_field' => 'retirement_service', 'webhook_key' => 'service_type'],
            ],
        ]);
        
        $this->assertEquals('lawn_care', $capturedData['service_type']);
    }

    public function test_field_mapping_uses_first_non_empty_value()
    {
        $capturedData = null;
        
        Http::shouldReceive('timeout')->andReturnSelf();
        Http::shouldReceive('withHeaders')->andReturnSelf();
        Http::shouldReceive('post')->andReturnUsing(function ($url, $data) use (&$capturedData) {
            $capturedData = $data;
            $response = \Mockery::mock(\Illuminate\Http\Client\Response::class);
            $response->shouldReceive('successful')->andReturn(true);
            $response->shouldReceive('status')->andReturn(200);
            $response->transferStats = null;
            return $response;
        });
        
        Log::shouldReceive('info')->once();

        $connector = new WebhookConnector;
        // This time commercial_service has the value
        $submission = $this->createMockSubmissionWithData([
            'residential_service' => null,
            'commercial_service' => 'office_cleaning',
            'retirement_service' => '',
        ]);
        
        $connector->process($submission, [
            'url' => 'https://example.com/webhook',
            'field_mapping' => [
                ['form_field' => 'residential_service', 'webhook_key' => 'service_type'],
                ['form_field' => 'commercial_service', 'webhook_key' => 'service_type'],
                ['form_field' => 'retirement_service', 'webhook_key' => 'service_type'],
            ],
        ]);
        
        $this->assertEquals('office_cleaning', $capturedData['service_type']);
    }

    private function createMockSubmission()
    {
        return $this->createMockSubmissionWithData([]);
    }

    private function createMockSubmissionWithData(array $data)
    {
        $submission = \Mockery::mock(Submission::class);
        $form = \Mockery::mock(\Statamic\Forms\Form::class);
        
        $form->shouldReceive('handle')->andReturn('test_form');
        $submission->shouldReceive('form')->andReturn($form);
        $submission->shouldReceive('id')->andReturn('test_id');
        $submission->shouldReceive('date')->andReturn(now());
        $submission->shouldReceive('data')->andReturn($data);
        
        $submission->shouldReceive('has')->andReturnUsing(function ($key) use ($data) {
            return array_key_exists($key, $data);
        });
        $submission->shouldReceive('get')->andReturnUsing(function ($key) use ($data) {
            return $data[$key] ?? null;
        });
        
        return $submission;
    }
}
