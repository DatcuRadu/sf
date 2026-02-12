<?php

namespace App\Services\Epicor\Estu;

use Exception;
use Illuminate\Support\Collection;

abstract class AbstractLine
{
    public function __construct(
        protected EpicorFormatter $fmt
    ) {}

    /**
     * Each concrete line must define schema
     */
    abstract protected function schema(): array;

    /* ===================================================
        PUBLIC API
    =================================================== */

    public function build(array $values): string
    {
        $merged = $this->mergeSchemaWithValues($values);


        $this->validate($merged);

        return $this->buildLine($merged);
    }

    /* ===================================================
        MERGE
    =================================================== */

    protected function mergeSchemaWithValues(array $values): array
    {
        $schema = collect($this->schema());

        return $schema
            ->mergeRecursive(
                collect($values)
                    ->intersectByKeys($schema) // 👈 ia DOAR cheile existente în schema
                    ->map(fn ($v) => ['value' => $v])
                    ->toArray()
            )
            ->toArray();
    }

    /* ===================================================
        VALIDATION
    =================================================== */

    protected function validate(array $fields): void
    {
        foreach ($fields as $key => $field) {

            $value = $field['value'] ?? null;

            if (($field['required'] ?? false) && ($value === null || $value === '')) {
                throw new Exception("Epicor field [$key] is required");
            }

            // Validate text/numeric length (raw input safety)
//            if (isset($field['length']) && $value !== null) {
//
//                if (strlen((string) $value) > $field['length'] && $field['type'] !== 'signed') {
//
//                    throw new Exception("Epicor field [$key] exceeds length {$field['length']}");
//                }
//            }
        }
    }

    /* ===================================================
        BUILD LINE
    =================================================== */

    protected function buildLine(array $fields): string
    {
        $line = '';

        foreach ($fields as $field) {

            $type  = $field['type'];
            $value = $field['value'] ?? null;

            $line .= match ($type) {

                'literal' => $this->fmt->text($value, $field['length']),

                'text' => $this->fmt->text(
                    $value,
                    $field['length']
                ),

                'numeric' => $this->fmt->numeric(
                    $value,
                    $field['length'],
                    $field['dec'] ?? 0,
                    true
                ),

                'signed' => $this->fmt->signedNumeric(
                    $value,
                    $field['int'],
                    $field['dec']
                ),

                'filler' => $this->fmt->filler(
                    $field['length']
                ),

                default => ''
            };
        }

        return $line;
    }
}
