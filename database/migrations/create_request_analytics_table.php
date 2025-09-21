<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tableName = config('request-analytics.database.table', 'request_analytics');
        $connection = config('request-analytics.database.connection');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            Schema::connection($connection)->create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('path');
                $table->string('page_title')->nullable();
                $table->string('ip_address');
                $table->string('operating_system')->nullable();
                $table->string('browser')->nullable();
                $table->string('device')->nullable();
                $table->string('screen')->nullable();
                $table->string('referrer')->nullable();
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->string('language')->nullable();
                $table->text('query_params')->nullable();
                $table->string('session_id');
                $table->string('visitor_id')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('http_method');
                $table->string('request_category');
                $table->bigInteger('response_time')->nullable();
                $table->timestamp('visited_at');
            });
        }
    }

    public function down()
    {
        $tableName = config('request-analytics.database.table', 'request_analytics');
        $connection = config('request-analytics.database.connection');

        Schema::connection($connection)->dropIfExists($tableName);
    }
};
