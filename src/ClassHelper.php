<?php

    namespace Exteon;

    abstract class ClassHelper
    {
        /**
         * @param string $classOrTraitOrInterface
         * @return bool
         */
        public static function preloadClass(
            string $classOrTraitOrInterface
        ): bool {
            return (
                class_exists($classOrTraitOrInterface) ||
                trait_exists($classOrTraitOrInterface, false) ||
                interface_exists($classOrTraitOrInterface, false)
            );
        }
    }