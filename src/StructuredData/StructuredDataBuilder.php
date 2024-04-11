<?php

namespace Lens\Bundle\SeoBundle\StructuredData;

use JsonSerializable;
use Spatie\SchemaOrg\Graph;
use Spatie\SchemaOrg\Type;

use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_UNICODE;

class StructuredDataBuilder implements JsonSerializable, Type
{
    private Graph $graph;

    private iterable $structuredDataBuilderResolvers;
    private int $jsonEncodeOptions;

    private array $schemaTypes = [];
    private array $schemaArrays = [];

    private array $cached = [];

    public function __construct(
        iterable $structuredDataBuilderResolvers,
        int $jsonEncodeOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    ) {
        $this->structuredDataBuilderResolvers = $structuredDataBuilderResolvers;
        $this->jsonEncodeOptions = $jsonEncodeOptions;

        // Used to track hidden schemas.
        $this->graph = new Graph();
    }

    /**
     * Add entry by SpatieOrg schema type.
     */
    public function addSchema(StructuredDataInterface|Type $type, ?string $identifier = Graph::IDENTIFIER_DEFAULT): self
    {
        if ($type instanceof StructuredDataInterface) {
            $type = $type();
        }

        $this->schemaTypes[] = [$type, $identifier];
        $this->invalidateCache();

        return $this;
    }

    /**
     * Hide specific schemas, this only works on schema entries NOT array/string entries.
     */
    public function hideSchema(string $type, ?string $identifier = Graph::IDENTIFIER_DEFAULT): self
    {
        $this->graph->hide($type, $identifier);
        $this->invalidateCache();

        return $this;
    }

    /**
     * Show previously hidden schemas.
     */
    public function showSchema(string $type, ?string $identifier = Graph::IDENTIFIER_DEFAULT): self
    {
        $this->graph->show($type, $identifier);
        $this->invalidateCache();

        return $this;
    }

    /**
     * Add schema entry from array data, this does not add multiple schemas.
     */
    public function addFromArray(array $data): self
    {
        $this->schemaArrays[] = $data;
        $this->invalidateCache();

        return $this;
    }

    public function addFromString(string $data): self
    {
        $this->schemaArrays[] = json_decode($data, flags: JSON_THROW_ON_ERROR);
        $this->invalidateCache();

        return $this;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        if (empty($this->cached)) {
            $graph = clone $this->graph;
            foreach ($this->schemaTypes as [$schema, $identifier]) {
                $graph->add($schema, $identifier);
            }

            foreach ($this->structuredDataBuilderResolvers as $resolver) {
                $schema = $resolver->resolve($graph);
                if ($schema) {
                    $graph->add($schema, $resolver::class);
                }
            }

            $this->cached = $graph->toArray();
            array_push($this->cached['@graph'], ...$this->schemaArrays);
        }

        return $this->cached;
    }

    public function toScript(): string
    {
        return '<script type="application/ld+json">'.json_encode($this->toArray(), $this->jsonEncodeOptions).'</script>';
    }

    public function __toString(): string
    {
        return $this->toScript();
    }

    private function invalidateCache(): void
    {
        $this->cached = [];
    }
}
