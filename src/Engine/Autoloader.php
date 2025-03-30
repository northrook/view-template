<?php

declare(strict_types=1);

namespace Core\View\Template\Engine;

use Core\View\Template\Exception\{CompileException, TemplateException};
use Core\Pathfinder\Path;
use InvalidArgumentException;
use Stringable;
use SplFileInfo;
use function Support\{str_includes};

/**
 * @internal
 */
final class Autoloader
{
    /**
     * The autoloader will first match preloaded string `$templates` by `name`,
     * then search `$directories` until a match is found.
     *
     * @param string[]             $directories
     * @param array<string,string> $templates
     */
    public function __construct(
        private readonly array $directories = [],
        private array          $templates = [],
    ) {}

    public function getContent( string $name ) : string
    {
        // Early return for obvious raw strings
        if ( str_includes( $name, '< >' ) ) {
            return $name;
        }

        // Return valid preloaded templates
        if ( $this->hasTemplate( $name ) ) {
            return $this->templates[$name];
        }

        $template = \file_get_contents( $this->templatePath( $name ) );

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
        if ( $this->hasTemplate( $name ) ) {
            return $name;
        }

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

    protected function templatePath( string $template ) : string
    {
        // Return full valid paths early
        if ( \is_readable( $template ) && \is_file( $template ) ) {
            return $template;
        }

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

            $fileInfo = new SplFileInfo( "{$directory}/{$template}" );

            if ( $fileInfo->isFile() ) {
                return $fileInfo->getPathname();
            }
        }

        foreach ( $this->directories as $directory ) {
            $fileInfo = new SplFileInfo( "{$directory}/{$template}" );

            if ( $fileInfo->isReadable() ) {
                return $fileInfo->getPathname();
            }
        }

        $message = "Unable to load view: '{$template}', it could not be found in any provided source.";

        throw new TemplateException( $message, __METHOD__ );
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

    public function hasTemplate( string $name ) : bool
    {
        return isset( $this->templates[$name] );
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
        $res = [];

        foreach ( \explode( '/', \strtr( $path, '\\', '/' ) ) as $part ) {
            if ( $part === '..' && $res && \end( $res ) !== '..' ) {
                \array_pop( $res );
            }
            elseif ( $part !== '.' ) {
                $res[] = $part;
            }
        }

        $path = \implode( DIR_SEP, $res );

        dump( $path );

        return $path;
    }
}
