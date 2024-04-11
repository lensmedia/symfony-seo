<?php

namespace Lens\Bundle\SeoBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Breadcrumb
{
    public string $routeName;
    public array $routeParameters = [];

    public function __construct(
        public string|array|null $title = null,
        public ?string $resolver = null,
        public bool $translate = false,
        public ?string $parent = null,
        public array $context = [],
    ) {
    }
}
