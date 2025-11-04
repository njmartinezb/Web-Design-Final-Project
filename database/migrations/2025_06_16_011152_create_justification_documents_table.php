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
        Schema::create('justification_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('justification_id')->constrained()->cascadeOnDelete();
            $table->binary('file_content');
            $table->string('file_name');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        //DB::statement('ALTER TABLE justification_documents MODIFY file_content LONGBLOB');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justification_documents');
    }
};
