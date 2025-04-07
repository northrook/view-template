<?php

namespace Core\View\Template;

use Core\Profiler\ClerkProfiler;
use Core\View\Attribute\ViewComponent;
use Core\View\Element\Attributes;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Stringable;
use ReflectionClass;
use BadMethodCallException;
use LogicException;
use function Support\{normalize_path, str_end};

abstract class Component implements Stringable
{
    /** @var ?string Manually define a name for this component */
    protected const ?string NAME = null;

    protected const ?string TEMPLATE_DIRECTORY = null;

    private ?Engine $engine = null;

    protected ?string $templateFilename = null;

    protected ?ClerkProfiler $profiler = null;

    protected ?LoggerInterface $logger = null;

    public readonly string $name;

    public readonly string $uniqueID;

    public readonly Attributes $attributes;

    /**
     * Creates {@see self} with provided arguments.
     *
     * @return $this
     */
    abstract public function __invoke() : static;

    public function __toString() : string
    {
        return $this->getString();
    }

    public function getString() : string
    {
        return \trim(
            $this->getEngine()->renderToString(
                $this->getTemplatePath(),
                $this->getParameters(),
                preserveCacheKey : true,
            ),
        );
    }

    final public function setDependencies(
        ?Engine          $engine,
        ?ClerkProfiler   $profiler = null,
        ?LoggerInterface $logger = null,
    ) : self {
        $this->engine   ??= $engine;
        $this->profiler ??= $profiler;
        $this->logger   ??= $logger;

        return $this;
    }

    final public function create(
        array   $arguments,
        array   $promote = [],
        ?string $uniqueId = null,
    ) : self {
        $this->prepareArguments( $arguments );

        $this->name       = $this::getComponentName();
        $this->uniqueID   = $this->componentUniqueID( $uniqueId ?? \serialize( [$arguments] ) );
        $this->attributes = $this->assignAttributes( $arguments );

        $this->promoteTaggedProperties( $arguments, $promote );

        return $this;
    }

    protected function prepareArguments( array &$arguments ) : void {}

    final public static function getComponentName() : string
    {
        $name = self::NAME ?? self::getViewComponentAttribute()->name;

        if ( ! $name ) {
            throw new BadMethodCallException( static::class.' name is not defined.' );
        }

        if ( ! \ctype_alnum( \str_replace( ':', '', $name ) ) ) {
            $message = static::class." name '{$name}' must be lower-case alphanumeric.";

            if ( \is_numeric( $name[0] ) ) {
                $message = static::class." name '{$name}' cannot start with a number.";
            }

            if ( \str_starts_with( $name, ':' ) || \str_ends_with( $name, ':' ) ) {
                $message = static::class." name '{$name}' must not start or end with a separator.";
            }

            throw new InvalidArgumentException( $message );
        }

        return $name;
    }

    final public static function getViewComponentAttribute() : ViewComponent
    {
        $viewComponentAttributes = ( new ReflectionClass( static::class ) )->getAttributes( ViewComponent::class );

        if ( empty( $viewComponentAttributes ) ) {
            $message = 'This Component is missing the required '.ViewComponent::class.' attribute.';
            throw new BadMethodCallException( $message );
        }

        $viewAttribute = $viewComponentAttributes[0]->newInstance();
        $viewAttribute->setClassName( static::class );

        return $viewAttribute;
    }

    /**
     * @param ?string $filename
     *
     * @return string
     */
    final protected function getTemplatePath( ?string $filename = null ) : string
    {
        $this->templateFilename ??= $this::getComponentName();

        $filename = str_end( $this->templateFilename, '.latte' );

        $path = normalize_path(
            self::TEMPLATE_DIRECTORY ?? ( new ReflectionClass( static::class ) )->getFileName(),
        );

        if ( \str_ends_with( $path, '.php' ) ) {
            $path = \strrchr( $path, DIR_SEP, true );
        }

        if ( \file_exists( "{$path}/{$filename}" ) ) {
            return "{$path}/{$filename}";
        }

        $templateDirectory = ( \strrchr( $path, DIR_SEP.'src', true ) ?: $path ).DIR_SEP.'templates';

        if ( \file_exists( "{$templateDirectory}/component/{$filename}" ) ) {
            $this->getEngine()->addTemplateDirectory( $templateDirectory, $this->name );
            return "component/{$filename}";
        }

        throw new LogicException(
            'Unable to resolve template directory for component '.static::class.'.',
        );
    }

    final protected function getEngine() : Engine
    {
        return $this->engine ??= new Engine( cache : false );
    }

    protected function getTemplate() : string
    {
        return $this->getTemplatePath();
    }

    /**
     * @return array<string, mixed>|object
     */
    protected function getParameters() : array|object
    {
        return $this;
    }

    private function componentUniqueID( string $set ) : string
    {
        // Set a predefined hash
        if ( \strlen( $set ) === 8 ) {
            if ( \ctype_alnum( $set ) || \strtolower( $set ) === $set ) {
                return $set;
            }

            $this->logger?->error(
                'Invalid component unique ID {set}. Expected 8 characters of alphanumeric, lowercase.',
                ['set' => $set],
            );
        }

        return \hash( 'xxh32', $set );
    }

    /**
     * @param array<string, mixed> $arguments
     *
     * @return Attributes
     */
    private function assignAttributes( array &$arguments ) : Attributes
    {
        /** @var array<string, null|array<array-key, string>|bool|int|string> $attributes */
        $attributes = $arguments['attributes'] ?? [];

        unset( $arguments['attributes'] );

        return new Attributes( ...$attributes );
    }

    /**
     * @param array<string, mixed>     $arguments
     * @param array<string, ?string[]> $promote
     *
     * @return void
     */
    private function promoteTaggedProperties( array &$arguments, array $promote = [] ) : void
    {
        if ( ! isset( $arguments['tag'] ) ) {
            return;
        }

        \assert( \is_string( $arguments['tag'] ) );

        /** @var array<int, string> $exploded */
        $exploded         = \explode( ':', $arguments['tag'] );
        $arguments['tag'] = $exploded[0];

        $promote = $promote[$arguments['tag']] ?? null;

        foreach ( $exploded as $position => $tag ) {
            if ( $promote && ( $promote[$position] ?? false ) ) {
                $arguments[$promote[$position]] = $tag;
                unset( $arguments[$position] );

                continue;
            }
            if ( $position ) {
                $arguments[$position] = $tag;
            }
        }

        unset( $arguments['content'], $arguments['tag'] );

        foreach ( $arguments as $property => $value ) {
            if ( \property_exists( $this, $property ) ) {
                if ( ! isset( $this->{$property} ) ) {
                    $this->{$property} = $value;
                }
                else {
                    $this->{$property} = match ( \gettype( $this->{$property} ) ) {
                        'boolean' => (bool) $value,
                        'integer' => (int) $value,
                        default   => $value,
                    };
                }

                continue;
            }

            \assert( \is_string( $value ), 'All remaining arguments should be method calls at this point.' );

            $method = 'do'.\ucfirst( $value );

            if ( \method_exists( $this, $method ) ) {
                $this->{$method}();
            }
            else {
                $this->logger?->error(
                    'The {component} was provided with undefined property {property}.',
                    ['component' => $this->name, 'property' => $property],
                );
            }
        }
    }
}
