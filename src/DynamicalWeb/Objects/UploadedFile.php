<?php

    namespace DynamicalWeb\Objects;

    use finfo;
    use RuntimeException;

    /**
     * Represents an uploaded file with memory-efficient handling
     */
    class UploadedFile
    {
        private string $name;
        private string $tmpPath;
        private int $size;
        private int $error;
        private ?string $clientMimeType;
        private ?string $detectedMimeType;
        private bool $moved;

        /**
         * UploadedFile constructor.
         *
         * @param string $name The original filename from the client
         * @param string $tmpPath The temporary path where the file is stored
         * @param int $size The file size in bytes
         * @param int $error The upload error code (e.g., UPLOAD_ERR_OK)
         * @param string|null $clientMimeType Optional. The MIME type provided by the client (less reliable than detection)
         */
        public function __construct(string $name, string $tmpPath, int $size, int $error, ?string $clientMimeType=null)
        {
            $this->name = $name;
            $this->tmpPath = $tmpPath;
            $this->size = $size;
            $this->error = $error;
            $this->clientMimeType = $clientMimeType;
            $this->detectedMimeType = null;
            $this->moved = false;

            // Detect actual MIME type from file if upload was successful
            if ($error === UPLOAD_ERR_OK && file_exists($tmpPath))
            {
                $this->detectedMimeType = $this->detectMimeType();
            }
        }

        /**
         * Get the original filename from the client
         *
         * @return string The original filename as provided by the client (may include path information depending on client)
         */
        public function getClientFilename(): string
        {
            return $this->name;
        }

        /**
         * Get the file extension from the original filename
         *
         * @return string The file extension (without dot) as provided by the client, or empty string if no extension
         */
        public function getClientExtension(): string
        {
            return pathinfo($this->name, PATHINFO_EXTENSION);
        }

        /**
         * Get the temporary path where the file is stored
         *
         * @return string The temporary file path on the server (note: this may change if the file is moved)
         */
        public function getTempPath(): string
        {
            return $this->tmpPath;
        }

        /**
         * Get the file size in bytes
         *
         * @return int The size of the uploaded file in bytes as provided by the client (note: this may not be reliable for large files or certain upload methods)
         */
        public function getSize(): int
        {
            return $this->size;
        }

        /**
         * Get the upload error code
         *
         * @return int The upload error code (e.g., UPLOAD_ERR_OK for success, or other UPLOAD_ERR_* constants for various errors)
         */
        public function getError(): int
        {
            return $this->error;
        }

        /**
         * Get human-readable error message
         *
         * @return string|null A descriptive error message if there was an upload error, or null if the upload was successful
         */
        public function getErrorMessage(): ?string
        {
            return match($this->error)
            {
                UPLOAD_ERR_OK => null,
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive in php.ini',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds the max file size directive in HTML form',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by PHP extension',
                default => 'Unknown upload error',
            };
        }

        /**
         * Check if upload was successful
         *
         * @return bool True if the file was uploaded successfully (error code UPLOAD_ERR_OK), false otherwise
         */
        public function isValid(): bool
        {
            return $this->error === UPLOAD_ERR_OK;
        }

        /**
         * Get the MIME type provided by the client
         *
         * @return string|null The MIME type as provided by the client (note: this is not reliable and should not be trusted for security-sensitive checks)
         */
        public function getClientMimeType(): ?string
        {
            return $this->clientMimeType;
        }

        /**
         * Get the detected MIME type (more reliable than client-provided)
         *
         * @return string|null The MIME type detected from the file content, or null if detection failed (note: this is more reliable than client-provided MIME type but still not foolproof)
         */
        public function getMimeType(): ?string
        {
            return $this->detectedMimeType;
        }

        /**
         * Detect MIME type from file content using finfo
         *
         * @return string|null The detected MIME type, or null if detection failed (note: this method relies on the file
         *                     being present and readable at the temporary path)
         */
        private function detectMimeType(): ?string
        {
            if (!file_exists($this->tmpPath))
            {
                return null;
            }

            if (function_exists('mime_content_type'))
            {
                return mime_content_type($this->tmpPath) ?: null;
            }

            if (class_exists('finfo'))
            {
                return (new finfo(FILEINFO_MIME_TYPE))->file($this->tmpPath) ?: null;
            }

            return null;
        }

        /**
         * Check if MIME type matches expected type(s)
         * 
         * @param string|array $mimeTypes MIME type(s) to check against (e.g., 'image/jpeg' or ['image/jpeg', 'image/png'])
         */
        public function isMimeType(string|array $mimeTypes): bool
        {
            if (!$this->detectedMimeType)
            {
                return false;
            }

            $mimeTypes = (array)$mimeTypes;
            
            foreach ($mimeTypes as $type)
            {
                // Support wildcards like 'image/*'
                if (str_ends_with($type, '/*'))
                {
                    $prefix = substr($type, 0, -2);
                    if (str_starts_with($this->detectedMimeType, $prefix . '/'))
                    {
                        return true;
                    }
                }
                elseif ($this->detectedMimeType === $type)
                {
                    return true;
                }
            }

            return false;
        }

        /**
         * Check if file is an image
         *
         * @return bool True if the detected MIME type indicates an image (e.g., 'image/jpeg', 'image/png', etc.), false otherwise
         */
        public function isImage(): bool
        {
            return $this->isMimeType('image/*');
        }

        /**
         * Check if file size is within limit
         * 
         * @param int $maxSize Maximum size in bytes
         */
        public function isSizeWithinLimit(int $maxSize): bool
        {
            return $this->size <= $maxSize;
        }

        /**
         * Check if file extension is in allowed list
         * 
         * @param array $allowedExtensions List of allowed extensions (without dot)
         * @param bool $caseSensitive Whether to perform case-sensitive comparison
         */
        public function hasAllowedExtension(array $allowedExtensions, bool $caseSensitive=false): bool
        {
            $extension = $this->getClientExtension();
            
            if (!$caseSensitive)
            {
                $extension = strtolower($extension);
                $allowedExtensions = array_map('strtolower', $allowedExtensions);
            }

            return in_array($extension, $allowedExtensions, true);
        }

        /**
         * Move uploaded file to permanent location
         * 
         * @param string $destination Target path
         * @param bool $overwrite Whether to overwrite existing file
         * @return bool Success status
         */
        public function moveTo(string $destination, bool $overwrite=false): bool
        {
            if ($this->moved)
            {
                throw new RuntimeException('File has already been moved');
            }

            if (!$this->isValid())
            {
                throw new RuntimeException('Cannot move invalid upload: ' . $this->getErrorMessage());
            }

            if (!$overwrite && file_exists($destination))
            {
                throw new RuntimeException('Destination file already exists');
            }

            // Create directory if it doesn't exist
            $directory = dirname($destination);
            if (!is_dir($directory))
            {
                if (!mkdir($directory, 0755, true))
                {
                    throw new RuntimeException('Failed to create destination directory');
                }
            }

            // Use move_uploaded_file for security
            if (is_uploaded_file($this->tmpPath))
            {
                $result = move_uploaded_file($this->tmpPath, $destination);
            }
            else
            {
                // Fallback for testing or non-standard uploads
                $result = rename($this->tmpPath, $destination);
            }

            if ($result)
            {
                $this->moved = true;
                $this->tmpPath = $destination;
            }

            return $result;
        }

        /**
         * Check if file has been moved
         *
         * @return bool True if the file has been moved to a new location, false otherwise (note: once moved, the
         *              temporary path is updated to the new location)
         */
        public function isMoved(): bool
        {
            return $this->moved;
        }

        /**
         * Get file contents (USE WITH CAUTION for large files)
         * 
         * @param int $maxSize Maximum size to read (default 10MB)
         * @return string|null File contents or null if too large
         */
        public function getContents(int $maxSize = 10485760): ?string
        {
            if (!$this->isValid())
            {
                return null;
            }

            $actualSize = filesize($this->tmpPath);
            if ($actualSize === false || $actualSize > $maxSize)
            {
                throw new RuntimeException('File too large to read into memory. Use getStream() instead.');
            }

            return file_get_contents($this->tmpPath);
        }

        /**
         * Get a stream resource for memory-efficient reading
         * 
         * @param string $mode File open mode (default 'rb')
         * @return resource|false Stream resource or false on failure
         */
        public function getStream(string $mode = 'rb')
        {
            if (!$this->isValid())
            {
                return false;
            }

            return fopen($this->tmpPath, $mode);
        }

        /**
         * Calculate file hash (memory-efficient for large files)
         * 
         * @param string $algorithm Hash algorithm (e.g., 'md5', 'sha256')
         */
        public function getHash(string $algorithm = 'sha256'): ?string
        {
            if (!$this->isValid())
            {
                return null;
            }

            return hash_file($algorithm, $this->tmpPath) ?: null;
        }

        /**
         * Get file as array with all information
         */
        public function toArray(): array
        {
            return [
                'name' => $this->name,
                'size' => $this->size,
                'extension' => $this->getClientExtension(),
                'mime_type' => $this->detectedMimeType,
                'client_mime_type' => $this->clientMimeType,
                'error' => $this->error,
                'error_message' => $this->getErrorMessage(),
                'is_valid' => $this->isValid(),
                'is_moved' => $this->moved,
            ];
        }
    }

