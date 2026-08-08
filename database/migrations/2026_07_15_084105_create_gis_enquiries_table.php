<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('gis_enquiries')) {
            return;
        }

        Schema::create('gis_enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_number');
            $table->string('inquiry')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 32)->default('lead_mql')->index();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gis_enquiries');
    }
};
