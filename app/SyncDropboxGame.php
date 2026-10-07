<?php

namespace App;

use App\Models\CatalogOption;
use App\Models\FileFormat;
use App\Models\Game;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SyncDropboxGame
{
    public function __construct(private DropboxClient $dropbox) {}

    public function sync(Game $game): int
    {
        $lock = Cache::lock('dropbox-game-'.$game->id, 600);
        if (! $lock->get()) {
            throw new RuntimeException('A Dropbox sync is already running for this game.');
        }
        try {
            $game->refresh();
            if (! $game->dropbox_folder_path) {
                throw new RuntimeException('Save a Dropbox folder path first.');
            }
            $path = $game->dropbox_folder_path;
            $folder = $this->dropbox->rpc('get_metadata', ['path' => $game->dropbox_folder_id ?: $path]);
            if ($folder['.tag'] !== 'folder') {
                throw new RuntimeException('The Dropbox path must point to a folder.');
            }
            $files = $this->dropbox->files($folder['id']);

            return DB::transaction(function () use ($game, $path, $folder, $files): int {
                $current = Game::query()->lockForUpdate()->findOrFail($game->id);
                if ($current->dropbox_folder_path !== $path) {
                    throw new RuntimeException('The folder mapping changed during sync. Please sync again.');
                }
                $current->resources()->whereNotNull('dropbox_file_id')->update(['dropbox_available' => false]);
                $count = 0;
                foreach ($files as $file) {
                    $relative = ltrim(substr($file['path_display'], strlen($folder['path_display'])), '/');
                    $parent = dirname($relative);
                    $categoryName = $parent === '.' ? 'General' : str_replace('/', ' / ', $parent);
                    $category = CatalogOption::firstOrCreate(['kind' => 'asset', 'name' => mb_substr($categoryName, 0, 255)]);
                    $available = $file['is_downloadable'] ?? true;
                    $format = FileFormat::forFilename($file['name']);
                    $current->resources()->updateOrCreate(['dropbox_file_id' => $file['id']], [
                        'title' => $file['name'], 'slug' => 'dropbox-'.$game->id.'-'.hash('sha256', $file['id']),
                        'kind' => 'download', 'file_path' => $file['name'], 'dropbox_path' => $file['path_display'],
                        'dropbox_revision' => $file['rev'], 'file_size' => $file['size'],
                        'dropbox_available' => $available, 'catalog_option_id' => $category->id,
                        'file_format_id' => $format?->id,
                        'is_published' => true, 'is_demo' => false, 'company_id' => null,
                    ]);
                    $count += (int) $available;
                }
                $current->forceFill(['dropbox_folder_id' => $folder['id'], 'dropbox_synced_at' => now(), 'dropbox_sync_error' => null])->saveQuietly();

                return $count;
            });
        } catch (Throwable $exception) {
            $message = $exception instanceof RuntimeException ? $exception->getMessage() : 'Dropbox sync failed. Please try again.';
            Game::whereKey($game->id)->update(['dropbox_sync_error' => $message]);
            throw new RuntimeException($message);
        } finally {
            $lock->release();
        }
    }
}
