<?php

use App\Enum\CertificateStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_certificate', function (Blueprint $table) {
            $table->string('status')->default(CertificateStatus::PENDING->value);
            $table->string('file_path')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_certificate', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->string('file_path')->nullable(false)->change();
        });
    }
};
