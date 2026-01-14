// Dalam Route.php, tambahkan method untuk module group:

class Route
{
    // ... existing code ...
    
    /**
     * Create module route group
     */
    public static function module(string $module, callable $callback, array $attributes = []): void
    {
        $defaultAttributes = [
            'prefix' => $module,
            'namespace' => 'App\\Modules\\' . ucfirst($module) . '\\Controllers\\',
            'as' => $module . '.',
            'module' => $module
        ];
        
        $groupAttributes = array_merge($defaultAttributes, $attributes);
        
        self::group($groupAttributes, $callback);
    }
    
    /**
     * Get current module dari group
     */
    public static function getCurrentModule(): ?string
    {
        $groupHandler = self::getGroupHandler();
        $attributes = $groupHandler->getCurrentAttributes();
        
        return $attributes['module'] ?? null;
    }
    
    // ... existing code ...
}