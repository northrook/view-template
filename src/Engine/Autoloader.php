<?php

declare(strict_types=1);

namespace Core\View\Template\Engine;

use Cache\{CachePoolTrait, LocalStorage};
use Core\View\Template\Exception\{CompileException, TemplateException};
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Stringable;
use SplFileInfo;
use function Support\{key_hash, slug, str_includes};

/**
 * @internal
 */
final class Autoloader
{
    use CachePoolTrait;

    /**
     * The autoloader will first match preloaded string `$templates` by `name`,
     * then search `$directories` until a match is found.
     *
     * @param string[]                           $directories
     * @param array<string,string>               $templates
     * @param null|CacheItemPoolInterface|string $cache
     */
    public function __construct(
        protected array &                    $directories = [],
        protected array                    $templates = [],
        null|string|CacheItemPoolInterface $cache,
    ) {
        if ( \is_string( $cache ) ) {
            $cache = new LocalStorage( "{$cache}/autoloader_map.php" );
        }
        $this->assignCacheAdapter( $cache );
    }

    public function getContent( string $name ) : string
    {
        // Early return for obvious raw strings
        if ( str_includes( $name, '< >' ) ) {
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

        return $this->getCache(
            'name.'.key_hash( 'xxh32', $name, $referringName ),
            fn() => $this->resolveReferredName( $name, $referringName ),
        );
    }

    private function resolveReferredName( string $name, string $referringName ) : string
    {
        // Absolute paths
        if ( $name[0] === '/' || $name[0] === '\\' ) {
            return $this->normalizePath( $referringName.'/../'.$name );
        }

        // scheme: prefixed paths
        if ( \ctype_alpha( $name[0] )
             && \str_contains( $name, ':' )
             && \ctype_alnum( \str_replace( ['+', '.', '-'], '', \strstr( $name, ':', true ) ) )
        ) {
            return $this->normalizePath( $referringName.'/../'.$name );
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
        // Return full valid paths early
        if ( \is_readable( $template ) && \is_file( $template ) ) {
            return $template;
        }

        return $this->getCache(
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

            $fileInfo = new SplFileInfo( $this->normalizePath( "{$directory}/{$template}" ) );

            if ( $fileInfo->isFile() ) {
                return $fileInfo->getPathname();
            }
        }

        foreach ( $this->directories as $directory ) {
            $fileInfo = new SplFileInfo( $this->normalizePath( "{$directory}/{$template}" ) );

            if ( $fileInfo->isReadable() ) {
                return $fileInfo->getPathname();
            }
        }

        return false;
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
            if ( \is_int( $name ) ) {
                throw new InvalidArgumentException( 'Template name must be a string.' );
            }
            if ( ! \is_string( $template ) || ! $template instanceof Stringable ) {
                throw new InvalidArgumentException( 'Templates must be a string.' );
            }
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

    // :: UTILITY

    private function normalizePath( string $path ) : string
    {
        $fragments = [];

        foreach ( \explode( '/', \strtr( $path, '\\', '/' ) ) as $fragment ) {
            if ( $fragment === '..' && $fragments && \end( $fragments ) !== '..' ) {
                \array_pop( $fragments );
            }
            elseif ( $fragment !== '.' ) {
                $fragments[] = $fragment;
            }
        }

        return \implode( DIR_SEP, \array_filter( $fragments ) );
    }
}
