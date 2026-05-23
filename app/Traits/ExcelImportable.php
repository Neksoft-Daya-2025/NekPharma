<?php

namespace App\Traits;

trait ExcelImportable
{

    private function getColumnValue(string $column)
    {
        $index = $this->columnIndexFor($column);

        return $index === null ? null : ($this->row[$index] ?? null);
    }

    private function isColumnExists(string $column)
    {
        return $this->columnIndexFor($column) !== null;
    }

    private function columnIndexFor(string $column): ?int
    {
        $indices = array_keys($this->columns, $column, true);

        if (empty($indices)) {
            return null;
        }

        return (int) min($indices);
    }

    private function getRowValuesAsString(array $values)
    {
        return implode(', ', $values);
    }

    private function failJob(string $message)
    {
        $this->job->fail($message . $this->getRowValuesAsString($this->row));
    }

    private function failJobWithMessage(string $message)
    {
        $this->job->fail($message);
    }

    private function isEmailValid(string|null $email)
    {
        if (empty($email)) {
            return false;
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

}
