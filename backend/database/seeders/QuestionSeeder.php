<?php

namespace Database\Seeders;

use App\Models\Question;
use Illuminate\Database\Seeder;

class QuestionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questions = [
            [
                'question_text' => '최근 2주 이내에 37.5도 이상의 발열이 있었습니까?',
                'description' => null,
                'question_type' => 'yes_no',
                'options' => [
                    ['label' => '예', 'value' => 'yes', 'is_danger' => true],
                    ['label' => '아니오', 'value' => 'no', 'is_danger' => false],
                ],
                'order_num' => 1,
                'is_active' => true,
            ],
            [
                'question_text' => '최근 2주 이내에 기침, 인후통, 호흡곤란 등의 호흡기 증상이 있었습니까?',
                'description' => null,
                'question_type' => 'yes_no',
                'options' => [
                    ['label' => '예', 'value' => 'yes', 'is_danger' => true],
                    ['label' => '아니오', 'value' => 'no', 'is_danger' => false],
                ],
                'order_num' => 2,
                'is_active' => true,
            ],
            [
                'question_text' => '현재 자가격리 또는 격리 해제 대상자입니까?',
                'description' => null,
                'question_type' => 'yes_no',
                'options' => [
                    ['label' => '예', 'value' => 'yes', 'is_danger' => true],
                    ['label' => '아니오', 'value' => 'no', 'is_danger' => false],
                ],
                'order_num' => 3,
                'is_active' => true,
            ],
            [
                'question_text' => '최근 2주 이내에 확진자와 접촉한 적이 있습니까?',
                'description' => null,
                'question_type' => 'yes_no',
                'options' => [
                    ['label' => '예', 'value' => 'yes', 'is_danger' => true],
                    ['label' => '아니오', 'value' => 'no', 'is_danger' => false],
                ],
                'order_num' => 4,
                'is_active' => true,
            ],
            [
                'question_text' => '현재 본인의 건강 상태는 어떻습니까?',
                'description' => '훈련 참여에 영향을 줄 수 있는 증상이 있다면 "주의 필요"를 선택해주세요.',
                'question_type' => 'multiple_choice',
                'options' => [
                    ['label' => '양호', 'value' => 'good', 'is_danger' => false],
                    ['label' => '주의 필요', 'value' => 'caution', 'is_danger' => false],
                    ['label' => '훈련 참여 어려움', 'value' => 'unable', 'is_danger' => true],
                ],
                'order_num' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($questions as $question) {
            Question::create($question);
        }
    }
}
