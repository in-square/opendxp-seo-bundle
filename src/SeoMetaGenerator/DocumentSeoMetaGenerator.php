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

    public function __construct(MetaGenerator $seoMetaGenerator)
    {
        $this->seoMetaGenerator = $seoMetaGenerator;
    }

    public function generate(Page|PageSnippet $page, bool $isNoIndex = false): void
    {
        $this->seoMetaGenerator
            ->setTitle($page->getTitle()) // @phpstan-ignore-line
            ->setDescription($page->getDescription()) // @phpstan-ignore-line
            ->setUrl($page->getUrl())
            ->setImage($this->prepareImage($page))
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
}
