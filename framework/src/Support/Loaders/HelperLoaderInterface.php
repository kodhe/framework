<?php

declare(strict_types=0);

namespace Kodhe\Framework\Support\Loaders;

/**
 * Interface untuk Helper Loader Strategy
 * 
 * Memungkinkan multiple strategy untuk loading helper functions
 * dengan cara yang terstruktur dan extensible.
 */
interface HelperLoaderInterface
{
    /**
     * Cek apakah loader ini bisa handle helper tertentu
     * 
     * @param string $helper Nama helper (tanpa _helper suffix)
     * @return bool True jika loader ini bisa load helper tersebut
     */
    public function canLoad(string $helper): bool;
    
    /**
     * Load helper functions
     * 
     * @param string $helper Nama helper (tanpa _helper suffix)
     * @return void
     * @throws \Exception Jika helper tidak dapat di-load
     */
    public function load(string $helper): void;
}
