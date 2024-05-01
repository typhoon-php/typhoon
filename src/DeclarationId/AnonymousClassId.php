<?php

declare(strict_types=1);

namespace Typhoon\DeclarationId;

/**
 * @api
 * @psalm-immutable
 */
final class AnonymousClassId extends DeclarationId
{
    /**
     * @param non-empty-string $file
     * @param positive-int $line
     * @param ?\class-string $originalName
     */
    protected function __construct(
        public readonly string $file,
        public readonly int $line,
        private readonly ?string $originalName = null,
    ) {}

    /**
     * @return class-string
     */
    public function name(): string
    {
        if ($this->originalName !== null) {
            return $this->originalName;
        }

        throw new \LogicException('Runtime anonymous class name is not available');
    }

    public function toString(): string
    {
        return sprintf('anonymous-class(%s, %d)', $this->file, $this->line);
    }

    public function __serialize(): array
    {
        return [
            'file' => $this->file,
            'line' => $this->line,
            'originalName' => null,
        ];
    }
}
