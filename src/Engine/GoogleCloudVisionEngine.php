<?php
declare(strict_types = 1);

namespace App\Engine;

use App\Exception\OcrException;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\ImageContext;
use Google\Cloud\Vision\V1\TextAnnotation;
use Krinkle\Intuition\Intuition;

class GoogleCloudVisionEngine extends EngineBase
{
    /** @var string The API key. */
    protected $key;

    /** @var ImageAnnotatorClient */
    protected $imageAnnotator;

    /**
     * GoogleCloudVisionEngine constructor.
     * @param string $keyFile Filesystem path to the credentials JSON file.
     * @param Intuition $intuition
     * @param string $projectDir
     */
    public function __construct(string $keyFile, Intuition $intuition, string $projectDir)
    {
        parent::__construct($intuition, $projectDir);
        $this->imageAnnotator = new ImageAnnotatorClient(['credentials' => $keyFile]);
    }

    /**
     * @inheritDoc
     */
    public static function getId(): string
    {
        return 'google';
    }

    /**
     * @inheritDoc
     * @throws OcrException
     */
    public function getText(string $imageUrl, ?array $langs = null): string
    {
        $this->checkImageUrl($imageUrl);

        // Validate the languages
        $this->validateLangs($langs);

        $imageContext = new ImageContext();
        if (null !== $langs) {
            $imageContext->setLanguageHints($langs);
        }

        $response = $this->imageAnnotator->textDetection($imageUrl, ['imageContext' => $imageContext]);

        if ($response->getError()) {
            throw new OcrException('google-error', [$response->getError()->getMessage()]);
        }

        $annotation = $response->getFullTextAnnotation();
        return $annotation instanceof TextAnnotation ? $annotation->getText() : '';
    }
}
