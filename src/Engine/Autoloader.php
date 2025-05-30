<?php

declare(strict_types=1);

namespace Core\View\Template\Engine;

use Cache\{CacheHandler, LocalStorage};
use Core\View\Template\Exception\{CompileException, TemplateException};
use Psr\Cache\CacheItemPoolInterface;
use Stringable;
use SplFileInfo;
use function Support\{is_path, key_hash, normalize_path, slug, str_includes_any};

/**
 * @used-by Engine
 */
final class Autoloader
{
    private readonly CacheHandler $cache;

    /**
     * The autoloader will first match preloaded string `$templates` by `name`,
     * then search `$directories` until a match is found.
     *
     * @param string[]                           $directories
     * @param array<string,string>               $templates
     * @param null|CacheItemPoolInterface|string $cache
     */
    public function __construct(
        protected array                    $directories = [],
        protected array                    $templates = [],
        null|string|CacheItemPoolInterface $cache = null,
    ) {
        if ( \is_string( $cache ) ) {
            \assert(
                is_path( $cache ) && ! \pathinfo( $cache, PATHINFO_EXTENSION ),
                "Autoloader( cache: {$cache} ) string arguments must be a path to a directory.",
            );
            $cache = new LocalStorage( "{$cache}/autoloader_map.php" );
        }
        $this->cache = new CacheHandler( $cache );
    }

    public function getContent( string $name ) : string
    {
        // Early return for obvious raw strings
        if ( str_includes_any( $name, '< >' ) ) {
            return $name;
        }

        // Return valid preloaded templates
        if ( isset( $this->templates[$name] ) ) {
            return $this->templates[$name];
        }

        $filePath = $this->templatePath( $name );

        if ( ! $filePath ) {
            $message = "Unable to load view: '{$name}', it could not be found in any provided source.";
            throw new TemplateException( $message, __METHOD__ );
        }

        $template = \file_get_contents( $filePath );

        if ( $template ) {
            return $this->templates[$name] = $template;
        }

        throw new TemplateException(
            $template === false
                        ? "Unable to load template '{$name}' file."
                        : "Template '{$name}' resolved as empty string.",
            __METHOD__,
        );
    }

    /**
     * Returns the path/template to a relative template, used by the {@see Template::createTemplate()}
     * when rendering nested or extended templates and blocks.
     *
     * @param string $name
     * @param string $referringName
     *
     * @return string
     * @throws CompileException
     */
    public function getReferredName(
        string $name,
        string $referringName,
    ) : string {
        // Throw on empty template names, this should never happen
        if ( ! $name ) {
            throw new CompileException( 'An empty template name was provided.' );
        }

        // Return matched preloaded string templates early
        if ( isset( $this->templates[$name] ) ) {
            return $name;
        }

        return $this->cache->get(
            'name.'.key_hash( 'xxh32', $name, $referringName ),
            fn() => $this->resolveReferredName( $name, $referringName ),
        );
    }

    private function resolveReferredName( string $name, string $referringName ) : string
    {
        // Absolute paths
        if ( $name[0] === '/' || $name[0] === '\\' ) {
            return normalize_path(
                path      : "{$referringName}..{$name}",
                traversal : true,
            );
        }

        // scheme: prefixed paths
        if ( \ctype_alpha( $name[0] )
             && \str_contains( $name, ':' )
             && \ctype_alnum( \str_replace( ['+', '.', '-'], '', \strstr( $name, ':', true ) ) )
        ) {
            return normalize_path(
                path      : $referringName.'/../'.$name,
                traversal : true,
            );
        }

        return $name;
    }

    /**
     * @param string       $name
     * @param false|string $hash
     *
     * @return string
     */
    public function getUniqueId(
        string       $name,
        false|string $hash = 'xxh32',
    ) : string {
        $id = $this->getContent( $name );
        return $hash ? \hash( $hash, $id ) : $id;
    }

    public function templateExists( string $template ) : bool
    {
        try {
            return (bool) (
                $this->templates[$template] ?? $this->templatePath( $template )
            );
        }
        catch ( TemplateException ) {
            return false;
        }
    }

    public function templatePath( string $template ) : false|string
    {
        $template = \str_ends_with( $template, '.latte' ) ? $template : $template.'.latte';

        // Return full valid paths early
        if ( \is_readable( $template ) && \is_file( $template ) ) {
            return $template;
        }

        return $this->cache->get(
            slug( "path.{$template}", '.' ),
            fn() => $this->resolveTemplatePath( $template ),
        );
    }

    private function resolveTemplatePath( string $template ) : false|string
    {
        if ( \str_starts_with( $template, '@' ) ) {
            if ( ! \str_contains( $template, '/' ) ) {
                $message = 'Namespaced view calls must use the forward slash separator.';
                throw new TemplateException( $message, __METHOD__ );
            }

            [$namespace, $template] = \explode( '/', $template, 2 );

            $directory = $this->directories[$namespace] ?? null;

            if ( ! $directory ) {
                $message = 'No directory set for namespace "'.$namespace.'".';
                throw new TemplateException( $message, __METHOD__ );
            }

            $fileInfo = new SplFileInfo( normalize_path( "{$directory}/{$template}" ) );

            if ( $fileInfo->isFile() ) {
                return $fileInfo->getPathname();
            }
        }

        if ( \str_starts_with( $template, 'templates'.DIR_SEP ) ) {
            $template = \substr( $template, 10 );
        }

        foreach ( $this->directories as $directory ) {
            $fileInfo = new SplFileInfo( normalize_path( "{$directory}/{$template}" ) );

            if ( $fileInfo->isReadable() ) {
                return $fileInfo->getPathname();
            }
        }

        return false;
    }

    /**
     * @param string          $directory
     * @param null|int|string $key
     *
     * @return $this
     */
    public function addDirectory( string $directory, null|int|string $key = null ) : self
    {
        if ( $key ) {
            $this->directories[$key] ??= $directory;
        }
        else {
            $this->directories[] = $directory;
        }
        return $this;
    }

    // :: Preloaded templates

    /**
     * @param array<string,string> $templates
     *
     * @return $this
     */
    public function setTemplates( array $templates ) : self
    {
        foreach ( $templates as $name => $template ) {
            \assert(
                \is_int( $name ),
                'Template name must be a string.',
            );

            \assert(
                \is_string( $template ) || $template instanceof Stringable,
                'Templates must be a string.',
            );

            $this->templates[$name] = (string) $template;
        }
        return $this;
    }

    public function addTemplate( string $name, string|Stringable $template ) : self
    {
        $this->templates[$name] = (string) $template;
        return $this;
    }

    public function removeTemplate( string $name ) : self
    {
        unset( $this->templates[$name] );
        return $this;
    }

    public function clearTemplates() : self
    {
        $this->templates = [];
        return $this;
    }
}
