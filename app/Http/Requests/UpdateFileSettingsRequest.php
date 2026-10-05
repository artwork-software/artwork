<?php

namespace App\Http\Requests;

use Artwork\Core\FileHandling\Upload\ArtworkFileTypes;
use Artwork\Modules\System\FileHandling\MimeTypeList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFileSettingsRequest extends FormRequest
{
    /** Obergrenze des Reglers in den Datei-Einstellungen (System/FileSettings/Index.vue). */
    public const MAX_FILE_SIZE_MB = 1024;

    /**
     * Zugriff regelt die Routen-Middleware `can:change tool settings`.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'data' => ['required', 'array'],
            'data.name' => ['required', Rule::enum(ArtworkFileTypes::class)],
            'data.fileSize' => ['required', 'integer', 'min:1', 'max:' . self::MAX_FILE_SIZE_MB],
            'data.fileTypes' => ['present', 'array'],
            'data.fileTypes.*.name' => [
                'required',
                'string',
                Rule::in(array_merge(
                    array_keys(MimeTypeList::IMAGE_MIME_TYPES),
                    array_keys(MimeTypeList::MIME_TYPES)
                )),
            ],
        ];
    }
}
