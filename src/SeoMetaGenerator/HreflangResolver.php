<?php

declare(strict_types=1);

namespace InSquare\OpendxpSeoBundle\SeoMetaGenerator;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\DataObject\ClassDefinition\LinkGeneratorInterface;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Page;
use OpenDxp\Model\Document\PageSnippet;
use OpenDxp\Model\Document\Service as DocumentService;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HreflangResolver
{
    private RequestStack $requestStack;
    private ?string $xDefaultLanguage;

    public function __construct(RequestStack $requestStack, ?string $xDefaultLanguage = null)
    {
        $this->requestStack = $requestStack;
        $xDefaultLanguage = is_string($xDefaultLanguage) ? trim($xDefaultLanguage) : null;
        $this->xDefaultLanguage = '' === $xDefaultLanguage ? null : $xDefaultLanguage;
    }

    /**
     * @return array<string, string>
     */
    public function resolveForDocument(Page|PageSnippet $page): array
    {
        $languages = Tool::getValidLanguages();
        if (count($languages) <= 1) {
            return [];
        }

        $cacheKey = sprintf(
            'in_square_opendxp_seo_hreflang_document_%d_%s',
            (int) $page->getId(),
            md5(implode('|', $languages))
        );

        if (RuntimeCache::isRegistered($cacheKey)) {
            /** @var array<string, string> $cached */
            $cached = RuntimeCache::get($cacheKey);

            return $cached;
        }

        $links = [];
        $documentService = new DocumentService();
        /** @var array<string, int> $translations */
        $translations = $documentService->getTranslations($page);

        foreach ($languages as $language) {
            if (!isset($translations[$language])) {
                continue;
            }

            $translationDocument = Document::getById((int) $translations[$language]);
            if (!$translationDocument instanceof PageSnippet || !$translationDocument->isPublished()) {
                continue;
            }

            try {
                $url = $this->normalizeAbsoluteUrl($translationDocument->getUrl());
            } catch (\Throwable) {
                continue;
            }
            if (null === $url) {
                continue;
            }

            $links[$this->normalizeHreflang($language)] = $url;
        }

        $links = $this->appendXDefault($links);
        RuntimeCache::set($cacheKey, $links);

        return $links;
    }

    /**
     * @return array<string, string>
     */
    public function resolveForObject(object $object, string $currentUrl): array
    {
        $languages = Tool::getValidLanguages();
        if (count($languages) <= 1) {
            return [];
        }

        $currentLocale = $this->requestStack->getMainRequest()?->getLocale();
        $objectIdentifier = method_exists($object, 'getId') ? (string) $object->getId() : spl_object_hash($object);
        $cacheKey = sprintf(
            'in_square_opendxp_seo_hreflang_object_%s_%s_%s',
            md5(get_class($object) . ':' . $objectIdentifier),
            md5($currentUrl),
            md5((string) $currentLocale)
        );

        if (RuntimeCache::isRegistered($cacheKey)) {
            /** @var array<string, string> $cached */
            $cached = RuntimeCache::get($cacheKey);

            return $cached;
        }

        $links = [];
        $linkGenerator = $this->resolveLinkGenerator($object);

        if (null !== $linkGenerator) {
            foreach ($languages as $language) {
                try {
                    $url = $linkGenerator->generate($object, [
                        'referenceType' => UrlGeneratorInterface::ABSOLUTE_URL,
                        'locale' => $language,
                        '_locale' => $language,
                    ]);
                } catch (\Throwable) {
                    continue;
                }

                $url = $this->normalizeAbsoluteUrl($url);
                if (null === $url) {
                    continue;
                }

                $links[$this->normalizeHreflang($language)] = $url;
            }
        }

        if (is_string($currentLocale) && in_array($currentLocale, $languages, true)) {
            $normalizedCurrentUrl = $this->normalizeAbsoluteUrl($currentUrl) ?? $currentUrl;
            $links[$this->normalizeHreflang($currentLocale)] = $normalizedCurrentUrl;
        }

        $links = $this->appendXDefault($links);
        RuntimeCache::set($cacheKey, $links);

        return $links;
    }

    private function resolveLinkGenerator(object $object): ?LinkGeneratorInterface
    {
        if (!method_exists($object, 'getClass')) {
            return null;
        }

        $classDefinition = $object->getClass();
        if (!is_object($classDefinition) || !method_exists($classDefinition, 'getLinkGenerator')) {
            return null;
        }

        $linkGenerator = $classDefinition->getLinkGenerator();
        if (!$linkGenerator instanceof LinkGeneratorInterface) {
            return null;
        }

        return $linkGenerator;
    }

    private function normalizeHreflang(string $language): string
    {
        $parts = preg_split('/[-_]/', $language) ?: [];
        if ([] === $parts) {
            return strtolower($language);
        }

        $primary = strtolower((string) array_shift($parts));
        $normalized = [$primary];

        foreach ($parts as $part) {
            if ('' === $part) {
                continue;
            }

            if (4 === strlen($part)) {
                $normalized[] = ucfirst(strtolower($part));
                continue;
            }

            $normalized[] = strtoupper($part);
        }

        return implode('-', $normalized);
    }

    /**
     * @param array<string, string> $links
     *
     * @return array<string, string>
     */
    private function appendXDefault(array $links): array
    {
        if (null !== $this->xDefaultLanguage) {
            $configuredKey = $this->normalizeHreflang($this->xDefaultLanguage);
            if (isset($links[$configuredKey])) {
                $links['x-default'] = $links[$configuredKey];

                return $links;
            }
        }

        $defaultLanguage = Tool::getDefaultLanguage();
        if (null === $defaultLanguage) {
            return $links;
        }
        $defaultKey = $this->normalizeHreflang($defaultLanguage);
        if (isset($links[$defaultKey])) {
            $links['x-default'] = $links[$defaultKey];
        }

        return $links;
    }

    private function normalizeAbsoluteUrl(?string $url): ?string
    {
        if (null === $url || '' === trim($url)) {
            return null;
        }

        $url = trim($url);

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        try {
            $hostUrl = Tool::getHostUrl();
        } catch (\Throwable) {
            return null;
        }
        if ('' === trim($hostUrl)) {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return rtrim($hostUrl, '/') . $url;
        }

        return rtrim($hostUrl, '/') . '/' . ltrim($url, '/');
    }
}
