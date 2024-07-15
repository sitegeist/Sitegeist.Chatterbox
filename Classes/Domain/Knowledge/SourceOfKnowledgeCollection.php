<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Doctrine\DBAL\Connection as DatabaseConnection;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextAnnotationFileCitationObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;
use Sitegeist\Chatterbox\Domain\QuotationCollection;
use Sitegeist\Chatterbox\Domain\ResolvedAndUnresolvedQuotations;
use Sitegeist\Chatterbox\Domain\UnresolvedQuotation;
use Sitegeist\Chatterbox\Domain\UnresolvedQuotationCollection;

/**
 * @implements \IteratorAggregate<SourceOfKnowledgeContract>
 */
final class SourceOfKnowledgeCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var SourceOfKnowledgeContract[]
     */
    public readonly array $items;

    public function __construct(SourceOfKnowledgeContract ...$items)
    {
        $this->items = $items;
    }

    public function getKnowledgeSourceByName(KnowledgeSourceName $name): ?SourceOfKnowledgeContract
    {
        foreach ($this->items as $sourceOfKnowledge) {
            if ($sourceOfKnowledge->getName()->equals($name)) {
                return $sourceOfKnowledge;
            }
        }

        return null;
    }

    public function resolveQuotations(
        ThreadMessageResponse $response,
        OrganizationDiscriminator $organizationDiscriminator,
        DatabaseConnection $databaseConnection
    ): ResolvedAndUnresolvedQuotations {
        $annotations = [];
        foreach ($response->content as $contentObject) {
            if ($contentObject instanceof ThreadMessageResponseContentTextObject) {
                $annotations = array_merge($annotations, $contentObject->text->annotations);
            }
        }

        $resolvedQuotations = [];
        $unresolvedQuotations = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ThreadMessageResponseContentTextAnnotationFileCitationObject) {
                $quoteString = QuoteString::tryFromFileCitationObject($annotation);
                if (!$quoteString) {
                    $unresolvedQuotations[] = new UnresolvedQuotation($annotation->text);
                    continue;
                }
                $databaseRecord = $databaseConnection->executeQuery(
                    'SELECT id, knowledge_source_discriminator FROM ' . Library::TABLE_NAME
                    . ' WHERE knowledge_source_discriminator IN (:knowledgeSourceDiscriminators)
                     AND (content LIKE :quote OR content LIKE :escapedQuote)',
                    [
                        'knowledgeSourceDiscriminators' => array_map(
                            fn (KnowledgeSourceDiscriminator $knowledgeSourceDiscriminator): string
                                => $knowledgeSourceDiscriminator->toString(),
                            $this->getDiscriminators($organizationDiscriminator)
                        ),
                        'quote' => $quoteString->wtf8Encode(),
                        'escapedQuote' => $quoteString->unicodeEscape()->wtf8Encode()
                    ],
                    [
                        'knowledgeSourceDiscriminators' => DatabaseConnection::PARAM_STR_ARRAY
                    ]
                )->fetchAssociative() ?: null;
                if (!$databaseRecord) {
                    $unresolvedQuotations[] = new UnresolvedQuotation($annotation->text);
                    continue;
                }
                $knowledgeSourceDiscriminator = KnowledgeSourceDiscriminator::fromString(
                    $databaseRecord['knowledge_source_discriminator']
                );
                $sourceOfKnowledge = $this->getKnowledgeSourceByName(
                    $knowledgeSourceDiscriminator->knowledgeSourceName
                );
                if (!$sourceOfKnowledge) {
                    $unresolvedQuotations[] = new UnresolvedQuotation($annotation->text);
                    continue;
                }
                $quotation = $sourceOfKnowledge->tryCreateQuotation($annotation->text, $annotation->fileCitation->quote, $databaseRecord['id']);
                if ($quotation) {
                    $resolvedQuotations[] = $quotation;
                }
            }
        }

        return new ResolvedAndUnresolvedQuotations(
            new QuotationCollection(...$resolvedQuotations),
            new UnresolvedQuotationCollection(...$unresolvedQuotations),
        );
    }

    /**
     * @return array<KnowledgeSourceDiscriminator>
     */
    public function getDiscriminators(OrganizationDiscriminator $organizationDiscriminator): array
    {
        return array_map(
            fn (SourceOfKnowledgeContract $sourceOfKnowledge): KnowledgeSourceDiscriminator
                => new KnowledgeSourceDiscriminator(
                    $organizationDiscriminator,
                    $sourceOfKnowledge->getName()
                ),
            $this->items
        );
    }

    /**
     * @return \Traversable<SourceOfKnowledgeContract>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
