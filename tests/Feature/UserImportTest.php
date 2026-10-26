<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\ImportUsers;
use App\Models\Company;
use App\Models\User;
use App\Notifications\AccountInvitation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserImportTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        if (DB::connection()->getDatabaseName() !== 'smartsoft_testing') {
            throw new RuntimeException('Testing database required.');
        }
    }

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Filament::bootCurrentPanel();
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function workbook(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'user-import-test-');
        try {
            $writer = new Writer;
            $writer->openToFile($path);
            foreach ($rows as $row) {
                $writer->addRow(Row::fromValues($row));
            }
            $writer->close();

            return UploadedFile::fake()->createWithContent('users.xlsx', file_get_contents($path));
        } finally {
            unlink($path);
        }
    }

    public function test_admin_can_import_excel_and_invite_new_users_while_preserving_duplicates(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = Company::factory()->create();
        $existing = User::factory()->for(Company::factory())->create(['email' => 'existing@example.test']);
        $original = $existing->fresh()->getAttributes();
        $file = $this->workbook([
            ['name', 'email'], ['New Partner', ' NEW@EXAMPLE.TEST '],
            ['Duplicate', 'new@example.test'], ['Existing', 'existing@example.test'],
        ]);

        Livewire::test(ManageUsers::class)->callAction('importUsers', data: ['company_id' => $company->id, 'file' => $file])->assertHasNoActionErrors();

        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertSame($company->id, $user->company_id);
        $this->assertSame('New Partner', $user->name);
        $this->assertFalse($user->is_admin);
        $this->assertTrue($user->requires_password_setup);
        Notification::assertSentToTimes($user, AccountInvitation::class, 1);
        $invitation = Notification::sent($user, AccountInvitation::class)->first();
        $this->assertTrue(Hash::check($invitation->temporaryPassword, $user->password));
        Notification::assertNotSentTo($existing, AccountInvitation::class);
        $this->assertSame($original, $existing->fresh()->getAttributes());
        $this->assertDatabaseCount('users', 3);
    }

    public function test_invalid_rows_show_errors_and_create_no_users_or_invitations(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = Company::factory()->create();
        $file = $this->workbook([['name', 'email'], ['Valid', 'valid@example.test'], ['Invalid', 'invalid-email']]);

        Livewire::test(ManageUsers::class)->callAction('importUsers', data: ['company_id' => $company->id, 'file' => $file])->assertHasActionErrors(['file']);

        $this->assertDatabaseMissing('users', ['email' => 'valid@example.test']);
        Notification::assertNothingSent();
    }

    public function test_template_downloads_as_excel(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        Livewire::test(ManageUsers::class)->callAction('downloadImportTemplate')->assertFileDownloaded('users-import-template.xlsx');
    }

    public function test_inactive_company_and_extra_privilege_columns_are_rejected(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = Company::factory()->create(['is_active' => false]);
        $file = $this->workbook([['name', 'email'], ['Partner', 'partner@example.test']]);
        try {
            app(ImportUsers::class)->import($file->getRealPath(), $company->id);
            $this->fail('Inactive company accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('company_id', $exception->errors());
        }
        $company->update(['is_active' => true]);
        $file = $this->workbook([['name', 'email', 'is_admin'], ['Partner', 'partner@example.test', true]]);
        try {
            app(ImportUsers::class)->import($file->getRealPath(), $company->id);
            $this->fail('Extra columns accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('file', $exception->errors());
        }
        $this->assertDatabaseMissing('users', ['email' => 'partner@example.test']);
        Notification::assertNothingSent();
    }

    public function test_non_admin_cannot_import_users(): void
    {
        Notification::fake();
        $company = Company::factory()->create();
        $this->actingAs(User::factory()->for($company)->create());
        $file = $this->workbook([['name', 'email'], ['Partner', 'partner@example.test']]);
        try {
            app(ImportUsers::class)->import($file->getRealPath(), $company->id);
            $this->fail('Non-admin import accepted.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->get('/admin/users')->assertForbidden();
        Notification::assertNothingSent();
    }

    public function test_oversized_row_count_is_rejected_without_partial_import(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = Company::factory()->create();
        $rows = [['name', 'email']];
        for ($index = 0; $index < 101; $index++) {
            $rows[] = ['Partner', 'partner'.$index.'@example.test'];
        }
        $file = $this->workbook($rows);
        try {
            app(ImportUsers::class)->import($file->getRealPath(), $company->id);
            $this->fail('Too many rows accepted.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('100 users', $exception->errors()['file'][0]);
        }
        $this->assertDatabaseMissing('users', ['email' => 'partner0@example.test']);
        Notification::assertNothingSent();
    }

    public function test_invalid_workbook_and_formula_cells_are_rejected(): void
    {
        Notification::fake();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $company = Company::factory()->create();
        $files = [
            UploadedFile::fake()->createWithContent('users.xlsx', 'not an Excel workbook'),
            $this->workbook([['name', 'email'], ['=1+1', 'partner@example.test']]),
            $this->workbook([['name', 'email']]),
        ];
        foreach ($files as $file) {
            try {
                app(ImportUsers::class)->import($file->getRealPath(), $company->id);
                $this->fail('Invalid workbook accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('file', $exception->errors());
            }
        }
        $this->assertDatabaseCount('users', 1);
        Notification::assertNothingSent();
    }
}
