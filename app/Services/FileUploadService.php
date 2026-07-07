<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class FileUploadService
{
    /**
     * Allowed file types and their configurations
     */
    private const ALLOWED_TYPES = [
        'audio' => [
            'extensions' => ['mp3', 'wav', 'ogg', 'm4a'],
            'max_size' => 50 * 1024 * 1024, // 50MB
            'mime_types' => ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp4']
        ],
        'image' => [
            'extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            'max_size' => 10 * 1024 * 1024, // 10MB
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
        ],
        'document' => [
            'extensions' => ['pdf', 'doc', 'docx', 'txt'],
            'max_size' => 20 * 1024 * 1024, // 20MB
            'mime_types' => ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain']
        ]
    ];

    /**
     * Upload a file with validation
     */
    public function upload(UploadedFile $file, string $type, string $directory = null): array
    {
        // Validate file type
        $this->validateFile($file, $type);
        
        // Generate unique filename
        $filename = $this->generateFilename($file);
        
        // Determine storage path
        $path = $this->getStoragePath($type, $directory);
        
        // Store the file
        $storedPath = $file->storeAs($path, $filename, 'public');
        
        if (!$storedPath) {
            throw new Exception('Failed to store file');
        }
        
        return [
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'path' => $storedPath,
            'url' => Storage::url($storedPath),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'type' => $type
        ];
    }

    /**
     * Upload multiple files
     */
    public function uploadMultiple(array $files, string $type, string $directory = null): array
    {
        $results = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $results[] = $this->upload($file, $type, $directory);
            }
        }
        
        return $results;
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        
        return false;
    }

    /**
     * Delete multiple files
     */
    public function deleteMultiple(array $paths): array
    {
        $results = [];
        
        foreach ($paths as $path) {
            $results[$path] = $this->delete($path);
        }
        
        return $results;
    }

    /**
     * Get file information
     */
    public function getFileInfo(string $path): ?array
    {
        if (!Storage::disk('public')->exists($path)) {
            return null;
        }
        
        return [
            'path' => $path,
            'url' => Storage::url($path),
            'size' => Storage::disk('public')->size($path),
            'last_modified' => Storage::disk('public')->lastModified($path),
            'exists' => true
        ];
    }

    /**
     * List files in directory
     */
    public function listFiles(string $directory, bool $recursive = false): array
    {
        $method = $recursive ? 'allFiles' : 'files';
        $files = Storage::disk('public')->$method($directory);
        
        return array_map(function ($file) {
            return [
                'path' => $file,
                'url' => Storage::url($file),
                'size' => Storage::disk('public')->size($file),
                'last_modified' => Storage::disk('public')->lastModified($file)
            ];
        }, $files);
    }

    /**
     * Validate uploaded file
     */
    private function validateFile(UploadedFile $file, string $type): void
    {
        if (!isset(self::ALLOWED_TYPES[$type])) {
            throw new Exception("Invalid file type: {$type}");
        }
        
        $config = self::ALLOWED_TYPES[$type];
        
        // Check file size
        if ($file->getSize() > $config['max_size']) {
            $maxSizeMB = $config['max_size'] / (1024 * 1024);
            throw new Exception("File size exceeds maximum allowed size of {$maxSizeMB}MB");
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $config['extensions'])) {
            $allowedExtensions = implode(', ', $config['extensions']);
            throw new Exception("Invalid file extension. Allowed extensions: {$allowedExtensions}");
        }
        
        // Check MIME type
        if (!in_array($file->getMimeType(), $config['mime_types'])) {
            throw new Exception("Invalid file type. File appears to be corrupted or not of the expected type.");
        }
        
        // Additional security checks
        $this->performSecurityChecks($file);
    }

    /**
     * Perform additional security checks
     */
    private function performSecurityChecks(UploadedFile $file): void
    {
        // Check for executable files
        $dangerousExtensions = ['php', 'exe', 'bat', 'sh', 'cmd', 'scr', 'pif', 'jar'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $dangerousExtensions)) {
            throw new Exception("Executable files are not allowed");
        }
        
        // Check file content for PHP tags (basic check)
        $content = file_get_contents($file->getPathname());
        if (strpos($content, '<?php') !== false || strpos($content, '<?=') !== false) {
            throw new Exception("Files containing PHP code are not allowed");
        }
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);
        
        return "{$timestamp}_{$random}.{$extension}";
    }

    /**
     * Get storage path for file type
     */
    private function getStoragePath(string $type, ?string $directory): string
    {
        $basePath = "uploads/{$type}";
        
        if ($directory) {
            $basePath .= "/{$directory}";
        }
        
        // Add date-based subdirectory
        $basePath .= '/' . now()->format('Y/m');
        
        return $basePath;
    }

    /**
     * Get allowed file types configuration
     */
    public function getAllowedTypes(): array
    {
        return self::ALLOWED_TYPES;
    }

    /**
     * Get maximum file size for type
     */
    public function getMaxFileSize(string $type): int
    {
        return self::ALLOWED_TYPES[$type]['max_size'] ?? 0;
    }

    /**
     * Get allowed extensions for type
     */
    public function getAllowedExtensions(string $type): array
    {
        return self::ALLOWED_TYPES[$type]['extensions'] ?? [];
    }

    /**
     * Clean up old files (for maintenance)
     */
    public function cleanupOldFiles(int $daysOld = 30): array
    {
        $cutoffDate = now()->subDays($daysOld);
        $deletedFiles = [];
        
        $allFiles = Storage::disk('public')->allFiles('uploads');
        
        foreach ($allFiles as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            
            if ($lastModified < $cutoffDate->timestamp) {
                if (Storage::disk('public')->delete($file)) {
                    $deletedFiles[] = $file;
                }
            }
        }
        
        return $deletedFiles;
    }
}