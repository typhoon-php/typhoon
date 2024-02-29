<?php

declare(strict_types=1);

namespace Typhoon\Psalm;

use PhpParser\Node\Stmt\Function_;
use Psalm\CodeLocation;
use Psalm\Issue\PluginIssue;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterClassLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\AfterFunctionLikeAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeAnalysisEvent;
use Psalm\Plugin\EventHandler\Event\AfterFunctionLikeAnalysisEvent;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;

/**
 * @psalm-suppress UnusedClass
 */
final class CheckVisibilityPlugin implements PluginEntryPointInterface, AfterClassLikeAnalysisInterface
{
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event): ?bool
    {
        $class = $event->getClasslikeStorage();

        if ($event->getStmt()->name !== null && !$class->internal && !$class->public_api) {
            IssueBuffer::accepts(
                new UnspecifiedVisibility(
                    'Class ' . $class->name,
                    $class->location ?? new CodeLocation($event->getStatementsSource(), $event->getStmt()),
                ),
                $event->getStatementsSource()->getSuppressedIssues(),
            );
        }

        return null;
    }

    public function __invoke(RegistrationInterface $registration, ?\SimpleXMLElement $config = null): void
    {
        $registration->registerHooksFromClass(self::class);
        $registration->registerHooksFromClass(CheckFunctionVisibility::class);
    }
}

final class CheckFunctionVisibility implements AfterFunctionLikeAnalysisInterface
{
    public static function afterStatementAnalysis(AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        $function = $event->getFunctionlikeStorage();

        if ($event->getStmt() instanceof Function_ && $function->cased_name !== null && !$function->internal && !$function->public_api) {
            IssueBuffer::accepts(
                new UnspecifiedVisibility(
                    'Function ' . $function->cased_name,
                    $function->location ?? new CodeLocation($event->getStatementsSource(), $event->getStmt()),
                ),
                $event->getStatementsSource()->getSuppressedIssues(),
            );
        }

        return null;
    }
}

final class UnspecifiedVisibility extends PluginIssue
{
    public function __construct(string $name, CodeLocation $code_location)
    {
        parent::__construct(sprintf('%s must be either @api or @internal', $name), $code_location);
    }
}
