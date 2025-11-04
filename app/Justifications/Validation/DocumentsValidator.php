<?php

namespace App\Justifications\Validation;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class DocumentsValidator extends AbstractValidationHandler
{
    public function __construct(private bool $required = true) {}

    protected function validate(array $payload): void
    {
        /** @var UploadedFile[]|null $files */
        $files = Arr::get($payload, 'documents', null);

        if ($this->required) {
            if (empty($files)) {
                throw ValidationException::withMessages([
                    'documents' => 'Debes adjuntar al menos un documento de evidencia.',
                ]);
            }
        }

        if (!empty($files)) {
            foreach ($files as $file) {
                if (!$file instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        'documents' => 'Archivo de evidencia inválido.',
                    ]);
                }
                // These mirror your Request rules to keep domain logic cohesive
                $okMime = in_array($file->getMimeType(), ['application/pdf', 'image/jpeg', 'image/png'], true);
                if (!$okMime || $file->getSize() > 2 * 1024 * 1024) {
                    throw ValidationException::withMessages([
                        'documents' => 'Cada documento debe ser PDF/JPG/PNG y no superar 2MB.',
                    ]);
                }
            }
        }
    }
}
