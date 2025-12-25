<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BuatProdukTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('produk', function (Blueprint $table) {
        //     $table->increments('id_produk');
        //     $table->unsignedInteger('id_kategori');
        //     $table->string('nama_produk')->unique();
        //     $table->string('merk')->nullable();
        //     $table->integer('harga_beli');
        //     // $table->tinyInteger(column: 'diskon')->default(0);
        //     $table->integer('harga_jual');
        //     $table->integer('stok');
        //     $table->timestamps();
        // });


        Schema::create('produk', function (Blueprint $table) {

            // Auto-incrementing primary key for the products table
            $table->increments('id_produk');
        
            // Foreign key reference to the category table (category ID)
            $table->unsignedInteger('id_kategori');
        
            // Product name (must be unique, no two products can have the same name)
            $table->string('nama_produk')->unique();
        
            // Product brand (optional / can be null)
            $table->string('merk')->nullable();
        
            // Purchase price (cost price of the product)
            $table->integer('harga_beli')->nullable();
        
            // Discount percentage (currently commented out, default would be 0)
            $table->tinyInteger('diskon')->default(0)->nullable();
        
            // Selling price of the product
            $table->integer('harga_jual')->nullable();
        
            // Stock quantity available
            $table->integer('stok');
        
            // Automatically adds created_at and updated_at timestamps
            $table->timestamps();
        });
        

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produk');
    }
}
