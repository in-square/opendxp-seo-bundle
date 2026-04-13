<?php

declare(strict_types=1);

namespace InSquare\OpendxpSeoBundle\SeoMetaGenerator;

use OpenDxp\Model\Asset\Image;

interface ObjectSeoInterface
{
    public function getSeoTitle(): ?string;

    public function getSeoDescription(): ?string;

    public function getSeoKeywords(): ?string;

    public function getSeoImage(): ?Image;
}
