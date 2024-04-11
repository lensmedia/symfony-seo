# LensSeoBundle
Some simple reusable SEO tools for Symfony projects.

## Meta
### Attribute 
```php
use Lens\Bundle\SeoBundle\Attribute\Meta;

class Index extends AbstractController
{
    #[Route([
        'nl' => null,
        'en' => '/en',
    ], name: 'homepage')]
    #[Meta('nl', 'Hoi wereld!', keywords: ['lens', 'zmo', 'bundel'])]
    #[Meta('en', 'Hello world!', keywords: ['lens', 'seo', 'bundle'])]
    public function __invoke(): Response
    {
        return $this->render('homepage.html.twig');
    }
}
```

### Using in twig
The `Twig/MetaExtension` adds a global variable `lens_seo_meta` (can be changed, see config) to the twig context that can then be used
```html
<title>{{ title ?? lens_seo_meta.title ?? 'meta.title'|trans }}</title>

{% set title = lens_seo_meta.title ?? title ?? 'meta.title'|trans %}
{% set description = lens_seo_meta.description ?? description ?? 'meta.description'|trans %}

{% if lens_seo_meta is defined and lens_seo_meta is not empty %}
    <meta name="title" content="{{ title }}">
    <meta name="description" content="{{ description }}">
    {% if keywords ?? lens_seo_meta.keywords|length %}
        <meta name="keywords" content="{{ (keywords ?? meta.keywords)|join(', ') }}">
    {% endif %}
{% endif %}
```

### Meta resolver
A meta resolver allows for full control over the meta tags, mainly useful for dynamic routes.
```php
#[Route(name: 'faq')]
#[Meta(resolver: FaqResolver::class)]
public function __invoke(): Response
{
   ...
```
```php
namespace App\Seo\Meta;

use Lens\Bundle\SeoBundle\Attribute\Meta;
use Lens\Bundle\SeoBundle\MetaResolverInterface;
use Symfony\Component\HttpFoundation\Request;

class FaqResolver implements MetaResolverInterface
{
    public function resolveMeta(Request $request, Meta $meta): void
    {
        // This works well if you have an entity value resolver, otherwise you
        // can use the value and use dependency injection to get the entity.
        $faq = $request->attributes->get('faq');

        $meta->title = $faq->metaTitle ?? $faq->question;
        $meta->description = $faq->metaDescription;
    }
```

## Breadcrumbs
### Attribute to add breadcrumbs
```php
use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;

class Index extends AbstractController
{
    #[Route(name: 'homepage_route_name')]
    #[Breadcrumb([
        'nl' => 'homepagina',
        'en' => 'homepage',
    ])]
    public function __invoke(): Response
    {
        return $this->render('homepage.html.twig');
    }
}
```
```php
class Faq extends AbstractController
{
    #[Route(name: 'faq_route_name')]
    #[Breadcrumb([
        'nl' => 'veel gestelde vragen',
        'en' => 'frequently asked questions',
    ], parent: 'homepage_route_name')]
    public function __invoke(): Response
    {
        return $this->render('faq.html.twig');
    }
}
```

### Using in twig
The `Twig/BreadcrumbExtension` adds a global variable `lens_seo_breadcrumbs` (can be changed, see config) to the twig context that can then be used

```html
{% if lens_seo_breadcrumbs is defined and lens_seo_breadcrumbs is not empty %}
    <div class="container py-2 small fst-italic">
        <ol class="breadcrumb opacity-75">
            {% for breadcrumb in lens_seo_breadcrumbs %}
                {% if loop.last %}
                    <li class="breadcrumb-item active">{{ breadcrumb.title }}</li>
                {% else %}
                    <li class="breadcrumb-item">
                        <a href="{{ path(breadcrumb.routeName, breadcrumb.routeParameters) }}">{{ breadcrumb.title }}</a>
                    </li>
                {% endif %}
            {% endfor %}
        </ol>
    </div>
{% endif %}
```
_Example was made for bootstrap 5_

### Breadcrumb resolver
A breadcrumb resolver allows for full control over the breadcrumbs when they have dynamic values.
```php
#[Route(name: 'faq')]
#[Meta(resolver: FaqResolver::class)]
public function __invoke(): Response
{
   ...
```
```php
namespace App\Seo\Meta;

use Lens\Bundle\SeoBundle\Attribute\Breadcrumb;
use Lens\Bundle\SeoBundle\BreadcrumbResolverInterface;
use Symfony\Component\HttpFoundation\Request;

class FaqResolver implements BreadcrumbResolverInterface
{
    public function resolveMeta(Request $request, Breadcrumb $breadcrumb): void
    {
        $faq = $request->attributes->get('faq');

        $breadcrumb->title = $faq->question ?? $request->attributes->get('uri');
        $breadcrumb->routeParameters['uri'] = $request->attributes->get('uri');
        $breadcrumb->routeParameters['_locale'] = $request->getLocale();
    }
```

## Structured Data
Provides classes to help with setting up structured data using [spatie/schema-org](https://github.com/spatie/schema-org).

```php
<?php

use Spatie\SchemaOrg\Schema;
use Lens\Bundle\SeoBundle\StructuredData\StructuredDataBuilder;

class Index extends AbstractController
{
    #[Route(name: 'homepage')]
    public function __invoke(StructuredDataBuilder $structuredData): Response
    {
        $url = rtrim($this->generateUrl('homepage_route_name', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $address = Schema::postalAddress()
            ->streetAddress('Energiestraat 5')
            ->addressLocality('Hattem')
            ->postalCode('8051TE')
            ->addressCountry('NL');

        return Schema::organization()
            ->name('LENS Verkeersleermiddelen')
            ->address($address)
            ->url($url)
            ->sameAs($url);
    
        // Usually you would do the organization in a listener, so it works on all requests.
        $structuredData->addSchema($organization);

        return $this->render('homepage.html.twig');
    }
}
```

You could also create a factory service for reusability like so:
```php
class Organization implements StructuredDataInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(): \Spatie\SchemaOrg\Organization
    {
        $url = rtrim($this->urlGenerator->generate('web_common_index', [], UrlGeneratorInterface::ABSOLUTE_URL), '/');

        $address = Schema::postalAddress()
            ->streetAddress('Energiestraat 5')
            ->addressLocality('Hattem')
            ->postalCode('8051TE')
            ->addressCountry('NL');

        return Schema::organization()
            ->name('LENS Verkeersleermiddelen')
            ->address($address)
            ->url($url)
            ->sameAs($url);
    }
}
```

Which in turn changes the controller to:
```php
class Index extends AbstractController
{
    #[Route(name: 'homepage')]
    public function __invoke(StructuredDataBuilder $structuredData, Organization $organization): Response
    {
        $structuredData->addSchema($organization);

        return $this->render('homepage.html.twig');
    }
}
```

_The invokable method will be called automatically (but it doesnt matter if you do)._

### Adding the structured data to the response 
The `Event/AppendStructuredDataToResponse` listener will automatically append the structured data to 
the response just before the closing body tag if it exists. If for some reason you need a different 
use case you can use the `StructuredDataBuilder` service `toArray`/`toScript` functions to do your things.

### Manually adding extra structured data
You can use the `StructuredDataBuilder` service to add extra structured data to the response from almost anywhere.
For example exposing the service to twig you could simply do:
```html
{% do lens_seo_structured_data.addFromArray({ foo: 'bar' }) %}
```
```php
$structeredData->addFromString('{"foo":"bar"}');
```

## Config
Below is the default available configuration options.
```yaml
lens_seo:
    structured_data:
        json_encode_options: 320 # int bitmask, see https://www.php.net/manual/en/function.json-encode.php unescaped slashes (64) & unescaped unicode (256)

    twig:
        globals:
            prefix: 'lens_seo_' # prefix for the global variable names listed below, null to disable

            meta:
                enabled: true # enables the global variable
                name: 'meta' # name of the global variable
            
            breadcrumbs:
                enabled: true
                name: 'breadcrumbs'
            
            structured_data:
                enabled: true
                name: 'structured_data'
```

Our current common example config:
```yaml
lens_seo:
    twig:
        globals:
            # removes all prefixes allows direct access to: meta, breadcrumbs and structuredData.
            prefix: ~

            structured_data:
                # looks prettier when using e.g.: structuredData.addFromArray. We do not use snake
                # case anymore, and the other functions are already one word.
                name: 'structuredData'

when@dev:
    lens_seo:
        structured_data:
            json_encode_options: 448 # Adds pretty print (128) in dev
```
