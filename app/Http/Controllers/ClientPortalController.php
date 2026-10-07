<?php

namespace App\Http\Controllers;

use App\DropboxClient;
use App\DropboxThumbnail;
use App\Models\Announcement;
use App\Models\CatalogOption;
use App\Models\EngagementTool;
use App\Models\Game;
use App\Models\ResourceDownload;
use App\Models\ResourceItem;
use App\Models\RoadmapItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ClientPortalController extends Controller
{
    public function thumbnail(Request $request, int $resourceItem, DropboxThumbnail $thumbnails): Response
    {
        $resource = ResourceItem::visibleTo($request->user())->findOrFail($resourceItem);
        $bytes = $thumbnails->get($resource);
        abort_if($bytes === null, 404);

        return response($bytes, 200, [
            'Content-Type' => 'image/png', 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function search(Request $request): RedirectResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'search_category' => ['required', Rule::in(['games', 'download', 'documentation', 'certificate'])],
        ]);
        $parameters = ['q' => $filters['q'] ?? ''];

        if ($filters['search_category'] === 'games') {
            return to_route('games.index', $parameters);
        }

        return to_route('resources.index', ['kind' => $filters['search_category']] + $parameters);
    }

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $since = now()->subDays(7);
        $games = Game::visibleTo($user);
        $purchasedGames = (clone $games)->purchasedBy($user);
        $availableGames = (clone $games)->whereNotIn('id', (clone $purchasedGames)->select('games.id'))
            ->whereHas('regionAvailabilities', fn ($regions) => $regions
                ->whereIn('region_id', $user->accessibleRegionIds())
                ->whereIn('status', ['available', 'limited']));
        $assets = ResourceItem::visibleTo($user)->where('kind', 'download');
        $downloads = ResourceDownload::where('user_id', $user->id);

        return view('client.dashboard', [
            'games' => (clone $purchasedGames)->orderByDesc('is_featured')->orderBy('title')->limit(6)->get(),
            'recentGames' => (clone $games)->latest()->orderByDesc('id')->limit(5)->get(),
            'availableGames' => (clone $availableGames)->latest()->orderByDesc('id')->limit(6)->get(),
            'featuredGame' => (clone $games)->where('is_featured', true)->latest()->orderByDesc('id')->first(),
            'gameCount' => (clone $games)->count(),
            'purchasedGameCount' => (clone $purchasedGames)->count(),
            'availableGameCount' => (clone $availableGames)->count(),
            'newGameCount' => (clone $games)->where('created_at', '>=', $since)->count(),
            'newAssetCount' => (clone $assets)->where('created_at', '>=', $since)->count(),
            'assetCount' => (clone $assets)->count(),
            'featuredCount' => (clone $games)->where('is_featured', true)->count(),
            'newFeaturedCount' => (clone $games)->where('is_featured', true)->where('created_at', '>=', $since)->count(),
            'downloadCount' => (clone $downloads)->count(),
            'weeklyDownloadCount' => (clone $downloads)->where('created_at', '>=', $since)->count(),
            'recentDownloads' => ResourceDownload::recentFor($user),
            'documentationCount' => ResourceItem::visibleTo($user)->where('kind', 'documentation')->count(),
        ]);
    }

    public function games(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', Rule::exists('catalog_options', 'name')->where('kind', 'category')],
            'game_type_id' => ['nullable', 'integer', Rule::exists('catalog_options', 'id')->where('kind', 'game_type')],
            'payout_type_id' => ['nullable', 'integer', Rule::exists('catalog_options', 'id')->where('kind', 'payout_type')],
            'volatility_id' => ['nullable', 'integer', Rule::exists('catalog_options', 'id')->where('kind', 'volatility')],
            'sort' => ['nullable', Rule::in(['name', 'newest', 'release'])],
        ]);
        $games = Game::visibleTo($request->user())
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'ilike', '%'.$q.'%'))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->whereHas('categoryTerm', fn ($term) => $term->where('name', $category)))
            ->when($filters['game_type_id'] ?? null, fn ($query, $value) => $query->where('game_type_id', $value))
            ->when($filters['payout_type_id'] ?? null, fn ($query, $value) => $query->where('payout_type_id', $value))
            ->when($filters['volatility_id'] ?? null, fn ($query, $value) => $query->where('volatility_id', $value))
            ->when(($filters['sort'] ?? 'name') === 'name', fn ($query) => $query->orderBy('title'))
            ->when(($filters['sort'] ?? '') === 'newest', fn ($query) => $query->latest()->orderByDesc('id'))
            ->when(($filters['sort'] ?? '') === 'release', fn ($query) => $query->orderByRaw('release_date desc nulls last'))
            ->orderBy('id')->with('categoryTerm')->paginate(9)->withQueryString();

        $recentDownloads = ResourceDownload::recentFor($request->user());

        return view('client.games', ['recentDownloads' => $recentDownloads] + ['games' => $games, 'catalogOptions' => CatalogOption::orderBy('sort_order')->orderBy('name')->get()->groupBy('kind')]);
    }

    public function game(Request $request, string $slug): View
    {
        $game = Game::visibleTo($request->user())->with('regionAvailabilities.region')->where('slug', $slug)->firstOrFail();
        $resources = $game->resources()->visibleTo($request->user())->whereIn('kind', ['download', 'certificate'])
            ->orderBy('title')->orderBy('id')->with('catalogOption')->get();
        $documents = $game->resources()->visibleTo($request->user())->where('kind', 'documentation')->with('catalogOption')->get();
        $tools = $game->engagementTools()->visibleTo($request->user())->get();
        $relatedGames = Game::visibleTo($request->user())->whereKeyNot($game->id)->with('categoryTerm')->limit(6)->get();
        $assetCategories = $this->dropboxAssetCategories($resources);

        return view('client.game', compact('game', 'resources', 'documents', 'tools', 'assetCategories', 'relatedGames'));
    }

    public function resources(Request $request, string $kind): View
    {
        abort_unless(in_array($kind, ['download', 'documentation', 'certificate'], true), 404);
        $rules = ['q' => ['nullable', 'string', 'max:100']];
        if ($kind === 'documentation') {
            $rules += [
                'documentation_category_id' => ['nullable', 'integer', Rule::exists('catalog_options', 'id')->where('kind', 'document')],
                'sort' => ['nullable', Rule::in(['name', 'newest'])],
            ];
        }
        if ($kind === 'download') {
            $rules += [
                'category' => ['nullable', 'string', Rule::exists('catalog_options', 'name')->where('kind', 'category')],
                'game_type_id' => ['nullable', 'integer', Rule::exists('catalog_options', 'id')->where('kind', 'game_type')],
                'payout_type_id' => ['nullable', 'integer', Rule::exists('catalog_options', 'id')->where('kind', 'payout_type')],
                'volatility_id' => ['nullable', 'integer', Rule::exists('catalog_options', 'id')->where('kind', 'volatility')],
                'sort' => ['nullable', Rule::in(['name', 'newest', 'release'])],
            ];
        }
        $filters = $request->validate($rules);
        $query = ResourceItem::visibleTo($request->user())->with(['game', 'catalogOption'])->where('kind', $kind)
            ->when(in_array($kind, ['certificate', 'documentation'], true), fn ($query) => $query->whereNull('game_id'))
            ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'ilike', '%'.$q.'%'));
        if ($kind === 'documentation') {
            $query->when($filters['documentation_category_id'] ?? null, fn ($query, $category) => $query->where('catalog_option_id', $category))
                ->when(($filters['sort'] ?? 'name') === 'newest', fn ($query) => $query->latest()->orderByDesc('id'));
        }
        if ($kind === 'download') {
            $query->when($filters['category'] ?? null, fn ($query, $category) => $query->whereHas('game.categoryTerm', fn ($term) => $term->where('name', $category)));
            foreach (['game_type_id', 'payout_type_id', 'volatility_id'] as $field) {
                $query->when($filters[$field] ?? null, fn ($query, $value) => $query->whereHas('game', fn ($game) => $game->where($field, $value)));
            }
            $sort = $filters['sort'] ?? 'name';
            $column = ['name' => 'title', 'newest' => 'created_at', 'release' => 'release_date'][$sort];
            $gameValue = Game::select($column)->whereColumn('games.id', 'resource_items.game_id');
            if ($sort === 'name') {
                $query->orderBy($gameValue);
            } else {
                $query->orderByRaw('('.$gameValue->toSql().') DESC NULLS LAST', $gameValue->getBindings())
                    ->orderByDesc('game_id');
            }
        }
        if ($kind === 'download') {
            $folders = (clone $query)->withoutEagerLoads()->reorder()->select('game_id')->groupBy('game_id');
            if ($sort === 'name') {
                $folders->orderBy($gameValue)->orderBy('game_id');
            } else {
                $folders->orderByRaw('('.$gameValue->toSql().') DESC NULLS LAST', $gameValue->getBindings())
                    ->orderByDesc('game_id');
            }
            $resources = $folders->paginate(12)->withQueryString();
            $gameIds = $resources->getCollection()->pluck('game_id');
            $folderAssets = $query->where(function ($query) use ($gameIds): void {
                $query->whereIn('game_id', $gameIds->filter(fn ($id) => $id !== null));
                if ($gameIds->contains(null)) {
                    $query->orWhereNull('game_id');
                }
            })->orderBy('title')->orderBy('id')->get();
            $resources->setCollection($folderAssets);
        } else {
            $resources = $query->orderBy('title')->orderBy('id')->paginate(12)->withQueryString();
        }
        $licenses = $kind === 'certificate'
            ? ResourceItem::visibleTo($request->user())->where('kind', 'license')->whereNull('game_id')
                ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'ilike', '%'.$q.'%'))
                ->orderBy('title')->orderBy('id')->paginate(12, ['*'], 'licenses_page')->withQueryString()
            : null;
        $title = ['download' => 'Download Center', 'documentation' => 'Documentation', 'certificate' => 'Licenses & Certificates'][$kind];

        return view('client.resources', ['recentDownloads' => ResourceDownload::recentFor($request->user()), 'assetCategories' => $this->dropboxAssetCategories($resources->getCollection()), 'catalogOptions' => CatalogOption::orderBy('sort_order')->orderBy('name')->get()->groupBy('kind')] + compact('resources', 'licenses', 'title', 'kind'));
    }

    /** @param Collection<int, ResourceItem> $resources
     * @return array<int, string>
     */
    private function dropboxAssetCategories(Collection $resources): array
    {
        return $resources->whereNotNull('dropbox_file_id')->pluck('catalogOption')->filter()
            ->unique('id')->sortBy('name')->pluck('name', 'id')->all();
    }

    public function resource(Request $request, int $resourceItem): View|StreamedResponse|RedirectResponse
    {
        $resource = ResourceItem::visibleTo($request->user())->with('game')->findOrFail($resourceItem);

        if ($resource->isDocumentationLink()) {
            abort_unless(filter_var($resource->external_url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($resource->external_url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true), 404);

            return redirect()->away($resource->external_url);
        }

        if ($resource->kind === 'documentation') {
            return $this->fileResponse($request, $resource);
        }

        return view('client.resource', compact('resource'));
    }

    public function download(Request $request, int $resourceItem): StreamedResponse
    {
        $resource = ResourceItem::visibleTo($request->user())->findOrFail($resourceItem);

        return $this->fileResponse($request, $resource);
    }

    private function fileResponse(Request $request, ResourceItem $resource): StreamedResponse
    {
        abort_unless($resource->hasDownloadableFile(), 404);
        if ($resource->dropbox_file_id) {
            $temporary = tempnam(sys_get_temp_dir(), 'dropbox-');
            try {
                app(DropboxClient::class)->downloadTo($resource->dropbox_file_id, $temporary);
            } catch (\Throwable) {
                unlink($temporary);
                abort(502, 'Dropbox download is currently unavailable. Please try again.');
            }
            if ($request->isMethod('GET')) {
                ResourceDownload::create(['user_id' => $request->user()->id, 'resource_item_id' => $resource->id]);
            }

            return response()->streamDownload(function () use ($temporary): void {
                try {
                    readfile($temporary);
                } finally {
                    unlink($temporary);
                }
            }, basename($resource->file_path), ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
        }
        abort_unless(Storage::disk('local')->exists($resource->file_path), 404);

        if ($request->isMethod('GET')) {
            ResourceDownload::create(['user_id' => $request->user()->id, 'resource_item_id' => $resource->id]);
        }

        return Storage::disk('local')->download($resource->file_path, basename($resource->file_path), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadArchive(Request $request, string $slug): BinaryFileResponse
    {
        $game = Game::visibleTo($request->user())->where('slug', $slug)->firstOrFail();
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:50'], 'ids.*' => ['required', 'integer', 'distinct']]);
        $resources = $game->resources()->visibleTo($request->user())->whereIn('id', $data['ids'])->get();
        abort_unless($resources->count() === count($data['ids']), 404);

        return $this->createAssetArchive($request, $resources);
    }

    public function downloadBasket(Request $request): BinaryFileResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:50'], 'ids.*' => ['required', 'integer', 'distinct']]);
        $resources = ResourceItem::visibleTo($request->user())->whereIn('id', $data['ids'])
            ->whereIn('kind', ['download', 'certificate'])->get();
        abort_unless($resources->count() === count($data['ids']), 404);

        return $this->createAssetArchive($request, $resources);
    }

    private function createAssetArchive(Request $request, Collection $resources): BinaryFileResponse
    {
        $total = 0;
        foreach ($resources as $resource) {
            abort_unless($resource->hasDownloadableFile(), 404);
            if ($resource->dropbox_file_id) {
                $total += $resource->file_size;
            } else {
                abort_unless(Storage::disk('local')->exists($resource->file_path), 404);
                $total += Storage::disk('local')->size($resource->file_path);
            }
        }
        abort_if($total > 100 * 1024 * 1024, 422, 'Select fewer files. The archive limit is 100 MB.');
        $path = tempnam(sys_get_temp_dir(), 'smartsoft-assets-');
        $zip = new ZipArchive;
        $temporaryFiles = [];
        $actualSize = 0;
        $zipOpen = false;
        try {
            if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Cannot create archive.');
            }
            $zipOpen = true;
            foreach ($resources as $resource) {
                $source = Storage::disk('local')->path($resource->file_path);
                if ($resource->dropbox_file_id) {
                    $source = tempnam(sys_get_temp_dir(), 'dropbox-zip-');
                    $temporaryFiles[] = $source;
                    try {
                        app(DropboxClient::class)->downloadTo($resource->dropbox_file_id, $source, 100 * 1024 * 1024 - $actualSize);
                    } catch (\Throwable) {
                        abort(502, 'Dropbox archive could not be prepared. Please retry or select fewer files.');
                    }
                }
                $actualSize += filesize($source);
                abort_if($actualSize > 100 * 1024 * 1024, 422, 'Select fewer files. The archive limit is 100 MB.');
                if (! $zip->addFile($source, $resource->id.'-'.basename($resource->file_path))) {
                    throw new \RuntimeException('Cannot add resource to archive.');
                }
            }
            if (! $zip->close()) {
                throw new \RuntimeException('Cannot finish archive.');
            }
            $zipOpen = false;
            foreach ($resources as $resource) {
                ResourceDownload::create(['user_id' => $request->user()->id, 'resource_item_id' => $resource->id]);
            }
        } catch (\Throwable $exception) {
            if ($zipOpen) {
                $zip->close();
            }
            if (is_file($path)) {
                unlink($path);
            }
            throw $exception;
        } finally {
            foreach ($temporaryFiles as $temporary) {
                if (is_file($temporary)) {
                    unlink($temporary);
                }
            }
        }

        return response()->download($path, 'game-assets.zip', ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])->deleteFileAfterSend(true);
    }

    public function updates(Request $request): View
    {
        return view('client.updates', ['announcements' => Announcement::visibleTo($request->user())->latest()->paginate(12)]);
    }

    public function roadmap(Request $request): View
    {
        $user = $request->user();
        $items = RoadmapItem::visibleTo($user)->with(['game', 'region'])->orderByRaw('target_date ASC NULLS LAST')->orderBy('id')->paginate(12);
        $upcomingItems = RoadmapItem::visibleTo($user)->where('status', '!=', 'released')->with('game')->orderByRaw('target_date ASC NULLS LAST')->orderBy('id')->limit(2)->get();
        $regionalItems = RoadmapItem::visibleTo($user)->whereNotNull('region_id')->with(['game', 'region'])->orderByRaw('target_date ASC NULLS LAST')->get()->groupBy('region_id');
        $openableGameIds = Game::visibleTo($user)->whereHas('roadmapItems', fn ($query) => $query->visibleTo($user))->pluck('id');
        $regionalGames = Game::roadmapVisibleTo($user)->whereHas('roadmapItems', fn ($query) => $query->visibleTo($user))
            ->with(['regionAvailabilities' => fn ($query) => $query->when(! $user->is_admin, fn ($query) => $query->whereIn('region_id', $user->accessibleRegionIds()))->with('region')])->orderBy('title')->get();
        $announcements = Announcement::visibleTo($user)->where('show_on_roadmap', true)->latest()->limit(6)->get();

        return view('client.roadmap', compact('items', 'openableGameIds', 'regionalGames', 'announcements', 'upcomingItems', 'regionalItems'));
    }

    public function tools(Request $request): View
    {
        return view('client.tools', [
            'tools' => EngagementTool::visibleTo($request->user())->orderBy('title')->paginate(12),
            'documents' => ResourceItem::visibleTo($request->user())->where('kind', 'documentation')
                ->where('show_on_engagement_tools', true)->with('catalogOption')->orderBy('title')->get(),
        ]);
    }
}
