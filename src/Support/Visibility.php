<?php

namespace Core\View\Template\Support;

enum Visibility
{
    case PRIVATE;
    case PROTECTED;
    case PUBLIC;

    public function label() : string
    {
        return \strtolower( $this->name );
    }
}
