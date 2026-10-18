<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MediaCollectionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\NewsRequest;
use App\Models\News;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function index(): View
    {
        return $this->form();
    }

    public function edit(News $news): View
    {
        return $this->form($news);
    }

    public function store(NewsRequest $request): RedirectResponse
    {
        $this->save(new News, $request);

        return to_route('admin.news.index')->with('status', 'News-Beitrag gespeichert //');
    }

    public function update(NewsRequest $request, News $news): RedirectResponse
    {
        $this->save($news, $request);

        return to_route('admin.news.index')->with('status', 'News-Beitrag aktualisiert //');
    }

    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return to_route('admin.news.index')->with('status', 'News-Beitrag gelöscht //');
    }

    private function form(?News $editing = null): View
    {
        return view('admin.news', [
            'news' => News::query()->latest('id')->get(),
            'editing' => $editing,
        ]);
    }

    private function save(News $news, NewsRequest $request): void
    {
        $news->fill($request->safe()->except(['image', 'remove_image']));

        $news->save();

        if ($request->hasFile('image')) {
            $news->addMediaFromRequest('image')->toMediaCollection(MediaCollectionEnum::News->value);
        } elseif ($request->boolean('remove_image')) {
            $news->clearMediaCollection(MediaCollectionEnum::News->value);
        }
    }
}
