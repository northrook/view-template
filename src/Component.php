<?php

declare(strict_types=1);

namespace Core\View\Template;

use BadMethodCallException;
use Cache\CachePoolTrait;
use Core\Profiler\ClerkProfiler;
use Core\View\Attribute\ViewComponent;
use Core\View\Element;
use Core\View\Element\Attributes;
use Core\View\Template\Compiler\{Nodes\AreaNode, Nodes\ComponentNode, Position};
use Core\View\Template\Compiler\Nodes\FragmentNode;
use Core\View\Template\Compiler\Nodes\Html\ElementNode;
use Core\View\Template\Component\Node\StaticNode;
use Core\View\Template\Support\NewNode;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use InvalidArgumentException;
use function Support\{normalize_path, slug};
use LogicException;
use Throwable;

abstract class Component
{
    use CachePoolTrait;

    /** @var ?string Manually define a name for this component */
    protected const ?string NAME = null;

    protected const ?string TEMPLATE_DIRECTORY = null;

    // ::DEBUG
    public static int $max_iterations = 0;
    // ::DEBUG

    private ?Engine $engine = null;

    /** @var array<string,null|scalar|scalar[]> */
    protected readonly array $settings;

    protected readonly ?ClerkProfiler $profiler;

    protected readonly ?LoggerInterface $logger;

    public readonly string $name;

    public readonly string $uniqueID;

    public readonly Attributes $attributes;

    public function render() : string
    {
        return $this->getComponentNode()->simplify()->print();
    }

    abstract protected function getTemplateParameters() : array|object;

    public function getComponentNode() : ComponentNode
    {
        self::$max_iterations++;
        $engine = $this->getEngine();

        if ( self::$max_iterations > 5 ) {
            dd( \get_defined_vars(), self::$max_iterations );
        }
        try {
            $template = \trim(
                $engine->renderToString(
                    $this->getTemplatePath(),
                    $this->getTemplateParameters(),
                ),
            );
            $ast = $engine->parse( $template );
            // $node     = $ast->main;
            return new ComponentNode( ...$ast->main->children );
        }
        catch ( Throwable $e ) {
            dd( $this, ...\get_defined_vars() );
        }
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
     * @param null|Position    $position
     * @param null|ElementNode $parent
     *
     * @return ElementNode
     */
    public function getElementNode(
        ?Position    $position = null,
        ?ElementNode $parent = null,
    ) : AreaNode {
        $view = new Element();

        $element = NewNode::element(
            name       : $view->tag->getTagName(),
            position   : $position,
            parent     : $parent,
            attributes : $view->attributes,
        );

        \assert( $element->content instanceof FragmentNode );

        $element->content->append( new StaticNode( $view->content->getString() ) );

        return $element;
    }

    /**
     * @param array{'tag': ?string,'attributes' : array<string, null|array<array-key, ?string>|bool|float|int|string>, 'content': null|string} $arguments
     * @param array<string, ?string[]>                                                                                                         $promote
     * @param null|string                                                                                                                      $uniqueId  8 character hash key
     *
     * @return $this
     */
    final public function create(
        array   $arguments,
        array   $promote = [],
        ?string $uniqueId = null,
    ) : self {
        $this->name       = $this::componentName();
        $this->attributes = new Attributes();
        $this->prepareArguments( $arguments );
        $this->uniqueID = $this->componentUniqueID( $uniqueId ?? \serialize( [$arguments] ) );

        // dump( $this, ...\get_defined_vars() );
        return $this;
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
        $filename ??= "{$this::componentName()}.latte";

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
            $this->getEngine()->addTemplateDirectory( $templateDirectory );
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

    private function componentUniqueID( string $set ) : string
    {
        // Set a predefined hash
        if ( \strlen( $set ) === 8
             && \ctype_alnum( $set )
             && \strtolower( $set ) === $set
        ) {
            return $set;
        }
        return \hash( 'xxh32', $set );
    }
}
