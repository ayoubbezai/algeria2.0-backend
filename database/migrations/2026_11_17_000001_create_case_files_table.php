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
        Schema::create('case_files', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique(); // e.g. '1042', '1043', '404'
            $table->string('tag'); // e.g. 'YOUR CASE', 'INTERNAL AFFAIRS'
            $table->string('title'); // e.g. 'Server Room Intrusion'
            $table->string('owner'); // e.g. 'Investigator #118'
            $table->unsignedBigInteger('owner_id')->nullable(); // user id of investigator
            $table->string('status')->default('Active'); // 'Active', 'Restricted', 'Closed'
            $table->text('notes')->nullable();
            $table->string('evidence')->nullable(); // e.g. '4 files attached'
            $table->boolean('restricted')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('case_files');
    }
};

