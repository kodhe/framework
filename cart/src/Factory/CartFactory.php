<?php

declare(strict_types=1);

namespace Kodhe\Cart\Factory;

use Kodhe\Cart\Contracts\CartStorageInterface;
use Kodhe\Cart\Storage\SessionStorage;
use Kodhe\Cart\Storage\DatabaseStorage;
use Kodhe\Cart\Storage\MemoryStorage;
use Kodhe\Cart\Calculator\TaxCalculator;
use Kodhe\Cart\Calculator\DiscountCalculator;
use Kodhe\Cart\Calculator\ShippingCalculator;

/**
 * Class CartFactory
 * 
 * Factory pattern for creating cart instances with different configurations.
 * 
 * @package Kodhe\Cart\Factory
 */
class CartFactory
{
    /**
     * Create a cart with session storage (default, backward compatible)
     *
     * @param array $config Configuration options
     * @return \Kodhe\Cart\Cart
     */
    public static function create(array $config = []): \Kodhe\Cart\Cart
    {
        $storage = self::createStorage('session', $config);
        return self::createWithStorage($storage, $config);
    }

    /**
     * Create a cart with specific storage type
     *
     * @param string $type Storage type: 'session', 'database', 'memory'
     * @param array $config Configuration options
     * @return \Kodhe\Cart\Cart
     */
    public static function createWithStorageType(string $type, array $config = []): \Kodhe\Cart\Cart
    {
        $storage = self::createStorage($type, $config);
        return self::createWithStorage($storage, $config);
    }

    /**
     * Create a cart with custom storage implementation
     *
     * @param CartStorageInterface $storage
     * @param array $config Configuration options
     * @return \Kodhe\Cart\Cart
     */
    public static function createWithStorage(CartStorageInterface $storage, array $config = []): \Kodhe\Cart\Cart
    {
        $cart = new \Kodhe\Cart\Cart($config);
        $cart->setStorage($storage);
        
        // Configure calculators if provided
        if (isset($config['tax_rate'])) {
            $cart->getTaxCalculator()->setTaxRate($config['tax_rate']);
        }

        if (isset($config['tax_included'])) {
            $cart->getTaxCalculator()->setTaxIncluded($config['tax_included']);
        }

        if (isset($config['shipping_method'])) {
            $cart->getShippingCalculator()->setMethod($config['shipping_method']);
        }

        if (isset($config['shipping_rate'])) {
            $cart->getShippingCalculator()->setFlatRate($config['shipping_rate']);
        }

        return $cart;
    }

    /**
     * Create storage instance based on type
     *
     * @param string $type Storage type
     * @param array $config Configuration options
     * @return CartStorageInterface
     */
    public static function createStorage(string $type, array $config = []): CartStorageInterface
    {
        $ci = $config['ci'] ?? null;

        switch ($type) {
            case 'database':
                $userId = $config['user_id'] ?? null;
                $table = $config['table'] ?? null;
                return new DatabaseStorage($ci, $userId, $table);

            case 'memory':
                $key = $config['key'] ?? 'default';
                return new MemoryStorage($key);

            case 'session':
            default:
                return new SessionStorage($ci);
        }
    }

    /**
     * Create a cart configured for testing
     *
     * @param string $key Unique cart key for test isolation
     * @return \Kodhe\Cart\Cart
     */
    public static function createForTesting(string $key = 'test_cart'): \Kodhe\Cart\Cart
    {
        return self::createWithStorageType('memory', ['key' => $key]);
    }

    /**
     * Create a cart with database persistence for logged-in users
     *
     * @param string $userId User ID
     * @param array $config Additional configuration
     * @return \Kodhe\Cart\Cart
     */
    public static function createForUser(string $userId, array $config = []): \Kodhe\Cart\Cart
    {
        $config['user_id'] = $userId;
        return self::createWithStorageType('database', $config);
    }

    /**
     * Create a cart that merges guest cart on user login
     *
     * @param string $userId User ID
     * @param bool $merge Whether to merge existing guest cart
     * @param array $config Additional configuration
     * @return \Kodhe\Cart\Cart
     */
    public static function createWithMergeOnLogin(
        string $userId,
        bool $merge = true,
        array $config = []
    ): \Kodhe\Cart\Cart {
        $cart = self::createForUser($userId, $config);

        if ($merge) {
            // Load guest cart from session
            $guestStorage = new SessionStorage($config['ci'] ?? null);
            $guestCart = $guestStorage->load();

            if (!empty($guestCart) && count($guestCart) > 2) {
                // Get database storage and merge
                $dbStorage = $cart->getStorage();
                if ($dbStorage instanceof DatabaseStorage) {
                    $dbStorage->mergeToUser($userId);
                }
            }
        }

        return $cart;
    }
}
