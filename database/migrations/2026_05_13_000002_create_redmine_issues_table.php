<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redmine_issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('redmine_issue_id')->unique();
            $table->string('project_name', 255);
            $table->string('subject', 500);
            $table->longText('description')->nullable();
            $table->unsignedInteger('assigned_to_redmine_id')->nullable();
            $table->string('assigned_to_login', 191)->nullable();
            $table->string('priority_name', 191)->nullable();
            $table->date('due_date')->nullable();
            $table->text('labels')->nullable();
            $table->decimal('estimated_hours', 10, 2)->nullable();
            $table->string('tracker_name', 191)->nullable();
            $table->string('status_name', 191)->nullable();
            $table->boolean('status_is_closed')->default(false);
            $table->unsignedTinyInteger('done_ratio')->default(0);
            $table->timestamp('redmine_updated_on')->nullable();
            $table->timestamps();

            $table->index(['assigned_to_redmine_id', 'status_is_closed']);
            $table->index('status_is_closed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redmine_issues');
    }
};
