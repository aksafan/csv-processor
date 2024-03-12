<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class MimeType extends Constraint
{
    private const ERROR_MESSAGE =
        'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.';

    #[HasNamedArguments]
    public function __construct(
        public array|string $mimeTypes,
        public string $message = self::ERROR_MESSAGE,
        array $groups = null,
        $payload = null
    ) {
        parent::__construct([], $groups, $payload);
    }
}
