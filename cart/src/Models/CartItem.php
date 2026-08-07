<?php

declare(strict_types=1);

namespace Kodhe\Cart\Models;

/**
 * Class CartItem
 * 
 * Value object representing a single item in the shopping cart.
 * 
 * @package Kodhe\Cart\Models
 */
class CartItem
{
    /**
     * @var string Unique row ID
     */
    private string $rowid;

    /**
     * @var string Product ID
     */
    private string $id;

    /**
     * @var float Quantity
     */
    private float $qty;

    /**
     * @var float Unit price
     */
    private float $price;

    /**
     * @var string Product name
     */
    private string $name;

    /**
     * @var array Product options (size, color, etc.)
     */
    private array $options;

    /**
     * @var float Item subtotal
     */
    private float $subtotal;

    /**
     * @var array Additional data
     */
    private array $data;

    /**
     * Constructor
     *
     * @param string $id Product ID
     * @param float $qty Quantity
     * @param float $price Unit price
     * @param string $name Product name
     * @param array $options Product options
     * @param string|null $rowid Row ID (auto-generated if null)
     */
    public function __construct(
        string $id,
        float $qty,
        float $price,
        string $name,
        array $options = [],
        ?string $rowid = null
    ) {
        $this->id = $id;
        $this->qty = $qty;
        $this->price = $price;
        $this->name = $name;
        $this->options = $options;
        $this->data = [];
        
        // Generate row ID based on product ID and options
        if ($rowid === null) {
            if (!empty($options)) {
                $this->rowid = md5($id . serialize($options));
            } else {
                $this->rowid = md5($id);
            }
        } else {
            $this->rowid = $rowid;
        }

        // Calculate subtotal
        $this->subtotal = $price * $qty;
    }

    /**
     * Get row ID
     *
     * @return string
     */
    public function getRowId(): string
    {
        return $this->rowid;
    }

    /**
     * Get product ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get quantity
     *
     * @return float
     */
    public function getQty(): float
    {
        return $this->qty;
    }

    /**
     * Set quantity
     *
     * @param float $qty
     * @return void
     */
    public function setQty(float $qty): void
    {
        $this->qty = $qty;
        $this->recalculate();
    }

    /**
     * Get unit price
     *
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * Set unit price
     *
     * @param float $price
     * @return void
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
        $this->recalculate();
    }

    /**
     * Get product name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get product options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set product options
     *
     * @param array $options
     * @return void
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
        // Regenerate row ID when options change
        $this->rowid = md5($this->id . serialize($options));
    }

    /**
     * Get item subtotal
     *
     * @return float
     */
    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    /**
     * Get additional data
     *
     * @param string|null $key Specific key or null for all data
     * @return mixed
     */
    public function getData(?string $key = null)
    {
        if ($key === null) {
            return $this->data;
        }
        
        return $this->data[$key] ?? null;
    }

    /**
     * Set additional data
     *
     * @param string|array $key Key or associative array
     * @param mixed $value Value if $key is string
     * @return void
     */
    public function setData($key, $value = null): void
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
    }

    /**
     * Recalculate subtotal
     *
     * @return void
     */
    private function recalculate(): void
    {
        $this->subtotal = $this->price * $this->qty;
    }

    /**
     * Convert to array (backward compatible format)
     *
     * @return array
     */
    public function toArray(): array
    {
        $array = [
            'rowid' => $this->rowid,
            'id' => $this->id,
            'qty' => $this->qty,
            'price' => $this->price,
            'name' => $this->name,
            'subtotal' => $this->subtotal,
        ];

        if (!empty($this->options)) {
            $array['options'] = $this->options;
        }

        if (!empty($this->data)) {
            $array = array_merge($array, $this->data);
        }

        return $array;
    }

    /**
     * Create from array (backward compatible)
     *
     * @param array $data Item data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['id'],
            (float) ($data['qty'] ?? 1),
            (float) ($data['price'] ?? 0),
            $data['name'] ?? '',
            $data['options'] ?? [],
            $data['rowid'] ?? null
        );
    }
}
