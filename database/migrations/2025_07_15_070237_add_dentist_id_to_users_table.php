<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dentist_id')->nullable()->unique()->after('id');
            $table->string('auth_provider')->nullable()->after('dentist_id');
            
            // delete password column
            $table->dropColumn('password');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dentist_id', 'auth_provider']);
            $table->string('password')->after('email'); // Re-add password column
        });
    }
};
