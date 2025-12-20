<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;

class SurveyResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'uuid',
        'name',
        'dob',
        'phone',
        'bank_name',
        'account_num',
        'lunch_yn',
        'survey_result',
        'answers_json',
        'signature',
        'attendance_status',
        'entered_at',
        'exited_at',
        'exited_by',
        'manual_override',
        'override_reason',
        'qr_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'lunch_yn' => 'boolean',
            'manual_override' => 'boolean',
            'answers_json' => 'array',
            'entered_at' => 'datetime',
            'exited_at' => 'datetime',
            'qr_generated_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // 암호화된 필드 setter/getter
    public function setDobAttribute($value)
    {
        $this->attributes['dob'] = Crypt::encryptString($value);
    }

    public function getDobAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setPhoneAttribute($value)
    {
        $this->attributes['phone'] = Crypt::encryptString($value);
    }

    public function getPhoneAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setAccountNumAttribute($value)
    {
        $this->attributes['account_num'] = Crypt::encryptString($value);
    }

    public function getAccountNumAttribute($value)
    {
        if (!$value) return null;
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    // Relationships
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function exitedByAdmin()
    {
        return $this->belongsTo(Admin::class, 'exited_by');
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeEntered($query)
    {
        return $query->where('attendance_status', 'entered');
    }

    public function scopeExited($query)
    {
        return $query->where('attendance_status', 'exited');
    }

    // Helper methods
    public function markAsEntered(): void
    {
        $this->update([
            'attendance_status' => 'entered',
            'entered_at' => now(),
        ]);
    }

    public function markAsExited(?int $exitedBy = null): void
    {
        $this->update([
            'attendance_status' => 'exited',
            'exited_at' => now(),
            'exited_by' => $exitedBy,
        ]);
    }

    public function generateQR(): void
    {
        $this->update([
            'qr_generated_at' => now(),
            'attendance_status' => 'entered',
            'entered_at' => now(),
        ]);
    }

    public static function findByVerification(string $name, string $dob, string $phone): ?self
    {
        return static::where('name', $name)
            ->get()
            ->first(function ($response) use ($dob, $phone) {
                return $response->dob === $dob && $response->phone === $phone;
            });
    }

    public function calculateSurveyResult(array $answers, array $questions): string
    {
        $hasDanger = false;
        $hasCaution = false;

        foreach ($questions as $question) {
            $answer = $answers[$question['id']] ?? null;
            if (!$answer) continue;

            $option = collect($question['options'])->firstWhere('value', $answer);
            if ($option && ($option['is_danger'] ?? false)) {
                $hasDanger = true;
                break;
            }
        }

        if ($hasDanger) {
            return 'DANGER';
        } elseif ($hasCaution) {
            return 'CAUTION';
        }

        return 'NORMAL';
    }
}
