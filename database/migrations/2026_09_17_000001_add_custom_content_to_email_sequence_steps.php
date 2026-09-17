<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('email_sequence_steps', function (Blueprint $table) {
            $table->string('content_mode', 16)->default('template')->after('step_number');
            $table->string('subject')->nullable()->after('email_template_id');
            $table->string('preview_text')->nullable()->after('subject');
            $table->longText('html_content')->nullable()->after('preview_text');
            $table->longText('plain_text_content')->nullable()->after('html_content');
            $table->json('variables')->nullable()->after('plain_text_content');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE email_sequence_steps MODIFY email_template_id BIGINT UNSIGNED NULL');
        }
    }

    public function down()
    {
        Schema::table('email_sequence_steps', function (Blueprint $table) {
            $table->dropColumn([
                'content_mode', 'subject', 'preview_text', 'html_content', 'plain_text_content', 'variables',
            ]);
        });
    }
};
