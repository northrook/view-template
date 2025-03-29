<?php

declare(strict_types=1);

namespace Core\View\Template\Support;

use Core\Interface\Printable;
use Stringable;
use Support\Time;
use function Support\{key_hash};

class PhpGenerator implements Printable
{
    private string $php = '';

    private string $generator;

    public readonly string $className;

    public readonly string $name;

    public readonly ?string $namespace;

    protected bool $abstract = false;

    protected bool $final = false;

    protected array $uses = [];

    protected array $extends = [];

    protected array $implements = [];

    protected array $traits = [];

    /** @var PhpConstant[] */
    protected array $constants = [];

    /** @var PhpProperty[] */
    protected array $properties = [];

    /** @var PhpMethod[] */
    protected array $methods = [];

    protected ?string $comment = null;

    public function __construct(
        string       $className,
        string|array $imports = [],
        ?string      $namespace = null,
        ?string      $generator = null,
        public bool  $strict = false,
    ) {
        $this->generator = $generator ?? $this::class;
        $this->className = $className;

        if ( $position = \strrpos( $className, '\\' ) ) {
            $namespace ??= \trim( \substr( $className, 0, $position ), " \n\r\t\v\0\\" );
            $className = \trim( \substr( $className, $position ), " \n\r\t\v\0\\" );
        }

        $this->name      = $className;
        $this->namespace = $namespace;

        $this->uses = \is_string( $imports ) ? [$imports] : $imports;
    }

    public function __toString() : string
    {
        $output = \explode(
            "\n",
            \str_replace(
                ["\r\n", "\r", "\n"],
                "\n",
                $this->generate(),
            ),
        );

        foreach ( $output as $line => $string ) {
            if ( \strspn( $string, " \t\0\x0B" ) === \strlen( $string ) ) {
                $output[$line] = '';

                continue;
            }

            if ( $string[0] === "\t" ) {
                $tabs          = \strspn( $string, " \t\0\x0B" );
                $output[$line] = \str_repeat( '    ', $tabs ).\substr( $string, $tabs );
            }
        }

        return $this->optimize( $output );
    }

    public static function dump( mixed $value, bool $multiline = false ) : string
    {
        if ( \is_array( $value ) ) {
            $indexed = $value && \array_keys( $value ) === \range( 0, \count( $value ) - 1 );
            $s       = '';

            foreach ( $value as $k => $v ) {
                $s .= $multiline
                        ? ( $s === '' ? "\n" : '' )."\t".( $indexed ? '' : self::dump( $k ).' => ' ).self::dump(
                            $v,
                        ).",\n"
                        : ( $s === '' ? '' : ', ' ).( $indexed ? '' : self::dump( $k ).' => ' ).self::dump( $v );
            }

            return '['.$s.']';
        }
        if ( $value === null ) {
            return 'null';
        }

        return \var_export( $value, true );
    }

    public static function optimize( Stringable|string|array $string ) : string
    {
        $string = \trim( (string) ( \is_array( $string ) ? \implode( "\n", $string ) : $string ) );

        $declaredPHP = \str_starts_with( $string, '<?php' );

        if ( $declaredPHP ) {
            $string = '<?php '.$string;
        }

        $res    = '';
        $tokens = \token_get_all( $string );
        $start  = null;
        $str    = '';

        for ( $i = 0; $i < \count( $tokens ); $i++ ) {
            $token = $tokens[$i];
            if ( $token[0] === T_ECHO ) {
                if ( ! $start ) {
                    $str   = '';
                    $start = \strlen( $res );
                }
            }
            elseif ( $start && $token[0] === T_CONSTANT_ENCAPSED_STRING && $token[1][0] === "'" ) {
                $str .= \stripslashes( \substr( $token[1], 1, -1 ) );
            }
            elseif ( $start && $token === ';' ) {
                if ( $str !== '' ) {
                    $res = \substr_replace(
                        $res,
                        'echo '.( $str === "\n" ? '"\n"' : \var_export( $str, true ) ),
                        $start,
                        \strlen( $res ) - $start,
                    );
                }
            }
            elseif ( $token[0] !== T_WHITESPACE ) {
                $start = null;
            }

            $res .= \is_array( $token ) ? $token[1] : $token;
        }

        return $declaredPHP ? \substr( $res, 5 ) : $res;
    }

    final protected function getUses() : array
    {
        return \array_keys( \array_filter( $this->uses ) );
    }

    private function generateHead() : string
    {
        $php = ['<?php'];

        if ( $this->strict ) {
            $php['strict'] = 'declare(strict_types=1);';
        }

        if ( $this->namespace ) {
            $php['namespace'] = 'namespace '.$this->namespace.';';
        }

        if ( $this->uses ) {
            foreach ( $this->getUses() as $use ) {
                $php['use'][] = "use {$use};";
            }

            $php['use'] = \implode( "\n", $php['use'] );
        }

        return \trim( \implode( "\n\n", $php ), " \n\r\t\v\0" );
    }

    private function generateClass() : string
    {
        $comment = $this->comment ? <<<PHP
            /**
             * {$this->comment}
             */
            PHP."\n" : null;

        $class = match ( true ) {
            $this->final    => 'final',
            $this->abstract => 'abstract',
            default         => '',
        };

        $class .= ' class ';

        $class .= $this->className;

        if ( $this->extends ) {
            $class .= ' extends ';

            foreach ( $this->extends as $extend => $enabled ) {
                $class .= "{$extend}, ";
            }

            $class = \rtrim( $class, ' ,' );
        }

        if ( $this->implements ) {
            $class .= ' implements ';

            foreach ( $this->implements as $interface => $enabled ) {
                $class .= "{$interface}, ";
            }

            $class = \rtrim( $class, ' ,' );
        }

        return \trim( $class, " \n\r\t\v\0" );
    }

    private function generateBody() : string
    {
        $php = [];

        if ( $this->traits ) {
            foreach ( $this->traits as $trait => $enabled ) {
                $php['traits'][] = "\tuse {$trait};";
            }

            $php['traits'] = \implode( "\n", $php['traits'] );
        }

        foreach ( $this->getFragments() as $fragment ) {
            $tab                                       = $fragment::TYPE === 'method' ? '' : "\t";
            $php[$fragment::TYPE.".{$fragment->name}"] = $tab.$fragment->resolve();
        }

        return \trim( \implode( "\n\n", $php ), " \n\r\t\v\0" );
    }

    public function generate( bool $regenerate = false ) : string
    {
        if ( $this->php && ! $regenerate ) {
            return 'cached value';
        }
        $this->php = <<<PHP
            {$this->generateHead()}
            
            {$this->generateClass()}
            {
                {$this->generateBody()}
            }
            PHP;

        $dateTime = Time::now();

        $timestamp          = $dateTime->unixTimestamp;
        $formattedTimestamp = $dateTime->format( 'Y-m-d H:i:s e' );
        $storageDataHash    = key_hash( 'xxh64', $this->php );

        return <<<PHP
            <?php
                   
            /*------------------------------------------------------%{$timestamp}%-
                   
               Name      : {$this->className}
               Generated : {$formattedTimestamp}
               Generator : {$this->generator}
                   
               Do not edit it manually.
                   
            -#{$storageDataHash}#------------------------------------------------*/
            PHP.\substr( $this->php, 5 );
    }

    public function uses( string ...$fqn ) : self
    {
        foreach ( $fqn as $use ) {
            $this->uses[$use] = true;
        }
        return $this;
    }

    public function final( bool $set = true ) : self
    {
        $this->final = $set;
        return $this;
    }

    public function abstract( bool $set = true ) : self
    {
        $this->abstract = $set;
        return $this;
    }

    public function extends( string $className ) : self
    {
        $this->extends[$className] = true;
        return $this;
    }

    public function implements( string $className ) : self
    {
        $this->implements[$className] = true;
        return $this;
    }

    public function traits( string $className ) : self
    {
        $this->traits[$className] = true;
        return $this;
    }

    public function comment( ?string $comment ) : self
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return PhpFragment[]
     */
    public function getFragments() : array
    {
        return [
            ...$this->constants,
            ...$this->properties,
            ...$this->methods,
        ];
    }

    public function addConstant(
        string     $name,
        mixed      $value,
        Visibility $visibility = Visibility::PUBLIC,
        ?string    $comment = null,
        ?string    $type = null,
    ) : self {
        $this->constants[$name] = new PhpConstant( $name, $value, $visibility, $comment, $type );
        return $this;
    }

    public function addProperty( string $name, mixed $value ) : self
    {
        $this->properties[$name] = $value;
        return $this;
    }

    public function addMethod(
        string            $name,
        string|Stringable $code,
        string            $arguments = '',
        string            $returns = 'void',
        Visibility        $visibility = Visibility::PUBLIC,
        bool              $final = false,
        ?string           $comment = null,
    ) : self {
        $this->methods[$name] = new PhpMethod( $name, $code, $arguments, $returns, $visibility, $final, $comment );
        return $this;
    }

    // :: :: :: :: :: ::

    final public function toString() : string
    {
        return $this->__toString();
    }

    final public function print() : void
    {
        echo $this->__toString();
    }
}
