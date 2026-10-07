<?php

namespace App;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DropboxClient
{
    private function request(): PendingRequest
    {
        $token = config('services.dropbox.access_token');
        if (! $token) {
            throw new RuntimeException('Dropbox access token is not configured.');
        }

        return Http::withToken($token)->connectTimeout(10)->timeout(60);
    }

    private function check(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        if ($response->status() === 400 && (str_contains($response->body(), 'files.metadata.read') || str_contains($response->body(), 'files.content.read'))) {
            throw new RuntimeException('Enable files.metadata.read and files.content.read in Dropbox Permissions, then generate a new token.');
        }

        throw new RuntimeException(match ($response->status()) {
            401 => 'Dropbox token is invalid or expired. Update DROPBOX_ACCESS_TOKEN.',
            403 => 'Dropbox permission denied. Enable files.metadata.read and files.content.read.',
            409 => 'Dropbox file or folder is unavailable. Check the saved folder path and account access.',
            429 => 'Dropbox rate limit reached. Try syncing again later.',
            default => 'Dropbox request failed. Please try again later.',
        });
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function rpc(string $endpoint, array $data): array
    {
        try {
            $response = $this->request()->post('https://api.dropboxapi.com/2/files/'.$endpoint, $data);
        } catch (ConnectionException) {
            throw new RuntimeException('Dropbox connection failed. Please try again later.');
        }
        $this->check($response);

        return $response->json();
    }

    /** @return array<int, array<string, mixed>> */
    public function files(string $folder): array
    {
        $page = $this->rpc('list_folder', ['path' => $folder, 'recursive' => true]);
        $entries = $page['entries'];
        while ($page['has_more']) {
            $page = $this->rpc('list_folder/continue', ['cursor' => $page['cursor']]);
            $entries = array_merge($entries, $page['entries']);
        }

        return array_values(array_filter($entries, fn (array $entry): bool => $entry['.tag'] === 'file'));
    }

    public function thumbnail(string $fileId): string
    {
        $response = $this->request()->timeout(15)->withOptions(['stream' => true])->withHeaders([
            'Dropbox-API-Arg' => json_encode([
                'resource' => ['.tag' => 'path', 'path' => $fileId],
                'format' => 'png', 'size' => 'w256h256', 'mode' => 'strict',
                'preserve_transparency' => true,
            ], JSON_THROW_ON_ERROR),
        ])->withBody('', 'application/octet-stream')->post('https://content.dropboxapi.com/2/files/get_thumbnail_v2');
        $body = $response->toPsrResponse()->getBody();
        try {
            if (! $response->successful()) {
                throw new RuntimeException('Dropbox thumbnail is unavailable.');
            }
            $bytes = '';
            while (! $body->eof() && strlen($bytes) <= 2 * 1024 * 1024) {
                $bytes .= $body->read(65536);
            }
            $image = @getimagesizefromstring($bytes);
            if (strlen($bytes) > 2 * 1024 * 1024 || ! $image || $image[2] !== IMAGETYPE_PNG || $image[0] > 256 || $image[1] > 256) {
                throw new RuntimeException('Invalid Dropbox thumbnail.');
            }

            return $bytes;
        } finally {
            $body->close();
        }
    }

    public function downloadTo(string $fileId, string $destination, ?int $maxBytes = null): void
    {
        try {
            $response = $this->request()->withOptions(['stream' => true])->withHeaders([
                'Dropbox-API-Arg' => json_encode(['path' => $fileId], JSON_THROW_ON_ERROR),
            ])->withBody('', 'application/octet-stream')->post('https://content.dropboxapi.com/2/files/download');
        } catch (ConnectionException) {
            throw new RuntimeException('Dropbox download connection failed. Please try again.');
        }
        $this->check($response);
        $body = $response->toPsrResponse()->getBody();
        $output = fopen($destination, 'wb');
        if ($output === false) {
            $body->close();
            throw new RuntimeException('Cannot prepare the download.');
        }
        $size = 0;
        try {
            while (! $body->eof()) {
                $chunk = $body->read(65536);
                $size += strlen($chunk);
                if ($maxBytes !== null && $size > $maxBytes) {
                    throw new RuntimeException('Selected files exceed the archive size limit.');
                }
                if (fwrite($output, $chunk) !== strlen($chunk)) {
                    throw new RuntimeException('Cannot prepare the download.');
                }
            }
        } finally {
            fclose($output);
            $body->close();
        }
    }
}
