<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileManagementController extends Controller
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Get file management dashboard
     */
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $directory = $request->get('directory', 'uploads');
        
        try {
            $files = $this->fileUploadService->listFiles($directory, true);
            
            // Filter by type if specified
            if ($type !== 'all') {
                $files = array_filter($files, function ($file) use ($type) {
                    return strpos($file['path'], "uploads/{$type}/") !== false;
                });
            }
            
            // Get storage statistics
            $stats = $this->getStorageStats();
            
            return response()->json([
                'files' => array_values($files),
                'stats' => $stats,
                'allowed_types' => $this->fileUploadService->getAllowedTypes()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to retrieve files: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload files
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file',
            'type' => 'required|string|in:audio,image,document',
            'directory' => 'nullable|string|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $files = $request->file('files');
            $type = $request->input('type');
            $directory = $request->input('directory');

            $results = $this->fileUploadService->uploadMultiple($files, $type, $directory);

            return response()->json([
                'message' => 'Files uploaded successfully',
                'files' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a file
     */
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            
            // Security check - ensure path is within uploads directory
            if (!str_starts_with($path, 'uploads/')) {
                return response()->json([
                    'error' => 'Invalid file path'
                ], 403);
            }

            $deleted = $this->fileUploadService->delete($path);

            if ($deleted) {
                return response()->json([
                    'message' => 'File deleted successfully'
                ]);
            } else {
                return response()->json([
                    'error' => 'File not found or could not be deleted'
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete multiple files
     */
    public function deleteMultiple(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'paths' => 'required|array|min:1',
            'paths.*' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $paths = $request->input('paths');
            
            // Security check - ensure all paths are within uploads directory
            foreach ($paths as $path) {
                if (!str_starts_with($path, 'uploads/')) {
                    return response()->json([
                        'error' => 'Invalid file path: ' . $path
                    ], 403);
                }
            }

            $results = $this->fileUploadService->deleteMultiple($paths);
            
            $successCount = count(array_filter($results));
            $totalCount = count($results);

            return response()->json([
                'message' => "Deleted {$successCount} of {$totalCount} files",
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file information
     */
    public function fileInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $path = $request->input('path');
            
            // Security check
            if (!str_starts_with($path, 'uploads/')) {
                return response()->json([
                    'error' => 'Invalid file path'
                ], 403);
            }

            $fileInfo = $this->fileUploadService->getFileInfo($path);

            if ($fileInfo) {
                return response()->json($fileInfo);
            } else {
                return response()->json([
                    'error' => 'File not found'
                ], 404);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get file info: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clean up old files
     */
    public function cleanup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'days_old' => 'nullable|integer|min:1|max:365'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $daysOld = $request->input('days_old', 30);
            $deletedFiles = $this->fileUploadService->cleanupOldFiles($daysOld);

            return response()->json([
                'message' => 'Cleanup completed',
                'deleted_count' => count($deletedFiles),
                'deleted_files' => $deletedFiles
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Cleanup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get storage statistics
     */
    private function getStorageStats()
    {
        try {
            $totalSize = 0;
            $fileCount = 0;
            $typeStats = [];

            $allFiles = Storage::disk('public')->allFiles('uploads');

            foreach ($allFiles as $file) {
                $size = Storage::disk('public')->size($file);
                $totalSize += $size;
                $fileCount++;

                // Determine file type from path
                $pathParts = explode('/', $file);
                $type = $pathParts[1] ?? 'unknown';

                if (!isset($typeStats[$type])) {
                    $typeStats[$type] = ['count' => 0, 'size' => 0];
                }

                $typeStats[$type]['count']++;
                $typeStats[$type]['size'] += $size;
            }

            return [
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
                'file_count' => $fileCount,
                'type_stats' => $typeStats
            ];

        } catch (\Exception $e) {
            return [
                'total_size' => 0,
                'total_size_formatted' => '0 B',
                'file_count' => 0,
                'type_stats' => []
            ];
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}