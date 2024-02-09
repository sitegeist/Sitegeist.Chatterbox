<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use Doctrine\DBAL\Connection as DatabaseConnection;
use Doctrine\DBAL\Types\Types;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponse;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextAnnotationFileCitationObject;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use Sitegeist\Chatterbox\Domain\OrganizationDiscriminator;
use Sitegeist\Chatterbox\Domain\QuotationCollection;

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
    ): QuotationCollection {
        $annotations = [];
        foreach ($response->content as $contentObject) {
            if ($contentObject instanceof ThreadMessageResponseContentTextObject) {
                $annotations = array_merge($annotations, $contentObject->text->annotations);
            }
        }

        $quotations = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof ThreadMessageResponseContentTextAnnotationFileCitationObject) {
                $databaseRecord = $databaseConnection->executeQuery(
                    'SELECT id, knowledge_source_discriminator FROM ' . Library::TABLE_NAME
                    . ' WHERE knowledge_source_discriminator IN :knowledgeSourceDiscriminators
                     AND content LIKE :quote AND %' . $annotation->fileCitation->quote,
                    [
                        'knowledgeSourceDiscriminators' => array_map(
                            fn (KnowledgeSourceDiscriminator $knowledgeSourceDiscriminator): string
                                => $knowledgeSourceDiscriminator->toString(),
                            $this->getDiscriminators($organizationDiscriminator)
                        )
                    ],
                    [
                        'knowledgeSourceDiscriminators' => Types::SIMPLE_ARRAY
                    ]
                )->fetchAssociative() ?: null;
                if (!$databaseRecord) {
                    continue;
                }
                $knowledgeSourceDiscriminator = KnowledgeSourceDiscriminator::fromString(
                    $databaseRecord['knowledge_source_discriminator']
                );
                $sourceOfKnowledge = $this->getKnowledgeSourceByName(
                    $knowledgeSourceDiscriminator->knowledgeSourceName
                );
                if (!$sourceOfKnowledge) {
                    continue;
                }
                $quotation = $sourceOfKnowledge->tryCreateQuotation($annotation->text, $databaseRecord['id']);
                if ($quotation) {
                    $quotations[] = $quotation;
                }
            }
        }

        return new QuotationCollection(...$quotations);
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
