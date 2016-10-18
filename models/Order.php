<?php namespace Octoshop\Checkout\Models;

use Model;
use Session;
use Carbon\Carbon;
use Octoshop\Checkout\Models\OrderStatus;
use RainLab\User\Models\User;

class Order extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'octoshop_orders';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'items' => 'Octoshop\Checkout\Models\OrderItem',
    ];
    public $belongsTo = [
        'user' => 'RainLab\User\Models\User',
        'status' => 'Octoshop\Checkout\Models\OrderStatus',
    ];

    public function beforeCreate()
    {
        $this->hash = str_random(36);
    }

    public static function getFromSession()
    {
        if (!Session::has('orderHash')) {
            return;
        }

        return static::findByHash(Session::get('orderHash'));
    }

    public static function findByHash($hash)
    {
        if (!$hash) {
            return;
        }

        return (new static())->whereHash($hash)->first();
    }

    public static function createForUser(User $user)
    {
        $defaultAddress = $user->addresses()->first();

        $order = new static();
        $order->user_id = $user->id;
        $order->setBillingAddress($defaultAddress);
        $order->setShippingAddress($defaultAddress);
        $order->status_id = OrderStatus::whereName('Draft')->first()->id;
        $order->save();

        return $order;
    }

    public function setBillingAddress($data)
    {
        $this->setAddress('billing', $data);
    }

    public function setShippingAddress($data)
    {
        $this->setAddress('shipping', $data);
    }

    public function setAddress($address, $data)
    {
        $prefix = $address.'_';
        $fields = ['company', 'line1', 'line2', 'town', 'region', 'postcode', 'country'];

        $this->{$prefix.'name'} = $data->first_name.' '.$data->last_name;

        foreach ($fields as $field) {
            $this->{$prefix.$field} = $data->$field;
        }
    }

    public function scopeCreatedThisMonth($query)
    {
        return $query->where('created_at', '>=', Carbon::now()->startOfMonth());
    }

    public function scopeCreatedLastMonth($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->subMonth()->startOfMonth(),
            Carbon::now()->subMonth()->endOfMonth()
        ]);
    }
}
