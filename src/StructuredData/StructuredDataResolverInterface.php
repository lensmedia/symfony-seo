<?php

namespace Lens\Bundle\SeoBundle\StructuredData;

use Spatie\SchemaOrg\Type;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

interface StructuredDataResolverInterface
{
    public const SERVICE_TAG = 'lens_seo.structured_data.resolver';

    public function resolve(): ?Type;
}
