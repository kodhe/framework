<?php

declare(strict_types=1);

namespace Kodhe\Framework\Cart\Storage;

use Kodhe\Framework\Cart\Contracts\CartStorageInterface;

/**
 * Class DatabaseStorage
 * 
 * Stores cart data in database.
 * Useful for persistent carts across sessions/devices.
 * 
 * @package Kodhe\Cart\Storage
 */
class DatabaseStorage implements CartStorageInterface
{
    /**
     * @var object CodeIgniter instance
     */
    protected $CI;

    /**
     * @var string Database table name
     */
    private string $table = 'cart';

    /**
     * @var string|null User/customer identifier
     */
    private ?string $userId = null;

    /**
     * @var string Session ID for anonymous users
     */
    private string $sessionId;

    /**
     * Cached cart data
     */
    protected ?array $cache = null;

    /**
     * Constructor
     *
     * @param object|null $ci CodeIgniter instance
     * @param string|null $userId User/customer ID (null for guests)
     * @param string|null $tableName Custom table name
     */
    public function __construct($ci = null, ?string $userId = null, ?string $tableName = null)
    {
        $this->CI = $ci ?? kodhe();
        $this->userId = $userId;
        $this->sessionId = session_id() ?: uniqid('guest_', true);
        
        if ($tableName !== null) {
            $this->table = $tableName;
        }

        // Load database if not already loaded
        if (!isset($this->CI->db)) {
            $this->CI->load->database();
        }

        // Load initial data into cache
        $this->load();
    }

    /**
     * Load cart data from database
     *
     * @return array|null
     */
    public function load(): ?array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $query = $this->CI->db
            ->where('session_id', $this->sessionId);
        
        if ($this->userId !== null) {
            $query->where('user_id', $this->userId);
        } else {
            $query->where('user_id IS NULL', null, false);
        }

        $result = $query->get($this->table)->row();

        if ($result && isset($result->cart_data)) {
            $this->cache = json_decode($result->cart_data, true);
            return $this->cache;
        }

        $this->cache = null;
        return null;
    }

    /**
     * Save cart data to database
     *
     * @param array $data
     * @return bool
     */
    public function save(array $data): bool
    {
        $this->cache = $data;

        // If cart is empty, delete from database
        if (count($data) <= 2) {
            return $this->delete();
        }

        $cartData = [
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'cart_data' => json_encode($data),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Check if record exists
        $exists = $this->CI->db
            ->where('session_id', $this->sessionId)
            ->where('user_id', $this->userId)
            ->get($this->table)
            ->num_rows() > 0;

        if ($exists) {
            $result = $this->CI->db
                ->where('session_id', $this->sessionId)
                ->update($this->table, $cartData);
        } else {
            $cartData['created_at'] = $cartData['updated_at'];
            $result = $this->CI->db->insert($this->table, $cartData);
        }

        return $result !== false;
    }

    /**
     * Delete cart data from database
     *
     * @return bool
     */
    public function delete(): bool
    {
        $this->cache = null;

        $this->CI->db
            ->where('session_id', $this->sessionId)
            ->where('user_id', $this->userId)
            ->delete($this->table);

        return true;
    }

    /**
     * Check if cart data exists in database
     *
     * @return bool
     */
    public function exists(): bool
    {
        if ($this->cache !== null) {
            return count($this->cache) > 2;
        }

        $count = $this->CI->db
            ->where('session_id', $this->sessionId)
            ->where('user_id', $this->userId)
            ->get($this->table)
            ->num_rows();

        return $count > 0;
    }

    /**
     * Set user ID (for when guest becomes logged in)
     *
     * @param string $userId
     * @return self
     */
    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        $this->cache = null; // Clear cache to reload with new user ID
        return $this;
    }

    /**
     * Get user ID
     *
     * @return string|null
     */
    public function getUserId(): ?string
    {
        return $this->userId;
    }

    /**
     * Merge guest cart with user's existing cart on login
     *
     * @param string $userId
     * @return bool
     */
    public function mergeToUser(string $userId): bool
    {
        $guestCart = $this->load();
        
        if (empty($guestCart) || count($guestCart) <= 2) {
            $this->setUserId($userId);
            return true;
        }

        // Load user's existing cart
        $this->setUserId($userId);
        $userCart = $this->load();

        if (empty($userCart) || count($userCart) <= 2) {
            // No existing user cart, just transfer guest cart
            $this->save($guestCart);
            return true;
        }

        // Merge carts (guest items override or add to user cart)
        foreach ($guestCart as $rowId => $item) {
            if (!in_array($rowId, ['total_items', 'cart_total'], true) && is_array($item)) {
                if (isset($userCart[$rowId])) {
                    // Item exists, merge quantities
                    $userCart[$rowId]['qty'] += $item['qty'];
                } else {
                    // New item, add it
                    $userCart[$rowId] = $item;
                }
            }
        }

        // Recalculate totals
        $userCart['total_items'] = 0;
        $userCart['cart_total'] = 0;

        foreach ($userCart as $rowId => $item) {
            if (is_array($item) && isset($item['price'], $item['qty'])) {
                $userCart['total_items'] += $item['qty'];
                $userCart['cart_total'] += ($item['price'] * $item['qty']);
                $userCart[$rowId]['subtotal'] = ($item['price'] * $item['qty']);
            }
        }

        return $this->save($userCart);
    }

    /**
     * Clear the internal cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->cache = null;
    }
}
