<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class ProductOutOfStockException extends Exception
{
    public function __construct()
    {
        parent::__construct('Product out of stock.');
    }
}
