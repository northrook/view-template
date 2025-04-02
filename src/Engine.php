<?php

/**
 * This file is part of the Latte (https://latte.nette.org)
 * Copyright (c) 2008 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Core\View\Template;

use Core\View\Template\Engine\{Autoloader, PreformatterExtension};
use Core\Interface\LazyService;
use Core\Profiler\Interface\Profilable;
use Core\Profiler\StopwatchProfiler;
use Core\View\Template\Sandbox\SandboxExtension;
use Exception;
use Latte\Loader;
use Psr\Log\{LoggerAwareInterface, LoggerInterface};
use Symfony\Component\Stopwatch\Stopwatch;
use Core\View\Template\Compiler\{TemplateFilter};
use Core\View\Template\Compiler\Nodes\TemplateNode;
use Core\View\Template\Engine\CoreExtension;
use Core\View\Template\Exception\{CompileException, RuntimeException, SecurityViolationException, TemplateException};
use Core\View\Template\Support\Helpers;
use Core\View\Template\Runtime\{FilterExecutor, FunctionExecutor, Template};
use Core\View\Template\Compiler\{PhpHelpers, TemplateFunction, TemplateGenerator, TemplateParser};
use Core\View\Template\Interface\Policy;
use LogicException;
use ReflectionClass;
use ReflectionMethod;
use Throwable;
use stdClass;
use Stringable;
use ReflectionAttribute;
use BadMethodCallException;
use ReflectionException;
use function Support\{class_id, file_purge, is_empty, is_path, key_hash, normalize_path, slug};
use const Support\AUTO;

class Engine implements LazyService, Profilable, LoggerAwareInterface
{
    use StopwatchProfiler;

    private ContentType $contentType = ContentType::HTML;

    private null|Autoloader|Loader $loader = null;

    private FunctionExecutor $functions;

    private FilterExecutor $filters;

    /** @var Extension[] */
    private array $extensions = [];

    private stdClass $providers;

    private ?Policy $policy = null;

    private bool $strictTypes = false;

    private bool $autoRefresh = true;

    private bool $strictParsing = false;

    private ?string $cacheDirectory = null;

    private bool $sandboxed = false;

    private ?string $phpBinary = null;

    private ?string $cacheKey;

    protected ?LoggerInterface $logger = null;

    protected bool $cache = true;

    // <editor-fold desc="Instantiation">

    /**
     * @param null|string             $cacheDirectory
     * @param array<array-key,string> $templateDirectories
     * @param array<string,string>    $preloadedTemplates
     * @param null|string             $locale
     * @param bool                    $preformatter
     */
    public function __construct(
        ?string                  $cacheDirectory = null,
        protected readonly array $templateDirectories = [],
        protected readonly array $preloadedTemplates = [],
        private ?string          $locale = null,
        private readonly bool    $preformatter = false,
    ) {
        $this->setCacheDirectory( $cacheDirectory );
        $this->filters   = new FilterExecutor();
        $this->functions = new FunctionExecutor();
        $this->providers = new stdClass();
        $this->addExtension( new CoreExtension() );
        if ( $this->preformatter ) {
            $this->addExtension( new PreformatterExtension() );
        }
    }

    /**
     * @param ?LoggerInterface $logger
     */
    final public function setLogger( ?LoggerInterface $logger ) : void
    {
        $this->logger = $logger;
    }

    final public function setProfiler( ?Stopwatch $stopwatch, ?string $category = 'View' ) : void
    {
        $this->assignProfiler( $stopwatch, $category );
    }

    // </editor-fold>

    // <editor-fold desc="Output">
    /**
     * Renders template to output.
     *
     * @param string       $name
     * @param array|object $parameters
     * @param ?string      $block
     * @param bool         $cache      [true]
     *
     * @throws Throwable
     */
    public function print(
        string       $name,
        object|array $parameters = [],
        ?string      $block = null,
        ?bool        $cache = AUTO,
    ) : void {
        $template = $this->createTemplate( $name, $this->templateParameters( $parameters ), $cache );

        $template->global->coreCaptured = false;
        $template->render( $block );
    }

    /**
     * Renders template to output.
     *
     * @param string       $template
     * @param array|object $parameters
     * @param bool         $cache      [true]
     *
     * @return string
     * @throws CompileException
     * @throws SecurityViolationException
     * @throws Throwable
     */
    public function render(
        string       $template,
        object|array $parameters = [],
        ?bool        $cache = AUTO,
    ) : string {
        $profiler = $this->profiler?->event( 'render' );
        $template = $this->createTemplate( $template, $parameters, $cache );

        $template->global->coreCaptured = true;

        $string = $template->capture( fn() => $template->render() );
        $profiler?->stop();
        return $string;
    }

    /**
     * Renders template to string.
     *
     * @param string       $name
     * @param array|object $parameters
     * @param ?string      $block
     * @param bool         $cache
     *
     * @return string
     * @throws CompileException
     * @throws Throwable
     */
    public function renderToString(
        string       $name,
        object|array $parameters = [],
        ?string      $block = null,
        ?bool        $cache = AUTO,
    ) : string {
        $profiler = $this->profiler?->event( 'render' );
        $template = $this->createTemplate( $name, $parameters, $cache );

        $template->global->coreCaptured = true;

        $string = $template->capture( fn() => $template->render( $block ) );
        $profiler?->stop();
        return $string;
    }

    /**
     * Creates template object.
     *
     * @internal
     *
     * @param string       $name
     * @param array|object $parameters
     * @param bool         $cache
     * @param bool         $preserveCacheKey
     *
     * @return Template
     * @throws CompileException
     * @throws SecurityViolationException
     */
    final public function createTemplate(
        string       $name,
        object|array $parameters = [],
        ?bool        $cache = AUTO,
        bool         $preserveCacheKey = false,
    ) : Template {
        if ( ! $preserveCacheKey ) {
            $this->cacheKey = null;
        }

        $name  = is_path( $name ) ? normalize_path( $name ) : $name;
        $class = $this->getTemplateClass( $name );
        if ( ! \class_exists( $class, false ) ) {
            $this->loadTemplate( $name, $cache );
        }

        $this->providers->fn = $this->functions;
        return new $class(
            $this,
            $this->templateParameters( $parameters ),
            $this->filters,
            $this->providers,
            $name,
        );
    }

    // </editor-fold>

    // <editor-fold desc="Configuration">

    /**
     * Sets locale for date and number formatting. See PHP intl extension.
     *
     * @param ?string $locale
     *
     * @return Engine
     */
    public function setLocale( ?string $locale ) : static
    {
        if ( $locale && ! \extension_loaded( 'intl' ) ) {
            throw new RuntimeException( "Locate requires the 'intl' extension to be installed." );
        }
        $this->locale = $locale;
        return $this;
    }

    public function getLocale() : ?string
    {
        return $this->locale;
    }

    public function setPolicy( ?Policy $policy ) : static
    {
        $this->policy = $policy;
        return $this;
    }

    public function getPolicy( bool $effective = false ) : ?Policy
    {
        return ! $effective || $this->sandboxed
                ? $this->policy
                : null;
    }

    public function setContentType( ContentType $type ) : static
    {
        $this->contentType = $type;
        return $this;
    }

    /**
     * Sets auto-refresh mode.
     *
     * @param bool $state
     *
     * @return Engine
     */
    public function setAutoRefresh( bool $state = true ) : static
    {
        $this->autoRefresh = $state;
        return $this;
    }

    /**
     * Enables declare(strict_types=1) in templates.
     *
     * @param bool $state
     *
     * @return Engine
     */
    public function setStrictTypes( bool $state = true ) : static
    {
        $this->strictTypes = $state;
        return $this;
    }

    public function setStrictParsing( bool $state = true ) : static
    {
        $this->strictParsing = $state;
        return $this;
    }

    public function isStrictParsing() : bool
    {
        return $this->strictParsing;
    }

    public function setExceptionHandler( callable $handler ) : static
    {
        $this->providers->coreExceptionHandler = $handler;
        return $this;
    }

    public function setSandboxMode( bool $state = true ) : static
    {
        $this->sandboxed = $state;
        return $this->addExtension( new SandboxExtension() );
    }

    final public function enablePhpLinter( ?string $phpBinary ) : static
    {
        $this->phpBinary = $phpBinary;
        return $this;
    }

    // </editor-fold>

    // <editor-fold desc="Compiler">

    /**
     * Compiles template to PHP code.
     *
     * @internal
     *
     * @param string $name
     *
     * @return string
     * @throws CompileException|SecurityViolationException
     */
    final public function compile( string $name ) : string
    {
        if ( $this->sandboxed && ! $this->policy ) {
            throw new LogicException( 'In sandboxed mode you need to set a security policy.' );
        }

        $template = $this->getLoader()->getContent( $name );

        try {
            $node = $this->parse( $template );
            $this->applyPasses( $node );
            $compiled = $this->generate( $node, $name );
        }
        catch ( Throwable $e ) {
            if ( ! $e instanceof CompileException && ! $e instanceof SecurityViolationException ) {
                $e = new CompileException( "Thrown exception '{$e->getMessage()}'", previous : $e );
            }

            throw $e->setSource( $template, $name );
        }

        if ( $this->phpBinary ) {
            PhpHelpers::checkCode( $this->phpBinary, $compiled, "(compiled {$name})" );
        }

        return $compiled;
    }

    /**
     * Parses template to AST node.
     *
     * @param string $template
     *
     * @return TemplateNode
     * @throws CompileException
     * @throws ReflectionException
     */
    final public function parse( string $template ) : TemplateNode
    {
        $parser         = new TemplateParser();
        $parser->strict = $this->strictParsing;

        foreach ( $this->extensions as $extension ) {
            $extension->beforeCompile( $this );
            $parser->addTags( $extension->getTags() );
        }

        return $parser
            ->setContentType( $this->contentType )
            ->setPolicy( $this->getPolicy( effective : true ) )
            ->parse( $template );
    }

    /**
     * Calls node visitors.
     *
     * @param TemplateNode $node
     */
    final protected function applyPasses( TemplateNode &$node ) : void
    {
        $passes = [];

        foreach ( $this->extensions as $extension ) {
            $passes = \array_merge( $passes, $extension->getPasses() );
        }

        $passes = Helpers::sortBeforeAfter( $passes );

        foreach ( $passes as $pass ) {
            $pass = $pass instanceof stdClass ? $pass->subject : $pass;
            ( $pass )( $node );
        }
    }

    /**
     * Generates compiled PHP code.
     *
     * @param TemplateNode $node
     * @param string       $name
     *
     * @return string
     * @throws CompileException
     * @throws SecurityViolationException
     */
    final protected function generate( TemplateNode $node, string $name ) : string
    {
        return ( new TemplateGenerator( $this->profiler ) )->generate(
            $node,
            $this->getTemplateClass( $name ),
            $name,
            $this->strictTypes,
        );
    }

    /**
     * @param array|object $parameters
     *
     * @return array
     */
    private function templateParameters( object|array $parameters ) : array
    {
        if ( \is_array( $parameters ) ) {
            return $parameters;
        }

        $methods = ( new ReflectionClass( $parameters ) )->getMethods( ReflectionMethod::IS_PUBLIC );

        foreach ( $methods as $method ) {
            if ( $method->getAttributes( TemplateFilter::class, ReflectionAttribute::IS_INSTANCEOF ) ) {
                $this->addFilter( $method->name, [$parameters, $method->name] );
            }
            if ( $method->getAttributes( TemplateFunction::class, ReflectionAttribute::IS_INSTANCEOF ) ) {
                $this->addFunction( $method->name, [$parameters, $method->name] );
            }

            $docblock = $method->getDocComment();

            if ( $docblock && \str_contains( $docblock, '@filter' ) ) {
                throw new TemplateException(
                    'Annotation @filter is deprecated, use attribute #['.TemplateFilter::class.'] instead.',
                    __METHOD__,
                    E_USER_DEPRECATED,
                );
            }

            if ( $docblock && \str_contains( $docblock, '@function' ) ) {
                throw new TemplateException(
                    'Annotation @function is deprecated, use attribute #['.TemplateFunction::class.'] instead.',
                    __METHOD__,
                    E_USER_DEPRECATED,
                );
            }
        }

        return \array_filter( (array) $parameters, fn( $key ) => $key[0] !== "\0", ARRAY_FILTER_USE_KEY );
    }

    /**
     * Registers run-time filter.
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return Engine
     */
    public function addFilter( string $name, callable $callback ) : static
    {
        if ( ! \preg_match( '#^[a-z]\w*$#iD', $name ) ) {
            throw new LogicException( "Invalid filter name '{$name}'." );
        }

        $this->filters->add( $name, $callback );
        return $this;
    }

    /**
     * Registers filter loader.
     *
     * @param callable $loader
     *
     * @return Engine
     */
    public function addFilterLoader( callable $loader ) : static
    {
        $this->filters->add( null, $loader );
        return $this;
    }

    /**
     * Returns all run-time filters.
     *
     * @return callable[]
     */
    public function getFilters() : array
    {
        return $this->filters->getAll();
    }

    /**
     * Call a run-time filter.
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function invokeFilter( string $name, array $args ) : mixed
    {
        return ( $this->filters->{$name} )( ...$args );
    }

    /**
     * Adds new extension.
     *
     * @param Extension $extension
     *
     * @return Engine
     */
    public function addExtension( Extension $extension ) : self
    {
        $id = class_id( $extension );

        if ( \array_key_exists( $id, $this->extensions ) ) {
            return $this;
        }

        $this->extensions[$id] = $extension;

        foreach ( $extension->getFilters() as $name => $value ) {
            $this->filters->add( $name, $value );
        }

        foreach ( $extension->getFunctions() as $name => $value ) {
            $this->functions->add( $name, $value );
        }

        foreach ( $extension->getProviders() as $name => $value ) {
            $this->providers->{$name} = $value;
        }
        return $this;
    }

    /**
     * @return Extension[]
     */
    public function getExtensions() : array
    {
        return $this->extensions;
    }

    /**
     * Registers run-time function.
     *
     * @param string   $name
     * @param callable $callback
     *
     * @return Engine
     */
    public function addFunction( string $name, callable $callback ) : static
    {
        if ( ! \preg_match( '#^[a-z]\w*$#iD', $name ) ) {
            throw new LogicException( "Invalid function name '{$name}'." );
        }

        $this->functions->add( $name, $callback );
        return $this;
    }

    /**
     * Call a run-time function.
     *
     * @param string $name
     * @param array  $args
     *
     * @return mixed
     */
    public function invokeFunction( string $name, array $args ) : mixed
    {
        return ( $this->functions->{$name} )( null, ...$args );
    }

    /**
     * @return callable[]
     */
    public function getFunctions() : array
    {
        return $this->functions->getAll();
    }

    /**
     * Adds new provider.
     *
     * @param string $name
     * @param mixed  $provider
     *
     * @return Engine
     */
    public function addProvider( string $name, mixed $provider ) : static
    {
        if ( ! \preg_match( '#^[a-z]\w*$#iD', $name ) ) {
            throw new LogicException( "Invalid provider name '{$name}'." );
        }

        $this->providers->{$name} = $provider;
        return $this;
    }

    /**
     * Returns all providers.
     *
     * @return array
     */
    public function getProviders() : array
    {
        return (array) $this->providers;
    }
    // </editor-fold>

    // <editor-fold desc="Loader">

    /**
     * @param null|Autoloader|Loader $loader
     *
     * @return $this
     */
    public function setLoader( null|Autoloader|Loader $loader ) : static
    {
        $this->loader = $loader;
        return $this;
    }

    /**
     * @return Autoloader|Loader
     */
    final public function getLoader() : Autoloader|Loader
    {
        return $this->loader ??= new Autoloader(
            $this->templateDirectories,
            $this->preloadedTemplates,
        );
    }

    /**
     * @param string $name
     * @param bool   $instantiate
     * @param ?bool  $cache
     *
     * @return class-string<Template>
     *
     * @throws CompileException
     * @throws SecurityViolationException
     */
    final public function getTemplateClass(
        string $name,
        bool   $instantiate = false,
        ?bool  $cache = AUTO,
    ) : string {
        $class = 'Template_'.$this->getCacheKey( $name );

        if ( $instantiate && ! \class_exists( $class, false ) ) {
            $this->loadTemplate( $name, $cache );
        }

        return $class;
    }

    /**
     * @param string $name
     * @param bool   $cache
     *
     * @throws CompileException|SecurityViolationException
     */
    private function loadTemplate( string $name, ?bool $cache ) : void
    {
        if ( ! $this->cacheDirectory || ! $cache ?? $this->cache ) {
            $compiled = $this->compile( $name );
            if ( @eval( \substr( $compiled, 5 ) ) === false ) { // @ is escalated to exception, substr removes <?php
                throw ( new CompileException( 'Error in template: '.\error_get_last()['message'] ) )
                    ->setSource( $compiled, "{$name} (compiled)" );
            }

            return;
        }

        // Solving atomicity to work everywhere is really pain in the ass.
        // 1) We want to do as little as possible IO calls on production and also directory and file can be not writable
        // so on Linux we include the file directly without shared lock, therefore, the file must be created atomically by renaming.
        // 2) On Windows file cannot be renamed-to while is open (ie by include), so we have to acquire a lock.
        $cacheFile = $this->getCacheFile( $name );
        $cacheKey  = $this->autoRefresh ? $this->getCacheSignature( $name ) : null;

        $lock = \defined( 'PHP_WINDOWS_VERSION_BUILD' ) || $this->autoRefresh
                ? $this->acquireLock( "{$cacheFile}.lock", LOCK_SH )
                : null;

        if (
            ! ( $this->autoRefresh && $cacheKey !== \stream_get_contents( $lock ) )
            && ( @include $cacheFile ) !== false // @ - file may not exist
        ) {
            return;
        }

        if ( $lock ) {
            \flock( $lock, LOCK_UN ); // release shared lock so we can get exclusive
            \fseek( $lock, 0 );
        }

        $lock = $this->acquireLock( "{$cacheFile}.lock", LOCK_EX );

        // while waiting for exclusive lock, someone might have already created the cache
        if ( ! \is_file( $cacheFile ) || ( $this->autoRefresh && $cacheKey !== \stream_get_contents( $lock ) ) ) {
            $compiled = $this->compile( $name );
            if (
                \file_put_contents( "{$cacheFile}.tmp", $compiled ) !== \strlen( $compiled )
                || ! \rename( "{$cacheFile}.tmp", $cacheFile )
            ) {
                @\unlink( "{$cacheFile}.tmp" ); // @ - file may not exist
                throw new RuntimeException( "Unable to create '{$cacheFile}'." );
            }

            \fseek( $lock, 0 );
            \fwrite( $lock, $cacheKey ?? $this->getCacheSignature( $name ) );
            \ftruncate( $lock, \ftell( $lock ) );

            if ( \function_exists( 'opcache_invalidate' ) ) {
                @\opcache_invalidate( $cacheFile, true ); // @ can be restricted
            }
        }

        if ( ( include $cacheFile ) === false ) {
            throw new RuntimeException( "Unable to load '{$cacheFile}'." );
        }

        \flock( $lock, LOCK_UN );
    }

    /**
     * @param string $file
     * @param int    $mode
     *
     * @return resource
     */
    private function acquireLock( string $file, int $mode )
    {
        $dir = \dirname( $file );
        if ( ! \is_dir( $dir ) && ! @\mkdir( $dir ) && ! \is_dir( $dir ) ) { // @ - dir may already exist
            throw new RuntimeException( "Unable to create directory '{$dir}'. ".\error_get_last()['message'] );
        }

        $handle = @\fopen( $file, 'c+' ); // @ is escalated to exception
        if ( ! $handle ) {
            throw new RuntimeException( "Unable to create file '{$file}'. ".\error_get_last()['message'] );
        }
        if ( ! @\flock( $handle, $mode ) ) { // @ is escalated to exception
            throw new RuntimeException(
                'Unable to acquire '.( $mode & LOCK_EX ? 'exclusive'
                            : 'shared' )." lock on file '{$file}'. ".\error_get_last()['message'],
            );
        }

        return $handle;
    }

    // </editor-fold>

    // ? Do not remove the old cached template, so it can be used as fallback on Exception
    // <editor-fold desc="Cache">

    /**
     * Sets path to temporary directory.
     *
     * @param ?string $path
     *
     * @return Engine
     */
    final public function setCacheDirectory( ?string $path ) : self
    {
        $this->cacheDirectory = $path ? normalize_path( $path ) : null;
        return $this;
    }

    final public function getCacheFile( string $name ) : string
    {
        $path = $this->cacheDirectory;
        if ( is_path( $name ) ) {
            $path .= DIR_SEP;
            $path .= slug(
                \implode( '.', \array_slice( \explode( DIR_SEP, $name ), -2 ) ),
            );
            $path .= '--';
        }
        return $path.$this->getCacheKey( $name ).'.php';
    }

    /**
     * Compiles template to cache.
     *
     * @param string $name
     *
     * @return bool
     */
    final public function warmupCache( string $name ) : bool
    {
        if ( ! $this->cacheDirectory ) {
            throw new LogicException( $this::class.' has no cache directory set.' );
        }

        try {
            $this->getTemplateClass( $name, true, true );
            return true;
        }
        catch ( Exception $exception ) {
            if ( $this->logger ) {
                $this->logger->error( $exception->getMessage() );
            }
            else {
                throw new TemplateException( $exception->getMessage(), __METHOD__ );
            }
            return false;
        }
    }

    final public function clearTemplateCache() : self
    {
        file_purge( $this->cacheDirectory );

        return $this;
    }

    final public function pruneTemplateCache() : array
    {
        throw new BadMethodCallException( __METHOD__.' not implemented yet.' );
    }

    /**
     * Returns an 8 character hash based on Template content and used Extensions.
     *
     * @param string $name
     *
     * @return string
     */
    final protected function getCacheSignature( string $name ) : string
    {
        $signature = \filemtime( __FILE__ );

        foreach ( $this->extensions as $extension ) {
            \assert( \class_exists( $extension::class ) );
            $signature ^= \filemtime(
                ( new ReflectionClass( $extension::class ) )->getFileName(),
            );
        }

        return \hash( 'xxh32', $signature.$this->getLoader()->getContent( $name ) );
    }

    /**
     * Generates a 16 character alphanumeric cache key.
     *
     * @param string $name
     *
     * @return string
     */
    final protected function getCacheKey( string $name ) : string
    {
        if ( ! $this->cacheKey ) {
            $fimemtime = 0;
            $signature = [
                $this->contentType,
                ...\array_keys( $this->getFunctions() ),
            ];

            foreach ( $this->extensions as $extension ) {
                \assert( \class_exists( $extension::class ) );
                $fimemtime ^= \filemtime(
                    ( new ReflectionClass( $extension::class ) )->getFileName(),
                );

                $signature[] = $extension::class;

                $extensionKey = $extension->getCacheKey( $this );

                if ( is_empty( $extensionKey ) ) {
                    continue;
                }

                $signature[] = match ( true ) {
                    $signature instanceof Stringable => $signature->__toString(),
                    \is_object( $extensionKey )      => $extensionKey::class,
                    default                          => $extensionKey,
                };
            }

            $signature[] = $fimemtime;

            $this->cacheKey = key_hash( 'xxh32', ...$signature );
        }

        return $this->cacheKey.key_hash( 'xxh32', $this->getLoader()->getUniqueId( $name ) );
    }
    // </editor-fold>)
}
