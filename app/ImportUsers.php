<?php

namespace App;

use App\Models\Company;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;
use ZipArchive;

class ImportUsers
{
    /** @return array{created: int, skipped: int} */
    public function import(string $path, int $companyId): array
    {
        abort_unless(auth()->user()?->canAccessPanel(Filament::getPanel('admin')), 403);
        if (! Company::whereKey($companyId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['company_id' => 'Select an active company.']);
        }
        $rows = $this->read($path);

        return DB::transaction(function () use ($rows, $companyId): array {
            $created = 0;
            $skipped = 0;
            foreach ($rows as $row) {
                if (User::whereRaw('LOWER(email) = ?', [$row['email']])->exists()) {
                    $skipped++;

                    continue;
                }
                $user = new User;
                $user->forceFill($row + ['company_id' => $companyId, 'is_active' => true]);
                app(AccountOnboarding::class)->invite($user);
                $created++;
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    /** @return array<int, array{name: string, email: string}> */
    private function read(string $path): array
    {
        $archive = new ZipArchive;
        if (! is_file($path) || filesize($path) > 5 * 1024 * 1024 || $archive->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'Upload a valid .xlsx workbook, up to 5 MB.']);
        }
        try {
            $expandedSize = 0;
            for ($index = 0; $index < $archive->numFiles; $index++) {
                $expandedSize += $archive->statIndex($index)['size'];
            }
            if ($expandedSize > 20 * 1024 * 1024 || $archive->numFiles > 2000) {
                throw ValidationException::withMessages(['file' => 'This workbook is too large. Use the template with no more than 100 users.']);
            }
        } finally {
            $archive->close();
        }
        $options = new Options;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);
        $rows = [];
        $errors = [];
        try {
            $reader->open($path);
            foreach ($reader->getSheetIterator() as $sheet) {
                $headers = null;
                foreach ($sheet->getRowIterator() as $number => $row) {
                    if ($number > 1001) {
                        throw ValidationException::withMessages(['file' => 'Remove extra blank rows and use no more than 100 users.']);
                    }
                    $values = [];
                    foreach ($row->getCells() as $cell) {
                        if ($cell instanceof FormulaCell || (! is_scalar($cell->getValue()) && $cell->getValue() !== null)) {
                            throw ValidationException::withMessages(['file' => "Row {$number}: use plain text, not formulas or dates."]);
                        }
                        $values[] = trim((string) $cell->getValue());
                    }
                    while ($values !== [] && end($values) === '') {
                        array_pop($values);
                    }
                    if ($values === []) {
                        continue;
                    }
                    if ($headers === null) {
                        $headers = array_map(fn (string $value): string => Str::lower($value), $values);
                        $sorted = $headers;
                        sort($sorted);
                        if ($sorted !== ['email', 'name']) {
                            throw ValidationException::withMessages(['file' => 'Use exactly two column headings: name and email. Select the company in this form.']);
                        }

                        continue;
                    }
                    if (count($rows) >= 100) {
                        throw ValidationException::withMessages(['file' => 'Import up to 100 users at a time. Split larger workbooks into separate files.']);
                    }
                    if (count($values) > 2) {
                        $errors[] = "Row {$number}: only name and email columns are supported.";
                    }
                    $data = [
                        'name' => $values[array_search('name', $headers, true)] ?? '',
                        'email' => Str::lower($values[array_search('email', $headers, true)] ?? ''),
                    ];
                    $validator = Validator::make($data, ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email', 'max:255']]);
                    foreach ($validator->errors()->all() as $message) {
                        $errors[] = "Row {$number}: {$message}";
                    }
                    $rows[] = $data;
                }
                break;
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw ValidationException::withMessages(['file' => 'The workbook could not be read. Save it as .xlsx using the template and try again.']);
        } finally {
            $reader->close();
        }
        if ($errors !== []) {
            throw ValidationException::withMessages(['file' => array_slice($errors, 0, 10)]);
        }
        if ($rows === []) {
            throw ValidationException::withMessages(['file' => 'Add at least one user below the column headings.']);
        }

        return $rows;
    }
}
