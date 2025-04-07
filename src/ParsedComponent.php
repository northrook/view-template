<?php

declare(strict_types=1);

namespace Core\View\Template;

use BadMethodCallException;
use Cache\CachePoolTrait;
use Core\Profiler\ClerkProfiler;
use Core\View\Attribute\ViewComponent;
use Core\View\Element\Attributes;
use Core\View\Template\Component\NodeParser;
use Core\View\Template\Compiler\{Nodes\ComponentNode};
use Core\View\Template\Compiler\Nodes\Html\ElementNode;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use InvalidArgumentException;
use LogicException;
use function Support\{normalize_path, slug, str_end};

abstract class ParsedComponent
{
    use CachePoolTrait;

    /** @var ?string Manually define a name for this component */
    protected const ?string NAME = null;

    protected const ?string TEMPLATE_DIRECTORY = null;

    private ?Engine $engine = null;

    protected ?string $templateFilename;

    /** @var array<string,null|scalar|scalar[]> */
    protected readonly array $settings;

    protected readonly ?ClerkProfiler $profiler;

    protected readonly ?LoggerInterface $logger;

    public string $tag;

    public readonly string $name;

    public readonly string $uniqueID;

    public readonly Attributes $attributes;

    public function render() : string
    {
        try {
            return $this->getComponentNode()->simplify()->print();
        }
        catch ( Exception\CompileException $exception ) {
            $this->logger?->critical( $exception->getMessage(), ['exception' => $exception] );
            return $this->getString();
        }
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

    /**
     * Process arguments passed to the {@see self::create()} method.
     *
     * @param array{'tag': ?string,'attributes' : array<string, null|array<array-key, ?string>|bool|float|int|string>, 'content': null|string} $arguments
     *
     * @return void
     */
    protected function prepareArguments( array &$arguments ) : void {}

    /**
     * @param array{'tag': ?string,'attributes' : array<string, null|array<array-key, ?string>|bool|float|int|string>, 'content': null|string}|ElementNode $arguments
     * @param array<string, ?string[]>                                                                                                                     $promote
     * @param null|string                                                                                                                                  $uniqueId  8 character hash key
     *
     * @return $this
     */
    final public function create(
        array|ElementNode $arguments,
        array             $promote = [],
        ?string           $uniqueId = null,
    ) : self {
        if ( $arguments instanceof ElementNode ) {
            $arguments = ParsedComponent::nodeArguments( $arguments );
        }

        $this->prepareArguments( $arguments );

        $this->name       = $this::componentName();
        $this->uniqueID   = $this->componentUniqueID( $uniqueId ?? \serialize( [$arguments] ) );
        $this->attributes = $this->assignAttributes( $arguments );

        $this->promoteTaggedProperties( $arguments, $promote );

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

        return $this;
    }

    /**
     * @param ?ElementNode $node
     * @param array        $tags
     *
     * @return ComponentNode
     * @throws Exception\CompileException
     */
    final public function getComponentNode( ?ElementNode $node = null, array $tags = [] ) : ComponentNode
    {
        $tags[$node->name] ??= true;
        $tags = \array_keys( $tags );
        $tags = \implode( '|', $tags );

        $template = (string) \preg_replace_callback(
            pattern  : "#<({$tags})(\b[^>]*?>)#m",
            callback : [$this, 'guaranteeComponentId'],
            subject  : $this->getString(),
            flags    : PREG_UNMATCHED_AS_NULL,
        );

        return new ComponentNode(
            $this->engine->parse( $template ),
            $node->position,
        );
    }

    /**
     * Ensure targeted native tags have the 'component-id' set
     * This is to prevent a recursion loop
     *
     * @param array{0:string, 1:string, 2: string} $_
     *
     * @return string
     */
    private function guaranteeComponentId( array $_ ) : string
    {
        /**
         * @var string $element '<$tag with="attributes">'
         * @var string $tag     opening '$tag'
         * @var string $tail    remaining ' with="attributes">'
         */
        [$element, $tag, $tail] = $_;

        // Bail early on existing component-id attribute
        if ( \str_contains( $element, ' component-id="' ) ) {
            return $element;
        }

        $element = "<{$tag} component-id=\"{$this->uniqueID}\"";

        if ( $tail[0] !== ' ' ) {
            $element .= ' ';
        }

        return $element.$tail;
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
    }

    final public function setDependencies(
        ?Engine                 $engine,
        ?CacheItemPoolInterface $cache = null,
        ?ClerkProfiler          $profiler = null,
        ?LoggerInterface        $logger = null,
        array                   $settings = [],
    ) : self {
        $this->engine   ??= $engine;
        $this->profiler ??= $profiler;
        $this->logger   ??= $logger;

        $this->assignCacheAdapter(
            adapter    : $cache,
            prefix     : slug( 'component.'.self::componentName(), '.' ),
            defer      : $this->settings['asset.cache.defer']      ?? true,         // defer save by default
            expiration : $this->settings['asset.cache.expiration'] ?? 14_400,  // 4 hours
        );

        return $this;
    }

    final public static function componentName() : string
    {
        $name = self::NAME ?? self::viewComponentAttribute()->name;

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

    /**
     * @param ElementNode|NodeParser $node
     *
     * @return array{tag: string, attributes: array<string, null|array<array-key, string>|bool|int|string>, content: ?array<array-key, string>}
     */
    public static function nodeArguments( NodeParser|ElementNode $node ) : array
    {
        if ( ! $node instanceof NodeParser ) {
            $node = new NodeParser( $node );
        }

        return [
            'tag'        => $node->tag,
            'attributes' => $node->attributes(),
            'content'    => $node->getContent(),
        ];
    }

    final public static function viewComponentAttribute() : ViewComponent
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
        $this->templateFilename ??= $this::componentName();

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
}
