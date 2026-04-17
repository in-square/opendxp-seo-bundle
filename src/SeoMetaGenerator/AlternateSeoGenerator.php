<?php

declare(strict_types=1);

namespace InSquare\OpendxpSeoBundle\SeoMetaGenerator;

use InSquare\SeoBundle\Builder\TagBuilder;
use InSquare\SeoBundle\Factory\TagFactory;
use InSquare\SeoBundle\Seo\AbstractSeoGenerator;

class AlternateSeoGenerator extends AbstractSeoGenerator
{
    private TagFactory $tagFactory;

    public function __construct(TagBuilder $tagBuilder, TagFactory $tagFactory)
    {
        parent::__construct($tagBuilder);
        $this->tagFactory = $tagFactory;
    }

    public function reset(): self
    {
        $this->tagBuilder = new TagBuilder($this->tagFactory);

        return $this;
    }

    public function setAlternateLink(string $href, string $hreflang): self
    {
        $name = sprintf('alternate_%s', $hreflang);

        $this->tagBuilder->addLink(
            $name,
            $href,
            'alternate',
            null,
            null,
            ['hreflang' => $hreflang]
        );

        return $this;
    }
}
