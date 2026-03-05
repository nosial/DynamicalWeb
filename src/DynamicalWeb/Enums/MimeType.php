<?php

    namespace DynamicalWeb\Enums;

    enum MimeType: string
    {
        // Text
        case HTML = 'text/html';
        case CSS = 'text/css';
        case JAVASCRIPT = 'application/javascript';
        case JSON = 'application/json';
        case YAML = 'application/x-yaml';
        case XML = 'application/xml';
        case TEXT = 'text/plain';
        case CSV = 'text/csv';
        
        // Images
        case JPEG = 'image/jpeg';
        case PNG = 'image/png';
        case GIF = 'image/gif';
        case SVG = 'image/svg+xml';
        case ICO = 'image/x-icon';
        case WEBP = 'image/webp';
        case BMP = 'image/bmp';
        case TIFF = 'image/tiff';
        
        // Fonts
        case WOFF = 'font/woff';
        case WOFF2 = 'font/woff2';
        case TTF = 'font/ttf';
        case OTF = 'font/otf';
        case EOT = 'application/vnd.ms-fontobject';
        
        // Documents
        case PDF = 'application/pdf';
        case DOC = 'application/msword';
        case DOCX = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        case XLS = 'application/vnd.ms-excel';
        case XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        case PPT = 'application/vnd.ms-powerpoint';
        case PPTX = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        
        // Archives
        case ZIP = 'application/zip';
        case TAR = 'application/x-tar';
        case GZIP = 'application/gzip';
        case BZIP2 = 'application/x-bzip2';
        case RAR = 'application/vnd.rar';
        case SEVEN_ZIP = 'application/x-7z-compressed';
        
        // Audio
        case MP3 = 'audio/mpeg';
        case WAV = 'audio/wav';
        case OGG = 'audio/ogg';
        case AAC = 'audio/aac';
        case FLAC = 'audio/flac';
        
        // Video
        case MP4 = 'video/mp4';
        case WEBM = 'video/webm';
        case AVI = 'video/x-msvideo';
        case MPEG = 'video/mpeg';
        case MOV = 'video/quicktime';
        
        // Other
        case OCTET_STREAM = 'application/octet-stream';

        /**
         * Attempts to determine the MIME type based on a file extension.
         * If the extension is not recognized, it defaults to application/octet-stream.
         *
         * @param string $extension The file extension (e.g., "jpg", "pdf", "html")
         * @return self The corresponding MimeType enum value
         */
        public static function fromExtension(string $extension): self
        {
            $extension = strtolower($extension);
            
            return match($extension)
            {
                // Text
                'html', 'htm' => self::HTML,
                'css' => self::CSS,
                'js', 'mjs' => self::JAVASCRIPT,
                'json' => self::JSON,
                'yaml', 'yml' => self::YAML,
                'xml' => self::XML,
                'txt', 'text' => self::TEXT,
                'csv' => self::CSV,
                
                // Images
                'jpg', 'jpeg' => self::JPEG,
                'png' => self::PNG,
                'gif' => self::GIF,
                'svg', 'svgz' => self::SVG,
                'ico' => self::ICO,
                'webp' => self::WEBP,
                'bmp' => self::BMP,
                'tif', 'tiff' => self::TIFF,
                
                // Fonts
                'woff' => self::WOFF,
                'woff2' => self::WOFF2,
                'ttf' => self::TTF,
                'otf' => self::OTF,
                'eot' => self::EOT,
                
                // Documents
                'pdf' => self::PDF,
                'doc' => self::DOC,
                'docx' => self::DOCX,
                'xls' => self::XLS,
                'xlsx' => self::XLSX,
                'ppt' => self::PPT,
                'pptx' => self::PPTX,
                
                // Archives
                'zip' => self::ZIP,
                'tar' => self::TAR,
                'gz', 'gzip' => self::GZIP,
                'bz2', 'bzip2' => self::BZIP2,
                'rar' => self::RAR,
                '7z' => self::SEVEN_ZIP,
                
                // Audio
                'mp3' => self::MP3,
                'wav' => self::WAV,
                'ogg', 'oga' => self::OGG,
                'aac' => self::AAC,
                'flac' => self::FLAC,
                
                // Video
                'mp4', 'm4v' => self::MP4,
                'webm' => self::WEBM,
                'avi' => self::AVI,
                'mpg', 'mpeg' => self::MPEG,
                'mov' => self::MOV,
                
                default => self::OCTET_STREAM,
            };
        }
    }
