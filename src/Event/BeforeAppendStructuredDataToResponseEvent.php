<?php

namespace Lens\Bundle\SeoBundle\Event;

use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event;

class BeforeAppendStructuredDataToResponseEvent extends Event
{
    public function __construct(
        public readonly StructuredDataBuilder $builder,
        public readonly Response $response,
    ) {
    }
}
