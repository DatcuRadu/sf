<?php

namespace App\Services\Epicor\Estu;


class EstuParser
{
    protected EpicorFormatter $fmt;

    public function __construct(EpicorFormatter $fmt)
    {
        $this->fmt = $fmt;
    }

    /**
     * Parsează un fișier ESTU (string complet)
     */
    public function parse(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", trim($content));

        $out = [
            'header' => [],
            'details' => [],
        ];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $type = $line[0];

            switch ($type) {
                case 'H':
                    $out['header'] = $this->parseLine(
                        $line,
                        new HeaderLine($this->fmt)
                    );
                    break;

                case 'D':
                    $out['details'][] = $this->parseLine(
                        $line,
                        new DetailLine($this->fmt)
                    );
                    break;

                default:
                    // linii necunoscute – le ignorăm (Epicor e messy)
                    break;
            }
        }

        return $out;
    }

    /**
     * Parsează o linie pe baza schemei (HeaderLine / DetailLine)
     */
    protected function parseLine(string $line, AbstractLine $lineDef): array
    {
        $result = [];

        foreach ($lineDef->schema() as $key => $field) {

            // positions: "1-3"
            if (!isset($field['positions'])) {
                continue;
            }

            [$start, $end] = explode('-', $field['positions']);

            $start = (int)$start;
            $end = (int)$end;

            if ($start <= 0 || $end <= 0 || $end < $start) {
                continue;
            }

            // substr este 0-based
            $raw = substr($line, $start - 1, $end - $start + 1);
            $raw = rtrim($raw);

            $type = $field['type'] ?? 'text';
            $dec = $field['dec'] ?? 0;

            try {
                switch ($type) {
                    case 'numeric':
                        $value = $this->fmt->parseNumeric($raw, $dec);
                        break;

                    case 'signed':
                        $value = $this->fmt->parseSignedNumeric($raw, $dec);
                        break;

                    case 'text':
                    default:
                        $value = $this->fmt->parseText($raw);
                        break;
                }
            } catch (\Throwable $e) {
                // fallback sigur (Epicor poate trimite junk)
                $value = null;
            }

            // valoare default din schemă (ex: record_id = H / D)
            if ($value === null && array_key_exists('value', $field)) {
                $value = $field['value'];
            }

            $result[$key] = $value;
        }
        $result['_line_length'] = strlen($line);
        return $result;
    }
}
