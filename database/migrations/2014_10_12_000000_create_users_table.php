<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('tel')->default("");;
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('specialiste');
            $table->float('salaire', 9, 2)->nullable();
            $table->integer('excellence')->nullable();
            $table->rememberToken();
            $table->boolean('active')->default(false);
            $table->string('activation_token');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
