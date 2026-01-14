// Di Kernel.php, update bagian ini:

protected function initializeRoutingComponents(): void
{
    log_message('debug', 'Initializing routing components');
    
    // Create UnifiedRouter instance
    $unifiedRouter = new UnifiedRouter();
    
    // Register UnifiedRouter sebagai singleton
    $this->container->registerSingleton('UnifiedRouter', function ($di) {
        return new UnifiedRouter();
    });

    // Register RoutingManager dengan unified router
    $this->container->registerSingleton('RoutingManager', function ($di) {
        $router = $di->make('UnifiedRouter');
        $manager = new RoutingManager($router);
        
        // Configure untuk mendukung hybrid routing
        $config = [
            'enable_modern_routing' => true,
            'enable_legacy_routing' => true,
            'prefer_modern' => true,
            'cache_routes' => ENVIRONMENT === 'production',
        ];
        
        $manager->setConfig($config);
        
        // Clear cache di development
        if (ENVIRONMENT !== 'production') {
            $manager->clearCache();
        }
        
        return $manager;
    });

    // Register ControllerExecutor
    $this->container->registerSingleton('ControllerExecutor', function ($di) {
        $facade = $this->facade;
        $routingManager = $di->make('RoutingManager');
        
        $executor = new ControllerExecutor($facade, $routingManager);
        
        // Set container reference
        $executorReflection = new \ReflectionClass($executor);
        if ($executorReflection->hasProperty('container')) {
            $containerProp = $executorReflection->getProperty('container');
            $containerProp->setAccessible(true);
            $containerProp->setValue($executor, $di);
        }
        
        return $executor;
    });

    // Get instances from container
    $this->routingManager = $this->container->make('RoutingManager');
    $this->controllerExecutor = $this->container->make('ControllerExecutor');

    // Register these instances untuk easy access
    $this->container->register('routing.manager', $this->routingManager);
    $this->container->register('controller.executor', $this->controllerExecutor);
    $this->container->register('unified.router', $unifiedRouter);
    
    log_message('debug', 'Routing components initialized');
}