<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_text',
        'description',
        'question_type',
        'options',
        'order_num',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'order_num' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_num', 'asc');
    }

    public static function getActiveQuestions()
    {
        return static::active()->ordered()->get();
    }

    public function hasDangerOption(string $answerValue): bool
    {
        $options = $this->options ?? [];
        $option = collect($options)->firstWhere('value', $answerValue);
        return $option ? ($option['is_danger'] ?? false) : false;
    }
}
