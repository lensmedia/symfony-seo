<?php

namespace Lens\Bundle\SeoBundle\Event;

use Lens\Bundle\SeoBundle\StructuredData\StructuredDataResolverInterface;
use Spatie\SchemaOrg\Type;
use Symfony\Contracts\EventDispatcher\Event;

class StructuredDataResolvedEvent extends Event
{
    public function __construct(
        public readonly Type $schema,
        public readonly StructuredDataResolverInterface $resolver,
    ) {
    }
}
