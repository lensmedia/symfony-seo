<?php

namespace Lens\Bundle\SeoBundle\StructuredData;

use Spatie\SchemaOrg\Type;

interface StructuredDataInterface
{
    public function __invoke(): ?Type;
}
