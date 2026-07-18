<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('business_type');
            $table->string('email');
            $table->string('country');
            $table->string('phone');
            $table->string('company');
            $table->string('company_website');
            $table->text('description')->nullable();
            $table->text('interest_in');
            $table->enum('status', ['lead_mql', 'sql', 'prospect', 'customer'])
                ->default('lead_mql')
                ->index();
            $table->string('reference')->nullable();
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
        Schema::dropIfExists('enquiries');
    }
};
