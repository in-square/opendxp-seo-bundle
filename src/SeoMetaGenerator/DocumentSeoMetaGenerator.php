<?php

declare(strict_types=1);

namespace InSquare\OpendxpSeoBundle\SeoMetaGenerator;

use OpenDxp\Model\Asset\Image;
use OpenDxp\Model\Asset\Image\Thumbnail;
use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Document\PageSnippet;
use OpenDxp\Tool;

class DocumentSeoMetaGenerator
{
    private MetaGenerator $seoMetaGenerator;
    private HreflangResolver $hreflangResolver;

    public function __construct(MetaGenerator $seoMetaGenerator, HreflangResolver $hreflangResolver)
    {
        $this->seoMetaGenerator = $seoMetaGenerator;
        $this->hreflangResolver = $hreflangResolver;
    }

    public function generate(Page|PageSnippet $page, bool $isNoIndex = false): void
    {
        $alternateLinks = $this->hreflangResolver->resolveForDocument($page);

        $this->seoMetaGenerator
            ->setTitle($page->getTitle()) // @phpstan-ignore-line
            ->setDescription($page->getDescription()) // @phpstan-ignore-line
            ->setKeywords($this->prepareKeywords($page))
            ->setUrl($page->getUrl())
            ->setImage($this->prepareImage($page))
            ->setAlternateLinks($alternateLinks)
            ->setIsNoIndex($isNoIndex)
            ->generate();
    }

    private function prepareImage(Page|PageSnippet $page): ?string
    {
        if (!$page->hasProperty('meta_tag_image')) {
            return null;
        }

        $image = $page->getProperty('meta_tag_image');

        if (!$image instanceof Image) {
            return null;
        }

        /**
         * @var Thumbnail $thumbnail
         */
        $thumbnail = $image->getThumbnail($this->seoMetaGenerator->getThumbnailName());

        return Tool::getHostUrl() . $thumbnail->getPath();
    }

    private function prepareKeywords(Page|PageSnippet $page): ?string
    {
        if (!$page->hasProperty('seo_keywords')) {
            return null;
        }

        $keywords = $page->getProperty('seo_keywords');

        if (!is_string($keywords)) {
            return null;
        }

        $keywords = trim($keywords);

        return '' === $keywords ? null : $keywords;
    }
}
