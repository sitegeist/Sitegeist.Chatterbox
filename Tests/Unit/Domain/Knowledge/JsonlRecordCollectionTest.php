<?php

declare(strict_types=1);

namespace Sitegeist\Chatterbox\Domain\Knowledge;

use GuzzleHttp\Psr7\Uri;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class JsonlRecordCollectionTest extends TestCase
{
    /**
     * @dataProvider stringProvider
     */
    public function testFromString(string $string, JsonlRecordCollection $expectedResult): void
    {
        Assert::assertEquals($expectedResult, JsonlRecordCollection::fromString($string));
    }

    /**
     * @return \Traversable<string,mixed>
     */
    public static function stringProvider(): \Traversable
    {
        yield 'emptyCase' => [
            'string' => '',
            'expectedResult' => new JsonlRecordCollection()
        ];

        yield 'singleLineCase' => [
            'string' => '{"id": "nody-mc-nodeface", "url": "https://www.sitegeist.de", "content": "<h1>Hello world</h1>"}',
            'expectedResult' => new JsonlRecordCollection(
                new JsonlRecord('nody-mc-nodeface', new Uri('https://www.sitegeist.de'), '<h1>Hello world</h1>')
            )
        ];

        yield 'multipleLineCase' => [
            'string' => '{"id": "nody-mc-nodeface", "url": "https://www.sitegeist.de", "content": "<h1>Hello world</h1>"}
{"id": "sir-nodeward-nodintgon", "url": "https://neos.io", "content": "<h1>Good night world</h1>"}',
            'expectedResult' => new JsonlRecordCollection(
                new JsonlRecord('nody-mc-nodeface', new Uri('https://www.sitegeist.de'), '<h1>Hello world</h1>'),
                new JsonlRecord('sir-nodeward-nodintgon', new Uri('https://neos.io'), '<h1>Good night world</h1>')
            )
        ];
    }

    public function testFindRecordByContentPart(): void
    {
        $noodleSoup = new JsonlRecord('nody-mc-nodeface', new Uri('https://www.sitegeist.de'), '<h1>Noodle Soup</h1>');
        $tomatoSoup = new JsonlRecord('sir-nodeward-nodintgon', new Uri('https://neos.io'), '<h1>Tomato Soup</h1>');
        $subject = new JsonlRecordCollection(
            $noodleSoup,
            $tomatoSoup
        );

        Assert::assertSame($noodleSoup, $subject->findRecordByContentPart('Noodle'));
        Assert::assertSame($tomatoSoup, $subject->findRecordByContentPart('Tomato'));
    }
}
