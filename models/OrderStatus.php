<?php namespace Octoshop\Checkout\Models;

use Carbon\Carbon;
use Model;

class OrderStatus extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'octoshop_order_statuses';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'colour'];

    /**
     * @var array Attributes to mutate as dates
     */
    public $timestamps = [];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'orders' => 'Octoshop\Checkout\Models\Order',
    ];
}
