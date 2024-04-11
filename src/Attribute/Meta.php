<?php

namespace Lens\Bundle\SeoBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Meta
{
    public function __construct(
        public ?string $locale = null,
        public ?string $title = null,
        public ?string $description = null,
        public array $keywords = [],
        public ?string $resolver = null,
        public bool $translate = false,
        public bool $translateTitle = false,
        public bool $translateDescription = false,
        public bool $translateKeywords = false,
        public array $context = [],
    ) {
    }

    public function hasTranslatableOptions(): bool
    {
        return $this->translate || $this->translateTitle || $this->translateDescription || $this->translateKeywords;
    }
}
