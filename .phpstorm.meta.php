<?php

namespace Latte {

    /**
     * Template loader.
     */
    interface Loader
    {
        /**
         * Returns template source code.
         *
         * @param string $name
         */
        public function getContent( string $name ) : string;

        /**
         * Returns referred template name.
         *
         * @param string $name
         * @param string $referringName
         */
        public function getReferredName( string $name, string $referringName ) : string;

        /**
         * Returns unique identifier for caching.
         *
         * @param string $name
         */
        public function getUniqueId( string $name ) : string;
    }
}
