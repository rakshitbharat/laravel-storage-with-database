<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Tests\Unit;

use Rakshitbharat\LaravelStorageWithDatabase\Tests\TestCase;
use Rakshitbharat\LaravelStorageWithDatabase\Exceptions\StorageDatabaseException;

class StorageDatabaseExceptionTest extends TestCase
{
    /** @test */
    public function it_creates_invalid_configuration_exception()
    {
        $exception = StorageDatabaseException::invalidConfiguration('Missing config');
        
        $this->assertEquals('Invalid storage configuration. Missing config', $exception->getMessage());
        $this->assertEquals('configuration_error', $exception->getData()['type']);
    }

    /** @test */
    public function it_creates_invalid_mime_type_exception()
    {
        $allowedTypes = ['text/plain', 'text/html'];
        $exception = StorageDatabaseException::invalidMimeType('application/json', $allowedTypes);
        
        $this->assertStringContainsString('Invalid MIME type: application/json', $exception->getMessage());
        $this->assertEquals('mime_type_error', $exception->getData()['type']);
        $this->assertEquals('application/json', $exception->getData()['mime_type']);
        $this->assertEquals($allowedTypes, $exception->getData()['allowed_types']);
    }

    /** @test */
    public function it_creates_content_too_large_exception()
    {
        $exception = StorageDatabaseException::contentTooLarge(1024, 512);
        
        $this->assertStringContainsString('Content size (1024 bytes) exceeds maximum', $exception->getMessage());
        $this->assertEquals('content_size_error', $exception->getData()['type']);
        $this->assertEquals(1024, $exception->getData()['content_size']);
        $this->assertEquals(512, $exception->getData()['max_size']);
    }

    /** @test */
    public function it_creates_path_too_long_exception()
    {
        $longPath = str_repeat('a', 300);
        $maxLength = 255;
        
        $exception = StorageDatabaseException::pathTooLong($longPath, $maxLength);
        
        $this->assertStringContainsString('Path length (300) exceeds maximum', $exception->getMessage());
        $this->assertEquals('path_length_error', $exception->getData()['type']);
        $this->assertEquals($longPath, $exception->getData()['path']);
        $this->assertEquals($maxLength, $exception->getData()['max_length']);
    }

    /** @test */
    public function it_creates_database_error_exception()
    {
        $previous = new \Exception('Database connection failed');
        $exception = StorageDatabaseException::databaseError('insert', $previous);
        
        $this->assertStringContainsString('Database operation failed: insert', $exception->getMessage());
        $this->assertEquals('database_error', $exception->getData()['type']);
        $this->assertEquals('insert', $exception->getData()['operation']);
        $this->assertEquals($previous, $exception->getPrevious());
    }

    /** @test */
    public function it_creates_monitoring_not_enabled_exception()
    {
        $exception = StorageDatabaseException::monitoringNotEnabled();
        
        $this->assertEquals('Storage monitoring is not enabled in configuration', $exception->getMessage());
        $this->assertEquals('monitoring_disabled', $exception->getData()['type']);
    }

    /** @test */
    public function it_creates_batch_operation_failed_exception()
    {
        $failedItems = [
            ['path' => 'file1.txt', 'error' => 'File too large'],
            ['path' => 'file2.txt', 'error' => 'Invalid mime type'],
        ];
        
        $exception = StorageDatabaseException::batchOperationFailed('putMany', $failedItems);
        
        $this->assertStringContainsString('Batch putMany operation failed', $exception->getMessage());
        $this->assertEquals('batch_operation_error', $exception->getData()['type']);
        $this->assertEquals('putMany', $exception->getData()['operation']);
        $this->assertEquals($failedItems, $exception->getData()['failed_items']);
    }

    /** @test */
    public function it_creates_invalid_operation_exception()
    {
        $exception = StorageDatabaseException::invalidOperation('unknownOperation');
        
        $this->assertStringContainsString('Invalid storage operation: unknownOperation', $exception->getMessage());
        $this->assertEquals('invalid_operation', $exception->getData()['type']);
        $this->assertEquals('unknownOperation', $exception->getData()['operation']);
    }
}