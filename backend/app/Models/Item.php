<?php

namespace App\Models;

use App\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catalog entry — an item *type* (e.g. "Multimeter"). Inventory rows
 * (where it lives, how many, whose hands it's in) live on
 * {@see ItemStock}. One Item has many ItemStocks.
 */
class Item extends Model
{
    use LogsActivity;

    protected $fillable = ['name'];

    public function stocks(): HasMany
    {
        return $this->hasMany(ItemStock::class);
    }

    public function totalQuantity(): int
    {
        return (int) $this->stocks()->sum('quantity');
    }

    public function labelForLog(): string
    {
        return 'Item: ' . $this->name;
    }
}
