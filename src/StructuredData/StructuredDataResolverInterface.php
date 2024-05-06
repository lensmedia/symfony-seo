<?php

namespace Lens\Bundle\SeoBundle\StructuredData;

use Spatie\SchemaOrg\Type;

interface StructuredDataResolverInterface
{
    public const STRUCTURED_DATA_SERVICE_TAG = 'lens_seo.structured_data.resolver';

    public function resolve(): ?Type;
}
