# Sitegeist.Chatterbox

Headless AI Chat with OpenApi backed Endpoint connecting the OpenAi-API with Neos.

## Authors & Sponsors

* Bernhard Schmitt - schmitt@sitegeist.de
* Melanie Wüst - wuest@sitegeist.de
* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored by http://www.sitegeist.de.*

## Installation

Sitegeist.Chatterbox is available via packagist. Run `composer require sitegeist/chatterbox` to require this package.
You may also want to install `flowpack/cors` or any other CORS package if you are using the endpoints from different urls .

We use semantic versioning, so every breaking change will increase the major version number.

## Setup

1. Configure Accounts

At first the OpenAI-Api Connections that are to be used are configured.

```yaml
Sitegeist:
  Flow:
    OpenAiClientFactory:
      accounts:
        example:
          apiKey: '___secret___'
          organisation: '___secret___'
```

2. Configure Tools and Knowledge

The tools and knowledge and instructions that can later be assigned to assistants are configured. 
!!! This does not mean they are used automatically, but they can be selected afterward !!!

```yaml
Sitegeist:
    Chatterbox:
        #
        # Instructions are repeated every time the assistant is called, use this for really important 
        # information that must not be forgotten
        # 
        # The interface to implement is: \Sitegeist\Chatterbox\Domain\Instruction\InstructionContract
        #
        instructions:
            currentDate:
                className: \Sitegeist\Chatterbox\Domain\Instruction\CurrentDateInstruction
                options:
                    description: 'Instructs the assistant about the current date'
    
        # 
        # Tools represent function calls the assistants may choose to execute to perform their job.
        # To prevent hallucinations, it is recommended to make all important todos and data available 
        # to the assistant via tools
        #
        # The interface to implement is: \Sitegeist\Chatterbox\Domain\Tools\ToolContract
        # 
        tools:
            example_tool:
                # implements 
                className: \Vendor\Site\ExampleTool
                options:
                    site: 'example'
                    
        #
        # Knowledge represents information that will be indexed in a vector store and can be accessed via file_search
        # Prima usecase is the content of the website the assistant should be aware of. 
        #
        # The interface to implement is: \Sitegeist\Chatterbox\Domain\Knowledge\SourceOfKnowledgeContract
        # 
        knowledge:
            # knowledge 
            example_site_content_de:
                className: \Sitegeist\Chatterbox\Domain\Knowledge\ContentRepositorySourceOfKnowledge
                options:
                    description: 'Example Site Content DE'
                    root: '/sites/example'
                    dimensions: { language: 'de' }

```

3. Create Assistants 

In the next step assistants can be created in the "Chatterbox AI Assistants" BE Module. Here you assign the 
account, define the instructions and select the tools knowledge and instructions available to this specific assistant.

4. Update Assitants Knowledge 

Once an assistant was configured the command `./flow knowledge:upload --account example`

5. Use API 

To actually communicate with the chatbot FE Apis are provieded with a specification that can be accessed via 
`GET /openapi/document/chatterbox` or via cli-command `./flow openapidocument:render chatterbox`

The actual communication happen usesing the three endpoints:    
- POST /chatterbox/chat/start ... inititate a chat. Returns the answer together with a thread-id that is used in the consecutive calls
- GET /chatterbox/chat/history ... read a full thread 
- POST /chatterbox/chat/post ... add a message to a thread 

Tools con return metadata in addition to the response that is sent to the OpenAI-API. This allows communicating with
the frontend without sending the data to an AI that may or may not be trusted. 

## Contribution

We will gladly accept contributions. Please send us pull requests.
