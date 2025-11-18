<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain;

use Neos\Flow\Annotations as Flow;
use OpenAI\Actions\Responses\OutputText;
use OpenAI\Responses\Responses\Input\InputMessageContentInputText as InputText;
use OpenAI\Responses\Responses\Output\OutputMessageContentOutputText;
use OpenAI\Responses\Responses\Output\OutputMessageContentOutputTextAnnotationsFileCitation;
use OpenAI\Responses\Threads\Messages\ThreadMessageResponseContentTextObject;
use League\CommonMark\CommonMarkConverter;
use Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeCollection;

#[Flow\Proxy(false)]
final class ContentText implements ContentInterface
{
    public function __construct(
        public readonly string $value,
        public readonly QuotationCollection $quotationCollection,
    ) {
    }

    public static function createFromInputText(InputText $inputText): self
    {
        return new ContentText($inputText->text, QuotationCollection::createEmpty());
    }

    public static function createFromOutputText(OutputMessageContentOutputText $outputText, SourceOfKnowledgeCollection $sourceOfKnowledgeCollection): self
    {
        $quotations = [];

        /**
         * @var OutputMessageContentOutputTextAnnotationsFileCitation[] $fileCitations
         */
        $fileCitations = array_filter($outputText->annotations, fn(object $item) => ($item instanceof OutputMessageContentOutputTextAnnotationsFileCitation));

        // make file citations unique because for some reason we get duplicates here
        /**
         * @var OutputMessageContentOutputTextAnnotationsFileCitation[] $fileCitationsUnique
         */
        $fileCitationsUnique = [];
        foreach ($fileCitations as $fileCitation) {
            $fileCitationsUnique[$fileCitation->index . '::' . $fileCitation->fileId] = $fileCitation;
        }

        foreach ($fileCitationsUnique as $fileCitation) {
            list($filename, $type) = explode('.', $fileCitation->filename, 2);
            $quotations[] = $sourceOfKnowledgeCollection->tryCreateQuotation($fileCitation->index, $filename, $type);
        }
        $converter = new CommonMarkConverter([
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        $html = $converter->convert($outputText->text);
        return new self($html->getContent(), new QuotationCollection(...array_filter($quotations)));
    }

    public function getType(): string
    {
        return "text";
    }

    /**
     * @return array{type:string, value: string}
     */
    public function toApiArray(): array
    {
        return [
            'type' => 'text',
            'value' => mb_convert_encoding($this->value, 'UTF-8', 'UTF-8'),
            'quotations' => $this->quotationCollection->toApiArray(),
        ];
    }
}
