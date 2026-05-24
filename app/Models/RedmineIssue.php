<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedmineIssue extends Model
{
    protected $fillable = [
        'redmine_issue_id',
        'project_name',
        'subject',
        'description',
        'assigned_to_redmine_id',
        'assigned_to_login',
        'priority_name',
        'due_date',
        'labels',
        'estimated_hours',
        'tracker_name',
        'status_name',
        'status_is_closed',
        'done_ratio',
        'redmine_updated_on',
    ];

    protected $casts = [
        'redmine_issue_id' => 'integer',
        'assigned_to_redmine_id' => 'integer',
        'due_date' => 'date',
        'estimated_hours' => 'float',
        'status_is_closed' => 'boolean',
        'done_ratio' => 'integer',
        'redmine_updated_on' => 'datetime',
    ];

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_to_redmine_id', 'redmine_user_id');
    }

    /**
     * @return array<int, string>
     */
    public function labelsList(): array
    {
        if ($this->labels === null || $this->labels === '') {
            return [];
        }
        $decoded = json_decode($this->labels, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }

    public function isUnassigned(): bool
    {
        return $this->assigned_to_redmine_id === null;
    }

    public function toTaskPayloadArray(): array
    {
        return [
            'redmine_issue_id' => $this->redmine_issue_id,
            'project' => $this->project_name,
            'subject' => $this->subject,
            'description' => $this->description,
            'assigned_login' => $this->assigned_to_login,
            'priority' => $this->priority_name,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'labels' => $this->labelsList(),
            'estimated_hours' => $this->estimated_hours,
            'tracker' => $this->tracker_name,
            'status' => $this->status_name,
            'status_closed' => $this->status_is_closed,
            'done_ratio' => $this->done_ratio,
        ];
    }
}
