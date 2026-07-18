<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('gms_stone_enquiries', function (Blueprint $table) {
            $table->charset = 'utf8mb3';
            $table->collation = 'utf8mb3_general_ci';

            $table->increments('id');
            $table->string('full_name');
            $table->string('email');
            $table->string('phone_number', 50);
            $table->string('country_code', 10);
            $table->enum('account_type', ['personal', 'business']);
            $table->string('business_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('tax_id', 100)->nullable();
            $table->string('mailing_name')->nullable();
            $table->string('website')->nullable();
            $table->string('office_type', 100)->nullable();
            $table->string('branch_code', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postcode', 20)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->boolean('is_seen')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gms_stone_enquiries');
    }
};
