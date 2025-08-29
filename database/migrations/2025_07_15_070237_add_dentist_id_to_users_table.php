<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('dentist_id')->nullable()->unique()->after('id');
            $table->string('auth_provider')->nullable()->after('dentist_id');

            // delete password column
            if (Schema::hasColumn('users', 'password')) {
                $table->dropColumn('password');
            }

            if (Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable()->change();
            } else {
                $table->string('avatar')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['dentist_id', 'auth_provider']);
            $table->string('password')->after('email'); // Re-add password column
        });
    }
};
