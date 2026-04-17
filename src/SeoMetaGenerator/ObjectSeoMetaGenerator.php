<?php

declare(strict_types=1);

namespace InSquare\OpendxpSeoBundle\SeoMetaGenerator;

use OpenDxp\Model\Asset\Image;
use OpenDxp\Tool;

class ObjectSeoMetaGenerator
{
    private MetaGenerator $seoMetaGenerator;
    private HreflangResolver $hreflangResolver;

    public function __construct(MetaGenerator $seoMetaGenerator, HreflangResolver $hreflangResolver)
    {
        $this->seoMetaGenerator = $seoMetaGenerator;
        $this->hreflangResolver = $hreflangResolver;
    }

    public function generate(ObjectSeoInterface $object, string $url, bool $isNoIndex = false): void
    {
        $title = $object->getSeoTitle();
        $description = $object->getSeoDescription();
        $image = $object->getSeoImage();

        $seoKeywords = $object->getSeoKeywords();
        $alternateLinks = $this->hreflangResolver->resolveForObject($object, $url);

        $this->seoMetaGenerator
            ->setTitle($title)
            ->setDescription($description)
            ->setKeywords($seoKeywords)
            ->setUrl($url)
            ->setImage($this->prepareImage($image))
            ->setAlternateLinks($alternateLinks)
            ->setIsNoIndex($isNoIndex)
            ->generate();
    }

    private function prepareImage(?Image $image): ?string
    {
        if (is_null($image)) {
            return null;
        }

        $thumbnail = $image->getThumbnail($this->seoMetaGenerator->getThumbnailName());

        return Tool::getHostUrl() . $thumbnail->getPath();
    }
}
