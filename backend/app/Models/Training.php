<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Training extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'access_code',
        'start_date',
        'end_date',
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

    protected $appends = [
        'lunch_image_mon_url',
        'lunch_image_tue_url',
        'lunch_image_wed_url',
        'lunch_image_thu_url',
        'lunch_image_fri_url',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'auto_purge_at' => 'datetime',
            'purge_days' => 'integer',
        ];
    }

    /**
     * Convert storage path to full URL
     */
    protected function getImageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // If it's already a full URL, return as-is
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        // Convert storage path to URL
        return Storage::disk('public')->url($path);
    }

    public function getLunchImageMonUrlAttribute(): ?string
    {
        return $this->getImageUrl($this->lunch_image_mon);
    }

    public function getLunchImageTueUrlAttribute(): ?string
    {
        return $this->getImageUrl($this->lunch_image_tue);
    }

    public function getLunchImageWedUrlAttribute(): ?string
    {
        return $this->getImageUrl($this->lunch_image_wed);
    }

    public function getLunchImageThuUrlAttribute(): ?string
    {
        return $this->getImageUrl($this->lunch_image_thu);
    }

    public function getLunchImageFriUrlAttribute(): ?string
    {
        return $this->getImageUrl($this->lunch_image_fri);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($training) {
            if (empty($training->access_code)) {
                $training->access_code = self::generateUniqueCode();
            }
        });
    }

    public static function generateUniqueCode(): string
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (self::where('access_code', $code)->exists());

        return $code;
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

    public function pause(): void
    {
        $this->update([
            'status' => 'scheduled',
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

    public function getAccessUrl(): string
    {
        return config('app.frontend_url', '') . '/t/' . $this->access_code;
    }
}
