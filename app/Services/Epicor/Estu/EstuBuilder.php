<?php

namespace App\Services\Epicor\Estu;
use Illuminate\Support\Facades\Storage;

class EstuBuilder
{
    private array $lines = [];

    public function __construct(
        private HeaderLine $headerLine,
        private DetailLine $detailLine,
    ) {}

    public function addHeader(array $data): self
    {
        $this->lines[] = $this->headerLine->build($data);
        return $this;
    }

    public function addDetail(array $data): self
    {
        $this->lines[] = $this->detailLine->build($data);
        return $this;
    }

    public function reset(): self
    {
        $this->lines = [];
        return $this;
    }

    public function build(): string
    {
        return implode("\n", $this->lines) . "\n";
    }

    public function save(string $filename = null, string $disk = 'local'): string
    {
        $filename ??= 'order_' . now()->format('Ymd_His') . '.estu';

        Storage::disk($disk)->put($filename, $this->build());

        return Storage::disk($disk)->path($filename);
    }
}
