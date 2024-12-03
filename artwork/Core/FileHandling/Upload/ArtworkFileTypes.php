<?php

namespace Artwork\Core\FileHandling\Upload;

enum ArtworkFileTypes: string
{
    case PROJECT = 'project';
    case ROOM = 'room';
    case BRANDING = 'branding';

    case CONTRACT = 'contract';
}
