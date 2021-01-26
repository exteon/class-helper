<?php

    namespace Exteon;

    use InvalidArgumentException;
    use ReflectionClass;
    use ReflectionException;
    use SplObjectStorage;

    class ClassMeta
    {
        /** @var bool */
        protected $isParentInit = false;

        /** @var static|null */
        protected $parent;

        /** @var bool */
        protected $isReflectionInit = false;

        /** @var bool */
        protected $isAbstract;

        /** @var bool */
        protected $isInterface;

        /** @var bool */
        protected $isTrait;

        /** @var bool */
        protected $isFinal;

        /** @var bool */
        protected $isTraitsInit = false;

        /** @var static[]|null */
        protected $traits;

        /** @var bool */
        protected $isInterfacesInit = false;

        /** @var static[]|null */
        protected $interfaces;

        /** @var array<string,null>|null */
        protected $hasInterface;

        /** @var bool */
        protected $isAllTraitsInit = false;

        /** @var static[]|null */
        protected $allTraits;

        /** @var array<string,null>|null */
        protected $hasTrait;

        /** @var string */
        protected $className;

        protected static $factories = [];

        /** @var array<string,static> */
        protected static $instances = [];

        protected function __construct(
            string $className
        ) {
            $this->className = $className;
        }

        /**
         * @return string
         */
        public function getClassName(): string
        {
            return $this->className;
        }

        public function getParent(): ?self
        {
            if ($this->isParentInit) {
                return $this->parent;
            }
            $parentName = get_parent_class($this->className);
            if ($parentName) {
                $parent = static::get($parentName);
            } else {
                $parent = null;
            }
            $this->parent = $parent;
            $this->isParentInit = true;
            return $this->parent;
        }

        /**
         * @throws ReflectionException
         */
        protected function initReflection()
        {
            if ($this->isReflectionInit) {
                return;
            }
            $reflection = new ReflectionClass($this->getClassName());
            $this->isAbstract = $reflection->isAbstract();
            $this->isInterface = $reflection->isInterface();
            $this->isTrait = $reflection->isTrait();
            $this->isFinal = $reflection->isFinal();
            $this->isReflectionInit = true;
        }

        /**
         * @return bool
         * @throws ReflectionException
         */
        public function isAbstract(): bool
        {
            static::initReflection();
            return $this->isAbstract;
        }

        /**
         * @return bool
         * @throws ReflectionException
         */
        public function isInterface(): bool
        {
            static::initReflection();
            return $this->isInterface;
        }

        /**
         * @return bool
         * @throws ReflectionException
         */
        public function isTrait(): bool
        {
            static::initReflection();
            return $this->isTrait;
        }

        /**
         * @return bool
         * @throws ReflectionException
         */
        public function isFinal(): bool
        {
            static::initReflection();
            return $this->isFinal;
        }

        /**
         * @return static[]
         * @throws ReflectionException
         */
        public function getTraits(): ?array
        {
            if ($this->isTraitsInit) {
                return $this->traits;
            }
            if ($this->isInterface()) {
                $this->traits = null;
            } else {
                $traitNames = class_uses($this->className);
                $this->traits = array_map(
                    [get_called_class(), 'get'],
                    $traitNames
                );
            }
            $this->isTraitsInit = true;
            return $this->traits;
        }

        /**
         * @throws ReflectionException
         */
        protected function initAllTraits()
        {
            if ($this->isAllTraitsInit) {
                return;
            }
            if ($this->isInterface()) {
                $this->allTraits = null;
                $this->hasTrait = null;
            } else {
                $allTraitsFlipped = new SplObjectStorage();
                foreach ($this->getTraits() as $trait) {
                    $allTraitsFlipped[$trait] = null;
                    foreach ($trait->getAllTraits() as $subtrait) {
                        $allTraitsFlipped[$subtrait] = null;
                    }
                }
                $parent = $this->getParent();
                if ($parent) {
                    foreach ($parent->getAllTraits() as $trait) {
                        $allTraitsFlipped[$trait] = null;
                    }
                }
                $allTraits = [];
                $hasTrait = [];
                /** @var static $trait */
                foreach ($allTraitsFlipped as $trait) {
                    $allTraits[] = $trait;
                    $hasTrait[$trait->getClassName()] = null;
                }
                $this->allTraits = $allTraits;
                $this->hasTrait = $hasTrait;
            }
            $this->isAllTraitsInit = true;
        }

        /**
         * @return static[] | null
         * @throws ReflectionException
         */
        public function getAllTraits(): ?array
        {
            $this->initAllTraits();
            return $this->allTraits;
        }

        /**
         * @param static|string $trait
         * @return bool
         * @throws ReflectionException
         */
        public function hasTrait($trait): bool
        {
            $invalidArgument = false;
            $traitName = null;
            if (is_a($trait, self::class)) {
                if (!$trait->isTrait()) {
                    $invalidArgument = true;
                } else {
                    $traitName = $trait->getClassName();
                }
            } elseif (is_string($trait)) {
                $traitName = $trait;
            } else {
                $invalidArgument = true;
            }
            if ($invalidArgument) {
                throw new InvalidArgumentException(
                    'Trait-type ClassMeta object or string expected'
                );
            }
            $this->initAllTraits();
            return array_key_exists($traitName, $this->hasTrait);
        }

        /**
         * @throws ReflectionException
         */
        protected function initInterfaces()
        {
            if ($this->isInterfacesInit) {
                return;
            }
            if ($this->isTrait()) {
                $this->interfaces = null;
                $this->hasInterface = null;
            } else {
                $interfaces = [];
                $hasInterface = [];
                $interfaceNames = class_implements($this->className);
                foreach ($interfaceNames as $interfaceName) {
                    $interfaces[] = static::get($interfaceName);
                    $hasInterface[$interfaceName] = null;
                }
                $this->interfaces = $interfaces;
                $this->hasInterface = $hasInterface;
            }
            $this->isInterfacesInit = true;
        }

        /**
         * @return null
         * @throws ReflectionException
         */
        public function getInterfaces(): ?array
        {
            $this->initInterfaces();
            return $this->interfaces;
        }

        /**
         * @param $interface
         * @return bool
         * @throws ReflectionException
         */
        public function hasInterface($interface): bool
        {
            $invalidArgument = false;
            $interfaceName = null;
            if (is_a($interface, self::class)) {
                if ($interface->isTrait()) {
                    $invalidArgument = true;
                } else {
                    $interfaceName = $interface->getClassName();
                }
            } elseif (is_string($interface)) {
                $interfaceName = $interface;
            } else {
                $invalidArgument = true;
            }
            if ($invalidArgument) {
                throw new InvalidArgumentException(
                    'Interface or class-type ClassMeta object or string expected'
                );
            }
            $this->initInterfaces();
            return array_key_exists($interfaceName, $this->hasInterface);
        }

        /**
         * @param string $className
         * @return static
         */
        public static function get(string $className): self
        {
            if (isset(self::$instances[$className])) {
                return self::$instances[$className];
            }
            $what = static::class;
            $instance = new $what($className);
            self::$instances[$className] = $instance;
            return $instance;
        }
    }