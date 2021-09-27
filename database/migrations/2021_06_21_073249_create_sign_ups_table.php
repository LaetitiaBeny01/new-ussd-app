<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSignUpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sign_ups', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender',10)->nullable();
            $table->string('MSISDN',20)->nullable();
            $table->string('phone_number',20)->nullable();
            $table->string('class',10)->nullable();
            $table->tinyInteger('age')->nullable();
            $table->string('district')->nullable();
            $table->string('district_actual')->nullable();
            $table->string('status',10)->default('pending');
            $table->string('ussd_current_level',5)->nullable();
            $table->text('ussd_string')->nullable();
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
        Schema::dropIfExists('sign_ups');
    }
}
