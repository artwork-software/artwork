<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Modules\GeneralSettings\Services\GeneralSettingsService;
use Artwork\Modules\System\FileHandling\MimeTypeList;
use Artwork\Modules\System\FileHandling\Service\FileHandlingFrontendService;

class FileSettingsController extends Controller
{
    public function __construct(private readonly FileHandlingFrontendService $fileHandlingFrontendService)
    {
    }
    
    public function index(): \Inertia\Response|\Inertia\ResponseFactory
    {
        $areas = [];
        foreach(ArtworkFileTypes::cases() as $fileType) {
            $areas[] = $this->fileHandlingFrontendService->createFileHandingDto($fileType);
        }
        return \inertia('System/FileSettings/Index', [
            'areas' => $areas,
            'imageFileTypes' => array_keys(MimeTypeList::IMAGE_MIME_TYPES),
            'otherFileTypes' => array_keys(MimeTypeList::MIME_TYPES)
        ]);
    }
}