<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'training_date',
        'status',
        'lunch_image_mon',
        'lunch_image_tue',
        'lunch_image_wed',
        'lunch_image_thu',
        'lunch_image_fri',
        'exit_mode',
        'auto_purge_at',
        'purge_days',
    ];

    protected function casts(): array
    {
        return [
            'training_date' => 'date',
            'auto_purge_at' => 'datetime',
            'purge_days' => 'integer',
        ];
    }

    public function surveyResponses()
    {
        return $this->hasMany(SurveyResponse::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeNotPurged($query)
    {
        return $query->whereIn('status', ['scheduled', 'active', 'completed']);
    }

    public function getLunchImageForDay(?string $day = null): ?string
    {
        if (!$day) {
            $day = strtolower(now()->format('D'));
        }

        $dayMap = [
            'mon' => 'lunch_image_mon',
            'tue' => 'lunch_image_tue',
            'wed' => 'lunch_image_wed',
            'thu' => 'lunch_image_thu',
            'fri' => 'lunch_image_fri',
        ];

        $field = $dayMap[$day] ?? null;
        return $field ? $this->$field : null;
    }

    public function activate(): void
    {
        $this->update([
            'status' => 'active',
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'auto_purge_at' => now()->addDays($this->purge_days),
        ]);
    }

    public function getTodayStats(): array
    {
        $responses = $this->surveyResponses();

        return [
            'total' => $responses->count(),
            'entered' => $responses->where('attendance_status', 'entered')->count(),
            'exited' => $responses->where('attendance_status', 'exited')->count(),
            'normal' => $responses->where('survey_result', 'NORMAL')->count(),
            'caution' => $responses->where('survey_result', 'CAUTION')->count(),
            'danger' => $responses->where('survey_result', 'DANGER')->count(),
            'lunch' => $responses->where('lunch_yn', true)->count(),
        ];
    }
}
