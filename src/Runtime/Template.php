<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template\Runtime;

use Core\View\Template\{ContentType,
    Engine,
    Exception\CompileException,
    Exception\TemplateException,
    Support\Helpers,
    Exception\RuntimeException
};
use Core\View\Template\Compiler\Escaper;
use Closure;
use Throwable;
use stdClass;

/**
 * Template.
 */
abstract class Template
{
    public const ContentType ContentType = ContentType::HTML;

    public const int LAYER_TOP = 0;

    public const string LAYER_SNIPPET = 'snippet';

    public const string LAYER_LOCAL = 'local';

    public const array BLOCKS = [];

    public const null|string SOURCE = null;

    /** @internal */
    protected string|false|null $parentName = null;

    /** @var array[] */
    protected array $varStack = [];

    /** @var Block[][] */
    protected array $blocks;

    /** @var array[] */
    private array $blockStack = [];

    private ?Template $referringTemplate = null;

    private ?string $referenceType = null;

    /**
     * @param Engine         $engine
     * @param array          $parameters
     * @param FilterExecutor $filters
     * @param stdClass       $global     `providers`
     * @param string         $name
     */
    final public function __construct(
        public readonly Engine   $engine,
        protected array          $parameters,
        protected FilterExecutor $filters,
        public stdClass          $global,
        public readonly string   $name,
    ) {
        $this->initBlockLayer( self::LAYER_TOP );
        $this->initBlockLayer( self::LAYER_LOCAL );
        $this->initBlockLayer( self::LAYER_SNIPPET );
    }

    public function getEngine() : Engine
    {
        return $this->engine;
    }

    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Returns array of all parameters.
     *
     * @return array
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }

    /**
     * @param int|string $layer
     *
     * @return string[]
     */
    public function getBlockNames( int|string $layer = self::LAYER_TOP ) : array
    {
        return \array_keys( $this->blocks[$layer] ?? [] );
    }

    public function getParentName() : ?string
    {
        return $this->parentName ?: null;
    }

    public function getReferringTemplate() : ?self
    {
        return $this->referringTemplate;
    }

    public function getReferenceType() : ?string
    {
        return $this->referenceType;
    }

    /**
     * Renders template.
     *
     * @internal
     *
     * @param ?string $block
     *
     * @throws Throwable
     */
    public function render( ?string $block = null ) : void
    {
        foreach ( $this->engine->getExtensions() as $extension ) {
            $extension->beforeRender( $this );
        }

        $parameters = $this->prepare();

        if ( $this->parentName === null && ! $this->referringTemplate && isset( $this->global->coreParentFinder ) ) {
            $this->parentName = ( $this->global->coreParentFinder )( $this );
        }

        if ( $this->referenceType === 'import' ) {
            if ( $this->parentName ) {
                throw new RuntimeException(
                    'Imported template cannot use {extends} or {layout}, use {import}',
                );
            }
        }
        elseif ( $this->parentName ) { // extends
            $this->parameters = $parameters;
            $this->createTemplate( $this->parentName, $parameters, 'extends' )->render( $block );
        }
        elseif ( $block !== null ) { // single block rendering
            $this->renderBlock( $block, $this->parameters );
        }
        else {
            $this->main( $parameters );
        }
    }

    /**
     * Renders template.
     *
     * @internal
     *
     * @param string $name
     * @param array  $params
     * @param string $referenceType
     *
     * @return Template
     * @throws CompileException
     */
    final public function createTemplate( string $name, array $params, string $referenceType ) : self
    {
        $name = $this->engine->getLoader()->getReferredName( $name, $this->name );

        $referred = $referenceType === 'sandbox'
                ? ( clone $this->engine )->setSandboxMode()->createTemplate( $name, $params )
                : $this->engine->createTemplate( $name, $params, preserveCacheKey : true );

        $referred->referringTemplate = $this;
        $referred->referenceType     = $referenceType;
        $referred->global            = $this->global;

        if ( \in_array( $referenceType, ['extends', 'includeblock', 'import', 'embed'], true ) ) {
            foreach ( $referred->blocks[self::LAYER_TOP] as $nm => $block ) {
                $this->addBlock( $nm, $block->contentType, $block->functions );
            }

            $referred->blocks[self::LAYER_TOP] = &$this->blocks[self::LAYER_TOP];

            $this->blocks[self::LAYER_SNIPPET] += $referred->blocks[self::LAYER_SNIPPET];
            $referred->blocks[self::LAYER_SNIPPET] = &$this->blocks[self::LAYER_SNIPPET];
        }

        return $referred;
    }

    /**
     * @internal
     *
     * @param null|Closure|string $mod   content-type name or modifier closure
     * @param ?string             $block
     *
     * @throws Throwable
     */
    public function renderToContentType( string|Closure|null $mod, ?string $block = null ) : void
    {
        $this->filter(
            fn() => $this->render( $block ),
            $mod,
            static::ContentType,
            "'{$this->name}'",
        );
    }

    /**
     * @return array<array-key, mixed>
     */
    public function prepare() : array
    {
        return $this->parameters;
    }

    /**
     * @param array<array-key, mixed> $__args__
     */
    abstract public function main( array $__args__ ) : void;

    /**
     * Renders block.
     *
     * @internal
     *
     * @param string              $name
     * @param array               $params
     * @param null|Closure|string $mod    content-type name or modifier closure
     * @param null|int|string     $layer
     *
     * @throws Throwable
     * @throws Throwable
     */
    public function renderBlock(
        string              $name,
        array               $params,
        string|Closure|null $mod = null,
        int|string|null     $layer = null,
    ) : void {
        $block = $layer
                ? ( $this->blocks[$layer][$name] ?? null )
                : ( $this->blocks[self::LAYER_LOCAL][$name] ?? $this->blocks[self::LAYER_TOP][$name] ?? null );

        if ( ! $block ) {
            $hint = $layer && ( $t = Helpers::getSuggestion( $this->getBlockNames( $layer ), $name ) )
                    ? ", did you mean '{$t}'?"
                    : '.';
            $name = $layer ? "{$layer} {$name}" : $name;
            throw new RuntimeException( "Cannot include undefined block '{$name}'{$hint}" );
        }

        $this->filter(
            fn() => \reset( $block->functions )( $params ),
            $mod,
            $block->contentType,
            "block {$name}",
        );
    }

    /**
     * Renders parent block.
     *
     * @internal
     *
     * @param string $name
     * @param array  $params
     */
    public function renderBlockParent( string $name, array $params ) : void
    {
        $block = $this->blocks[self::LAYER_LOCAL][$name] ?? $this->blocks[self::LAYER_TOP][$name] ?? null;
        if ( ! $block || ( $function = \next( $block->functions ) ) === false ) {
            throw new RuntimeException( "Cannot include undefined parent block '{$name}'." );
        }
        $function( $params );
        \prev( $block->functions );
    }

    /**
     * Captures output to string.
     *
     * @internal
     *
     * @param callable $function
     *
     * @return string
     */
    final public function capture( callable $function ) : string
    {
        try {
            \ob_start( fn() => '' );
            $function();
            return \ob_get_clean();
        }
        catch ( Throwable $ex ) {
            \ob_end_clean();
            throw new TemplateException( $ex->getMessage(), __METHOD__, previous : $ex );
        }
    }

    /**
     * Creates block if it doesn't exist and checks if content type is the same.
     *
     * @internal
     *
     * @param string             $name
     * @param ContentType|string $contentType
     * @param callable[]         $functions
     * @param null|int|string    $layer
     */
    protected function addBlock(
        string             $name,
        string|ContentType $contentType,
        array              $functions,
        int|string|null    $layer = null,
    ) : void {
        $block = &$this->blocks[$layer ?? self::LAYER_TOP][$name];
        $block ??= new Block();
        if ( $block->contentType === null ) {
            $block->contentType = ContentType::from( $contentType );
        }
        elseif ( ! Escaper::getConvertor( $contentType, $block->contentType ) ) {
            throw new RuntimeException(
                \sprintf(
                    "Overridden block {$name} with content type %s by incompatible type %s.",
                    $contentType->name,
                    $block->contentType->name,
                ),
            );
        }

        $block->functions = \array_merge( $block->functions, $functions );
    }

    /**
     * @param callable            $function
     * @param null|Closure|string $mod         content-type name or modifier closure
     * @param ContentType|string  $contentType
     * @param string              $name
     *
     * @throws Throwable
     */
    private function filter(
        callable            $function,
        string|Closure|null $mod,
        string|ContentType  $contentType,
        string              $name,
    ) : void {
        if ( $mod === null || $mod === $contentType ) {
            $function();
        }
        elseif ( $mod instanceof Closure ) {
            echo $mod( $this->capture( $function ), $contentType );
        }
        elseif ( $filter = Escaper::getConvertor( $contentType, $mod ) ) {
            echo $filter( $this->capture( $function ) );
        }
        else {
            throw new TemplateException(
                \sprintf(
                    "Including {$name} with content type %s into incompatible type %s.",
                    $contentType->name,
                    \strtoupper( $mod ),
                ),
                __METHOD__,
            );
        }
    }

    private function initBlockLayer( int|string $staticId, ?int $destId = null ) : void
    {
        $destId ??= $staticId;
        $this->blocks[$destId] = [];

        foreach ( static::BLOCKS[$staticId] ?? [] as $nm => $info ) {
            [$method, $contentType] = \is_array( $info ) ? $info : [$info, static::ContentType];
            $this->addBlock( $nm, $contentType, [[$this, $method]], $destId );
        }
    }

    protected function enterBlockLayer( int $staticId, array $vars ) : void
    {
        $this->blockStack[] = $this->blocks[self::LAYER_TOP];
        $this->initBlockLayer( $staticId, self::LAYER_TOP );
        $this->varStack[] = $vars;
    }

    protected function copyBlockLayer() : void
    {
        foreach ( \end( $this->blockStack ) as $nm => $block ) {
            $this->addBlock( $nm, $block->contentType, $block->functions );
        }
    }

    protected function leaveBlockLayer() : void
    {
        $this->blocks[self::LAYER_TOP] = \array_pop( $this->blockStack );
        \array_pop( $this->varStack );
    }

    public function hasBlock( string $name ) : bool
    {
        return isset( $this->blocks[self::LAYER_LOCAL][$name] ) || isset( $this->blocks[self::LAYER_TOP][$name] );
    }
}
