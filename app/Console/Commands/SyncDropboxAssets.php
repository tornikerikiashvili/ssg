<?php

namespace App\Console\Commands;

use App\DropboxClient;
use App\Models\Game;
use App\SyncDropboxGame;
use Illuminate\Console\Command;
use RuntimeException;

class SyncDropboxAssets extends Command
{
    protected $signature = 'dropbox:sync {game? : Game ID} {--check : Test the configured token without importing files}';

    protected $description = 'Sync Dropbox assets for games with a linked folder';

    public function handle(SyncDropboxGame $sync, DropboxClient $dropbox): int
    {
        if ($this->option('check')) {
            try {
                $dropbox->rpc('list_folder', ['path' => '', 'limit' => 1]);
                $this->info('Dropbox connection successful. Folder read permission verified.');

                return self::SUCCESS;
            } catch (RuntimeException $exception) {
                $this->error($exception->getMessage());

                return self::FAILURE;
            }
        }
        $failed = false;
        $games = Game::whereNotNull('dropbox_folder_path')->when($this->argument('game'), fn ($query, $id) => $query->whereKey($id))->get();
        if ($this->argument('game') && $games->isEmpty()) {
            $this->error('No linked game found.');

            return self::FAILURE;
        }
        foreach ($games as $game) {
            try {
                $count = $sync->sync($game);
                $this->info('Game '.$game->id.': '.$count.' assets synced.');
            } catch (RuntimeException $exception) {
                $failed = true;
                $this->error('Game '.$game->id.': '.$exception->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
