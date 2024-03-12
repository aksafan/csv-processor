<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class MimeTypeValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof MimeType) {
            throw new ConstraintDefinitionException(sprintf('Constraint must be an instance of %s.', MimeType::class));
        }
        if (!$constraint->mimeTypes) {
            throw new ConstraintDefinitionException('At least one mime type has to be specified.');
        }

        // Ignoring null and empty values to allow other constraints (E.g. NotBlank, NotNull, etc.) to take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!($value instanceof File)) {
            throw new UnexpectedValueException($value, File::class);
        }

        $mimeType = $value->getMimeType();
        if ($this->isMimeTypeValid($mimeType, $constraint->mimeTypes)) {
            return;
        }

        $mimeTypes = is_string($constraint->mimeTypes) ? [$constraint->mimeTypes] : $constraint->mimeTypes;
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ type }}', $this->formatValue($mimeType))
            ->setParameter('{{ types }}', $this->formatValues($mimeTypes))
            ->setCode(\Symfony\Component\Validator\Constraints\File::INVALID_MIME_TYPE_ERROR)
            ->addViolation();
    }

    protected function isMimeTypeValid(null|string $mimeType, array|string $mimeTypes): bool
    {
        if (!$mimeType) {
            return false;
        }

        if (is_string($mimeTypes)) {
            return str_contains($mimeTypes, $mimeType);
        }

        return in_array($mimeType, $mimeTypes, true);
    }
}
