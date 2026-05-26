<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'redmine_user_id',
        'login',
        'grades',
        'competencies',
        'experience_achievements',
        'ignored',
    ];

    protected $casts = [
        'redmine_user_id' => 'integer',
        'ignored' => 'boolean',
    ];

    /**
     * @param Builder<Employee> $query
     * @return Builder<Employee>
     */
    public function scopeForRecommendations(Builder $query): Builder
    {
        return $query->where('ignored', false);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(RedmineIssue::class, 'assigned_to_redmine_id', 'redmine_user_id');
    }

    public function openAssignedIssues(): HasMany
    {
        return $this->issues()->where('status_is_closed', false);
    }

    public function plannedHoursOnIncomplete(): float
    {
        return (float) $this->openAssignedIssues()
            ->where('done_ratio', '<', 100)
            ->sum('estimated_hours');
    }

    /**
     * @return array<int, string>
     */
    public function inProgressTaskSubjects(): array
    {
        $names = config('redmine.in_progress_status_names', []);
        $q = $this->openAssignedIssues()->where('done_ratio', '<', 100);

        if ($names !== []) {
            $q->whereIn('status_name', $names);
        }

        return $q->orderBy('subject')->pluck('subject')->all();
    }
}
