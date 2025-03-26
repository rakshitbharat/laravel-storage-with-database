<?php

namespace Rakshitbharat\LaravelStorageWithDatabase\Exceptions;

use Exception;

class StorageDatabaseException extends Exception
{
    protected array $data = [];

    public function __construct(string $message = "", array $data = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public static function invalidConfiguration(string $details = ""): self
    {
        return new static(
            "Invalid storage configuration. {$details}",
            ['type' => 'configuration_error']
        );
    }

    public static function invalidMimeType(string $mimeType, array $allowedTypes): self
    {
        return new static(
            "Invalid MIME type: {$mimeType}. Allowed types: " . implode(', ', $allowedTypes),
            [
                'type' => 'mime_type_error',
                'mime_type' => $mimeType,
                'allowed_types' => $allowedTypes,
            ]
        );
    }

    public static function contentTooLarge(int $size, int $maxSize): self
    {
        return new static(
            "Content size ({$size} bytes) exceeds maximum allowed size ({$maxSize} bytes)",
            [
                'type' => 'content_size_error',
                'content_size' => $size,
                'max_size' => $maxSize,
            ]
        );
    }

    public static function pathTooLong(string $path, int $maxLength): self
    {
        return new static(
            "Path length (" . strlen($path) . ") exceeds maximum allowed length ({$maxLength})",
            [
                'type' => 'path_length_error',
                'path' => $path,
                'max_length' => $maxLength,
            ]
        );
    }

    public static function databaseError(string $operation, \Throwable $previous): self
    {
        return new static(
            "Database operation failed: {$operation}. {$previous->getMessage()}",
            [
                'type' => 'database_error',
                'operation' => $operation,
            ],
            0,
            $previous
        );
    }

    public static function monitoringNotEnabled(): self
    {
        return new static(
            "Storage monitoring is not enabled in configuration",
            ['type' => 'monitoring_disabled']
        );
    }

    public static function batchOperationFailed(string $operation, array $failed): self
    {
        return new static(
            "Batch {$operation} operation failed for some items",
            [
                'type' => 'batch_operation_error',
                'operation' => $operation,
                'failed_items' => $failed,
            ]
        );
    }

    public static function invalidOperation(string $operation): self
    {
        return new static(
            "Invalid storage operation: {$operation}",
            [
                'type' => 'invalid_operation',
                'operation' => $operation,
            ]
        );
    }
}