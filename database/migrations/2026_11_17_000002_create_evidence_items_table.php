<?php

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
        Schema::create('evidence_items', function (Blueprint $table) {
            $table->id();
            $table->string('evidence_id')->unique(); // e.g. 'EV-404-01'
            $table->foreignId('case_file_id')->nullable()->constrained('case_files')->nullOnDelete();
            $table->string('file_name');
            $table->string('file_url')->nullable();
            $table->string('file_type')->default('image'); // 'image', 'log', 'dump', 'pcap'
            $table->string('sha256_hash')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->string('classification')->default('CONFIDENTIAL'); // 'RESTRICTED', 'TLP:RED', 'CONFIDENTIAL'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_items');
    }
};

