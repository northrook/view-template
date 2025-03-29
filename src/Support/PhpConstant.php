<?php

declare(strict_types=1);

namespace Core\View\Template\Support;

final class PhpConstant extends PhpFragment
{
    public const string TYPE = 'const';

    public function __construct(
        public readonly string $name,
        public mixed           $value,
        public Visibility      $visibility = Visibility::PUBLIC,
        protected ?string      $comment = null,
        protected ?string      $type = null,
    ) {}

    public function build() : string
    {
        $this->type ??= \gettype( $this->value );

        $const = "{$this->visibility->label()} const {$this->type} {$this->name} = {$this->dump( $this->value )};";

        return $const;
    }
}
