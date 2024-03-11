<?php

declare(strict_types=1);

namespace App\Entity\Csv;

use Symfony\Component\Validator\Constraints as Assert;

readonly class Product extends AbstractCsvEntity
{
    private const TYPE = ['Physical', 'Non-Physical'];
    private const ITEM = ['Product', 'Service'];

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Choice(
            choices: Product::ITEM,
            message: 'The item type of the row is invalid ({{ value }}). Allowed item types are {{ choices }}.'
        )]
        public ?string $item,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(max: 100)]
        public ?string $name,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Choice(
            choices: Product::TYPE,
            message: 'The type of the row is invalid ({{ value }}). Allowed item types are {{ choices }}.'
        )]
        public ?string $type,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(max: 32)]
        public ?string $sku,
        #[Assert\NotBlank]
        #[Assert\Type('int')]
        public ?int $stock,
        #[Assert\NotBlank]
        #[Assert\Type('int')]
        #[Assert\PositiveOrZero]
        public ?int $price,
        #[Assert\NotBlank]
        #[Assert\Type('int')]
        public ?int $category,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(max: 5000)]
        public ?string $description,
        #[Assert\NotBlank]
        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        public ?float $weight,
        #[Assert\NotBlank]
        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        public ?float $width,
        #[Assert\NotBlank]
        #[Assert\Type('float')]
        #[Assert\PositiveOrZero]
        public ?float $height,
        #[Assert\NotNull]
        #[Assert\Type('bool')]
        public ?bool $visible
    ) {
    }
}
