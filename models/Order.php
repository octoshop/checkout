<?php namespace Octoshop\Checkout\Models;

use Model;
use Session;
use Carbon\Carbon;
use Octoshop\Checkout\Models\OrderStatus;
use RainLab\User\Models\User;

class Order extends Model
{
    use \Octoshop\Core\Traits\Uuidable;

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

    public static function getFromSession()
    {
        if (!Session::has('order')) {
            return;
        }

        return static::findByUuid(Session::get('order'));
    }

    public static function createForUser(User $user)
    {
        $order = new static();
        $order->user_id = $user->id;
        $order->status_id = OrderStatus::whereName('Draft')->first()->id;

        if ($defaultAddress = $user->addresses()->first()) {
            $order->setBillingAddress($defaultAddress);
            $order->setShippingAddress($defaultAddress);
        }

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
        if (is_array($data)) {
            $data = (object) $data;
        }

        $prefix = $address.'_';
        $fields = ['company', 'line1', 'line2', 'town', 'region', 'postcode', 'country'];

        $this->{$prefix.'name'} = sprintf(
            "%s %s",
            $data->{$prefix.'first_name'},
            $data->{$prefix.'last_name'}
        );

        foreach ($fields as $field) {
            $this->{$prefix.$field} = $data->{$prefix.$field};
        }
    }

    public function syncWithCartItem($basketItem)
    {
        $searchParam = ['basket_row_id' => $basketItem->rowId];
        $item = $this->items()->firstOrNew($searchParam);

        $item->fill([
            'name' => $basketItem->name,
            'quantity' => $basketItem->qty,
            'price' => $basketItem->price,
            'subtotal' => $basketItem->price * $basketItem->qty,
        ]);

        $item->save();
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
