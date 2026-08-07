<?php

namespace Kodhe\Framework\View\View;

use Kodhe\Framework\View\Contracts\ViewLoaderInterface;
use Kodhe\Framework\View\Path\ViewPathResolver;
use Kodhe\Framework\View\Theme\ThemeManager;
use Kodhe\Framework\View\Variant\VariantManager;
use Kodhe\Framework\View\Support\DataResolver;

/**
 * Class ViewLoader
 *
 * @package Kodhe\Framework\View\View
 */
class ViewLoader implements ViewLoaderInterface
{
    /**
     * @var ViewFactory
     */
    protected $factory;

    /**
     * @var ViewPathResolver
     */
    protected $pathResolver;

    /**
     * @var ThemeManager
     */
    protected $themeManager;

    /**
     * @var VariantManager
     */
    protected $variantManager;

    /**
     * Create a new ViewLoader instance
     *
     * @param ViewFactory|null $factory
     * @param ViewPathResolver|null $pathResolver
     * @param ThemeManager|null $themeManager
     * @param VariantManager|null $variantManager
     */
    public function __construct(
        ?ViewFactory $factory = null,
        ?ViewPathResolver $pathResolver = null,
        ?ThemeManager $themeManager = null,
        ?VariantManager $variantManager = null
    ) {
        $this->factory = $factory ?? new ViewFactory();
        $this->pathResolver = $pathResolver ?? new ViewPathResolver();
        $this->themeManager = $themeManager ?? new ThemeManager();
        $this->variantManager = $variantManager ?? new VariantManager();
    }

    /**
     * Load a view
     *
     * @param string $view
     * @param array $data
     * @param bool $return
     * @return string|void
     */
    public function view(string $view, array $data = [], bool $return = true)
    {
        // Resolve variant-specific view
        $variantView = $this->resolveVariantView($view);
        
        // Create view with merged data
        $viewData = DataResolver::merge($data, DataResolver::getShared());
        $viewInstance = $this->factory->make($variantView, $viewData);

        return $viewInstance->render($return);
    }

    /**
     * Check if view exists
     *
     * @param string $view
     * @return bool
     */
    public function exists(string $view): bool
    {
        // Check variant-specific view first
        $variantView = $this->resolveVariantView($view);
        
        if ($this->pathResolver->exists($variantView)) {
            return true;
        }

        // Fall back to original view
        return $this->pathResolver->exists($view);
    }

    /**
     * Resolve variant-specific view
     *
     * @param string $view
     * @return string
     */
    protected function resolveVariantView(string $view): string
    {
        $variant = $this->variantManager->getVariant();
        
        // Try variant-specific view: views/mobile/home.php
        $variantView = dirname($view) . '/' . $variant . '/' . basename($view);
        
        if ($this->pathResolver->exists($variantView)) {
            return $variantView;
        }

        return $view;
    }

    /**
     * Get the view factory
     *
     * @return ViewFactory
     */
    public function getFactory(): ViewFactory
    {
        return $this->factory;
    }

    /**
     * Set the view factory
     *
     * @param ViewFactory $factory
     * @return self
     */
    public function setFactory(ViewFactory $factory): self
    {
        $this->factory = $factory;
        return $this;
    }

    /**
     * Get the path resolver
     *
     * @return ViewPathResolver
     */
    public function getPathResolver(): ViewPathResolver
    {
        return $this->pathResolver;
    }

    /**
     * Set the path resolver
     *
     * @param ViewPathResolver $resolver
     * @return self
     */
    public function setPathResolver(ViewPathResolver $resolver): self
    {
        $this->pathResolver = $resolver;
        return $this;
    }

    /**
     * Get the theme manager
     *
     * @return ThemeManager
     */
    public function getThemeManager(): ThemeManager
    {
        return $this->themeManager;
    }

    /**
     * Set the theme manager
     *
     * @param ThemeManager $manager
     * @return self
     */
    public function setThemeManager(ThemeManager $manager): self
    {
        $this->themeManager = $manager;
        return $this;
    }

    /**
     * Get the variant manager
     *
     * @return VariantManager
     */
    public function getVariantManager(): VariantManager
    {
        return $this->variantManager;
    }

    /**
     * Set the variant manager
     *
     * @param VariantManager $manager
     * @return self
     */
    public function setVariantManager(VariantManager $manager): self
    {
        $this->variantManager = $manager;
        return $this;
    }
}
