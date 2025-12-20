<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ResponsesExport implements FromCollection, WithHeadings, WithMapping
{
    protected Collection $responses;

    public function __construct(Collection $responses)
    {
        $this->responses = $responses;
    }

    public function collection(): Collection
    {
        return $this->responses;
    }

    public function headings(): array
    {
        return [
            '이름',
            '생년월일',
            '연락처',
            '은행',
            '계좌번호',
            '중식신청',
            '결과',
            '상태',
            '훈련명',
            '입소시간',
            '퇴소시간',
            '등록일시',
        ];
    }

    public function map($response): array
    {
        return [
            $response->name,
            $response->dob,
            $response->phone,
            $response->bank_name,
            $response->account_num,
            $response->lunch_yn ? 'O' : 'X',
            $response->survey_result,
            $this->getStatusText($response->attendance_status),
            $response->training?->name ?? '-',
            $response->entered_at?->format('Y-m-d H:i:s') ?? '-',
            $response->exited_at?->format('Y-m-d H:i:s') ?? '-',
            $response->created_at->format('Y-m-d H:i:s'),
        ];
    }

    private function getStatusText(string $status): string
    {
        return match ($status) {
            'registered' => '대기',
            'entered' => '입소',
            'exited' => '퇴소',
            default => $status,
        };
    }
}
