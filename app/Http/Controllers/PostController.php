<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Contracts\Actions\Models\LoadsRelatedPosts;
use App\Contracts\Actions\Resources\ProvidesPostResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Wink\WinkPost;

final class PostController extends Controller
{
    public function __construct(
        private ProvidesPostResource $postResourceProvider,
        private LoadsRelatedPosts $relatedPostLoader
    ) {
    }

    public function index(Request $request): Response
    {
        $posts = WinkPost::query()
            ->with('author')
            ->published()
            ->live()
            ->paginate(10)
            ->through(fn (WinkPost $post) => $this->postResourceProvider->for($post, $request));

        return Inertia::render('Blog', ['posts' => $posts]);
    }

    public function show(Request $request, WinkPost $post): Response
    {
        abort_unless($post->published, ResponseCode::HTTP_NOT_FOUND);

        abort_if($post->publish_date->isFuture(), ResponseCode::HTTP_NOT_FOUND);

        $relatedPosts = $this->relatedPostLoader->handle($post)->limit(3)->get();

        return Inertia::render('Post', [
            'post' => $this->postResourceProvider->for($post, $request),
            'related_posts' => $this->postResourceProvider->forAll($relatedPosts, $request),
        ]);
    }
}
