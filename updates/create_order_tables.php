<?php namespace Octoshop\Checkout\Updates;

use Schema;
use Octoshop\Core\Updates\Migration;
use October\Rain\Database\Schema\Blueprint;

class CreateOrderTables extends Migration
{
    public function up()
    {
        Schema::create('octoshop_orders', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->binary('uuid', 16)->nullable();
            $table->integer('user_id')->unsigned()->nullable();
            $table->integer('status_id')->unsigned()->default(1);
            $table->decimal('total', 20, 5)->default(0);
            $this->addAddressCols($table, 'billing');
            $this->addAddressCols($table, 'shipping');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('octoshop_order_items', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('order_id')->unsigned();
            $table->string('basket_row_id')->nullable();
            $table->string('name');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 20, 5)->default(0);
            $table->decimal('subtotal', 20, 5)->default(0);
            $table->timestamps();
        });

        Schema::create('octoshop_order_statuses', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('colour');
        });
    }

    protected function addAddressCols(&$table, $type)
    {
        $table->string($type.'_first_name')->nullable();
        $table->string($type.'_last_name')->nullable();
        $table->string($type.'_company')->nullable();
        $table->string($type.'_line1')->nullable();
        $table->string($type.'_line2')->nullable();
        $table->string($type.'_town')->nullable();
        $table->string($type.'_region')->nullable();
        $table->string($type.'_postcode')->nullable();
        $table->string($type.'_country')->nullable();
    }

    public function down()
    {
        Schema::dropIfExists('octoshop_orders');
        Schema::dropIfExists('octoshop_order_items');
        Schema::dropIfExists('octoshop_order_statuses');
    }
}
