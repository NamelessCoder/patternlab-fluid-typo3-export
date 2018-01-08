<?php
declare(strict_types=1);
namespace NamelessCoder\FluidPatternEngineExport;

use NamelessCoder\FluidPatternEngine\Resolving\PartialNamingHelper;
use NamelessCoder\FluidPatternEngine\Traits\FluidLoader;
use PatternLab\Config;
use PatternLab\PatternData;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\NodeInterface;

class FluidPatternLabHook
{
    use FluidLoader;

    public function getListeners()
    {
        return [
            'builder.generatePatternsEnd' => [
                'callable' => static::class . '::generatePatternsEnd'
            ]
        ];
    }

    public function generatePatternsEnd(Event $event, string $eventName, EventDispatcherInterface $eventDispatcher)
    {
        $this->copyPatternSourceFiles();
    }

    protected function copyPatternSourceFiles()
    {
        $data = PatternData::get();
        $targetDirectory = Config::getOption('fluidTYPO3ExtensionExportPath');
        if (!$targetDirectory) {
            throw new \RuntimeException('Configuration option "fluidTYPO3ExtensionExportPath" must be set to a valid path');
        }
        $targetDirectory = realpath($targetDirectory);
        if (!$targetDirectory || !is_dir($targetDirectory)) {
            mkdir(Config::getOption('fluidTYPO3ExtensionExportPath'), 0755, true);
        }
        $filtered = array_filter($data, function(array $item) {
            return $item['category'] === 'pattern';
        });

        $types = array_filter($data, function(array $item) {
            return $item['category'] !== 'pattern';
        });

        $helper = new PartialNamingHelper();
        $parser = $this->view->getRenderingContext()->getTemplateParser();

        foreach ($filtered as $patternName => $patternConfiguration) {
            $targetFilename = $helper->determineTargetFileLocationForPattern($patternName);
            $sourceFilename = $this->determineSourceFileLocation($patternConfiguration, $types);
            if (!file_exists($sourceFilename)) {
                throw new \UnexpectedValueException('File "' . $sourceFilename . '" was referenced by pattern "' . $patternName . '" but the file does not exist');
            }
            $targetPatternDirectory = pathinfo($targetFilename, PATHINFO_DIRNAME);
            if (!is_dir($targetPatternDirectory)) {
                mkdir($targetPatternDirectory, 0755, true);
            }

            $source = file_get_contents($sourceFilename);
            $finalSource = $source;

            // Parse the file, creating a ParsingState, enabling us to read the layout name if one was used.
            // If a layout is used, make sure this file also gets copied to the output folder - and rename
            // it to proper UpperCamelCase in both file name and f:layout statement.
            $parsedTemplate = $parser->parse($finalSource);
            if ($parsedTemplate->hasLayout()) {
                // Rewrite the target filename to replace the layout node. Do this by evaluating, so in case any
                // dynamic Layout naming is used, this gets hardcoded in the export.
                $layoutName = $parsedTemplate->getLayoutName($this->view->getRenderingContext());
                if ($layoutName instanceof NodeInterface) {
                    $layoutName = $layoutName->evaluate($this->view->getRenderingContext());
                }
                $properLayoutName = ucfirst($layoutName);
                if (strpos($properLayoutName, '_') || strpos($properLayoutName, '-')) {
                    // Layout is using invalid name parts - convert to UpperCamelCase variant.
                    $parts = preg_split('/[_\\-]+/', $properLayoutName);
                    $parts = array_map('ucfirst', $parts);
                    $properLayoutName = implode('', $parts);
                }

                $layoutMatchPattern = '/f:layout(\\s*name=([\'"])(' . $layoutName . ')[\'"])?/';
                $matches = [];
                preg_match($layoutMatchPattern, $finalSource, $matches);
                $finalSource = $this->writeNewLayoutName($finalSource, $layoutName, $properLayoutName);

                $targetLayoutDirectory = $targetDirectory . '/Resources/Private/Layouts';
                if (!is_dir($targetLayoutDirectory)) {
                    mkdir($targetLayoutDirectory, 0755, true);
                }

                $layoutSourceFilename = $this->view->getRenderingContext()->getTemplatePaths()->getLayoutPathAndFilename($layoutName);
                $layoutTargetFilename = $targetLayoutDirectory . '/' . $properLayoutName . '.html';

                // Rewrite the layout source just in case it also contains an f:layout node. While layouts are
                // not required to contain this node, and it does not get evaluated, we rewrite in order to
                // reduce potential confusion.
                $layoutSource = file_get_contents($layoutSourceFilename);
                $layoutSource = $this->writeNewLayoutName($layoutSource, $layoutName, $properLayoutName);
                file_put_contents($layoutTargetFilename, $layoutSource);
            }

            // Next, identify any "f:render" statements which render partials (with or without sections). Rewrite all
            // of those to use the expected target partial naming.
            $finalSource = preg_replace_callback('/(f:render.+partial=["\'])([^"\']+)/', function(array $matches) {
                return $matches[1] . (new PartialNamingHelper())->determinePatternSubPath($matches[2]);
            }, $finalSource);

            file_put_contents($targetFilename, $finalSource);
        }

        return null;
    }

    protected function writeNewLayoutName(string $source, string $oldLayoutName, string $newLayoutName): string
    {
        $layoutMatchPattern = '/f:layout(\\s*name=([\'"])(' . $oldLayoutName . ')[\'"])?/';
        $matches = [];
        preg_match($layoutMatchPattern, $source, $matches);
        if (!isset($matches[2])) {
            // Source contains a *NAMED* layout. Nodes that do not specify name="" are ignored since they do not need
            // to be rewritten!
            return $source;
        }
        return preg_replace($layoutMatchPattern, 'f:layout name=' . $matches[2] . $newLayoutName . $matches[2], $source);
    }

    protected function determineSourceFileLocation(array $patternConfiguration, array $types): string
    {
        $fileSubpath = ($patternConfiguration['pathName'] ?: $patternConfiguration['path'] . DIRECTORY_SEPARATOR . $patternConfiguration['name']) . '.' . $patternConfiguration['ext'];
        $type = null;
        foreach ($types as $type) {
            if ($type['name'] === $patternConfiguration['type']) {
                break;
            }
        }
        return $type['path'] . DIRECTORY_SEPARATOR . $fileSubpath;
    }
}
