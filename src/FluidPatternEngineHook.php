<?php
declare(strict_types=1);
namespace NamelessCoder\FluidPatternEngineExport;

use NamelessCoder\FluidPatternEngine\Hooks\FluidPatternEngineHookInterface;
use TYPO3Fluid\Fluid\View\ViewInterface;

/*
 * Notice: This is a stub class. It is included so that any PL fluid edition
 * that is generated, will receive this class as part of the configuration
 * which means it does not have to be recreated once this class is filled
 * with actual business logic - since PL edition configuration does not get
 * updated if this class and the associated configuration entry was not
 * present at the initial installation.
 *
 *
 * Intention behind hook:
 *
 * The intention behind this class is to allow further configuration of a
 * View, validation of namespaces and resolving of ViewHelper class names
 * using an (installed and working) instance of TYPO3 to read out things
 * like globally registered namespaces and apply View configuration such
 * as "settings" coming from TypoScript.
 *
 * At the time of writing this notice, all of the above features are only
 * planned, none of it is implemented yet. But your PL instance will be
 * prepared for it once it does start providing features!
 */

class FluidPatternEngineHook implements FluidPatternEngineHookInterface
{
    public function viewCreated(ViewInterface $view): ViewInterface
    {
        return $view;
    }

    public function resolveViewHelperClassName(string $namespace, string $methodIdentifier)
    {
        return null;
    }

    public function validateNamespace(string $namespaceIdentifier): bool
    {
        return false;
    }

    public function viewRendered(ViewInterface $view, array $options, string $source, string $rendered): string
    {
        return $rendered;
    }
}
