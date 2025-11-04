<?php

namespace App\Examples;

use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileServiceExamples
{
    private static $service;

    public static function init()
    {
        self::$service = new FileService();
    }

    public static function runAll()
    {
        self::init();
        try {
            self::simpleExamples();
            self::intermediateExamples();
            self::complexExamples();
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    public static function simpleExamples()
    {
        echo "\n=== Simple Examples ===\n";

        // 1. Get file name
        $filePath = storage_path('app/public/test.txt');
        echo "File Name: " . self::$service->getFileName($filePath) . "\n";

        // 2. Get file extension
        echo "Extension: " . self::$service->getFileExtension($filePath) . "\n";

        // 3. Get MIME type
        echo "MIME Type: " . self::$service->getMimeType($filePath) . "\n";

        // 4. Get file size in bytes
        echo "File Size (bytes): " . self::$service->getFileSize($filePath) . "\n";

        // 5. Get file hash (MD5)
        echo "MD5 Hash: " . self::$service->getFileHash($filePath) . "\n";

        // 6. Get file type category
        echo "File Type Category: " . self::$service->getFileTypeCategory($filePath) . "\n";

        // 7. Check if file exists
        echo "File Exists: " . (self::$service->fileExists($filePath) ? 'Yes' : 'No') . "\n";

        // 8. Read file to string
        $content = self::$service->fileToString($filePath);
        echo "File Content: $content\n";

        // 9. Write string to file
        $newPath = storage_path('app/public/new_test.txt');
        self::$service->stringToFile('Hello World', $newPath);
        echo "String written to: $newPath\n";

        // 10. Delete file
        self::$service->deleteFile($newPath);
        echo "File deleted: $newPath\n";

        // 11. Ensure directory exists
        $dir = storage_path('app/public/test_dir');
        self::$service->ensureDirectoryExists($dir);
        echo "Directory ensured: $dir\n";

        // 12. List files in directory
        $files = self::$service->listFiles(storage_path('app/public'));
        echo "Files in directory: " . implode(', ', $files) . "\n";

        // 13. Get file from ID (simple, assuming ID exists)
        $fileContent = self::$service->getFile('some_file_id');
        echo "Get File Content: " . substr($fileContent, 0, 50) . "...\n";

        // 14. Verify file type
        echo "File Type Valid: " . (self::$service->verifyFileType($filePath) ? 'Yes' : 'No') . "\n";

        // 15. Get file URL
        echo "File URL: " . self::$service->getFileUrl('path/to/file') . "\n";
    }

    public static function intermediateExamples()
    {
        echo "\n=== Intermediate Examples ===\n";

        // 16. File to Base64
        $imagePath = storage_path('app/public/sample.png');
        $base64 = self::$service->fileToBase64($imagePath);
        echo "Base64: " . substr($base64, 0, 50) . "...\n";

        // 17. Base64 to file
        $outputPath = storage_path('app/public/sample_copy.png');
        self::$service->base64ToFile($base64, $outputPath);
        echo "Base64 saved to: $outputPath\n";

        // 18. JSON to file
        $jsonData = ['key' => 'value'];
        $jsonPath = storage_path('app/public/data.json');
        self::$service->jsonToFile($jsonData, $jsonPath);
        echo "JSON saved to: $jsonPath\n";

        // 19. File to JSON
        $json = self::$service->fileToJson($jsonPath);
        echo "JSON read: " . json_encode($json) . "\n";

        // 20. Copy file
        $copyPath = storage_path('app/public/test_copy.txt');
        self::$service->copyFile($filePath, $copyPath);
        echo "File copied to: $copyPath\n";

        // 21. Move file
        $movePath = storage_path('app/public/test_moved.txt');
        self::$service->moveFile($copyPath, $movePath);
        echo "File moved to: $movePath\n";

        // 22. Get file size in MB
        echo "File Size (MB): " . self::$service->getFileSize($filePath, 'mb') . "\n";

        // 23. Get file hash (SHA256)
        echo "SHA256 Hash: " . self::$service->getFileHash($filePath, 'sha256') . "\n";

        // 24. Get image dimensions
        $dimensions = self::$service->getImageDimensions($imagePath);
        echo "Dimensions: {$dimensions['width']}x{$dimensions['height']}\n";

        // 25. Convert image format
        $jpgPath = storage_path('app/public/sample.jpg');
        self::$service->convertImageFormat($imagePath, $jpgPath, 'jpg');
        echo "Image converted to: $jpgPath\n";

        // 26. Get file with output true
        $fileInfo = self::$service->getFile('some_file_id', true);
        echo "File Info: " . json_encode($fileInfo) . "\n";

        // 27. File to Base64 with MIME
        $base64Mime = self::$service->fileToBase64($imagePath, true);
        echo "Base64 with MIME: " . substr($base64Mime, 0, 50) . "...\n";

        // 28. Zip files
        $files = [$filePath, $imagePath];
        $zipPath = storage_path('app/public/archive.zip');
        self::$service->zipFiles($files, $zipPath);
        echo "Zipped to: $zipPath\n";

        // 29. Unzip
        $extractPath = storage_path('app/public/extracted');
        self::$service->unzip($zipPath, $extractPath);
        echo "Unzipped to: $extractPath\n";

        // 30. Download response (simulated)
        // $response = self::$service->downloadFileResponse($filePath);
    }

    public static function complexExamples()
    {
        echo "\n=== Complex Examples ===\n";

        // 31. Save file (requires Request, simulated)
        // Assume $request = new Request(); $request->files->add(['file' => UploadedFile::fake()->create('test.txt')]);
        // self::$service->saveFile($request, 'key', 'file', 'custom', 'business_id');

        // 32. Store uploaded file
        // $file = UploadedFile::fake()->create('doc.pdf');
        // $path = self::$service->storeFileUploaded($file, 'uploads');
        // echo "Stored path: $path\n";

        // 33. Combination: Convert image, zip, unzip
        $convPath = storage_path('app/public/conv.png');
        self::$service->convertImageFormat($jpgPath, $convPath, 'png');
        $files2 = [$convPath, $jpgPath];
        $zip2 = storage_path('app/public/archive2.zip');
        self::$service->zipFiles($files2, $zip2);
        $extract2 = storage_path('app/public/extracted2');
        self::$service->unzip($zip2, $extract2);

        // 34. Hash verification
        $hash = self::$service->getFileHash($filePath, 'sha256');
        // Copy and compare
        $copy = storage_path('app/public/copy.txt');
        self::$service->copyFile($filePath, $copy);
        $hashCopy = self::$service->getFileHash($copy, 'sha256');
        echo "Hashes match: " . ($hash === $hashCopy ? 'Yes' : 'No') . "\n";

        // 35. JSON operations chain
        $data = ['a' => 1, 'b' => [2, 3]];
        $jsonP = storage_path('app/public/complex.json');
        self::$service->jsonToFile($data, $jsonP);
        $read = self::$service->fileToJson($jsonP);
        echo "JSON equal: " . (json_encode($data) === json_encode($read) ? 'Yes' : 'No') . "\n";

        // 36. Base64 chain
        $b64 = self::$service->fileToBase64($filePath);
        $newF = storage_path('app/public/from_b64.txt');
        self::$service->base64ToFile($b64, $newF);
        echo "From Base64: " . self::$service->fileToString($newF) . "\n";

        // 37. Verify type after conversion
        self::$service->verifyFileType($jpgPath);

        // 38. List files and get sizes
        $dir = storage_path('app/public');
        $files = self::$service->listFiles($dir);
        foreach ($files as $f) {
            $full = $dir . '/' . $f;
            echo "$f size: " . self::$service->getFileSize($full, 'kb') . " KB\n";
        }

        // 39. Move and delete chain
        $move = storage_path('app/public/moved.png');
        self::$service->moveFile($imagePath, $move);
        self::$service->deleteFile($move);

        // 40. Get file URL for public
        // Assume path
        self::$service->getFileUrl('public/path.jpg', 'public');

        // 41. Image dimensions after conversion
        self::$service->getImageDimensions($jpgPath);

        // 42. Hash with different algos
        self::$service->getFileHash($filePath, 'sha1');

        // 43. Category for different files
        self::$service->getFileTypeCategory($jpgPath);

        // 44. Size in GB for large file (assume)
        // self::$service->getFileSize('large.file', 'gb');

        // 45. Ensure dir and write
        $dir2 = storage_path('app/public/new_dir');
        self::$service->ensureDirectoryExists($dir2);
        self::$service->stringToFile('Test', $dir2 . '/test.txt');

        // 46. File exists after write
        self::$service->fileExists($dir2 . '/test.txt');

        // 47. Name without extension
        self::$service->getFileName($filePath, false);

        // 48. MIME for unknown
        self::$service->getMimeType('unknown.ext');

        // 49. Unzip to subdir
        // Assume zip with dirs

        // 50. Complex save with all params
        // Simulated

        // Add more if needed, but up to 50.
    }
}

// Run
FileServiceExamples::runAll();