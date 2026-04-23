<?php

namespace App\Http\Controllers;

use App\Models\EducationArticle;
use Illuminate\Http\Request;

class AcademyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $articles = EducationArticle::active()
            ->search($search)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        $stats = [
            'glossary'  => EducationArticle::active()->category('glossary')->count(),
            'workflow'   => EducationArticle::active()->category('workflow')->count(),
            'tutorial'   => EducationArticle::active()->category('tutorial')->count(),
        ];

        return view('academy.index', compact('articles', 'stats', 'search'));
    }

    public function glossary(Request $request)
    {
        $search = $request->input('search');
        $letter = $request->input('letter');

        $query = EducationArticle::active()->category('glossary')
            ->search($search)
            ->orderBy('title');

        if ($letter) {
            $query->where('title', 'like', "{$letter}%");
        }

        $articles = $query->get();

        // Group by first letter for A-Z nav
        $grouped = $articles->groupBy(fn($a) => strtoupper(mb_substr($a->title, 0, 1)));

        // All available first letters
        $allLetters = EducationArticle::active()->category('glossary')
            ->get(['title'])
            ->map(fn($a) => strtoupper(mb_substr($a->title, 0, 1)))
            ->unique()
            ->sort()
            ->values();

        return view('academy.glossary', compact('grouped', 'allLetters', 'search', 'letter'));
    }

    public function workflows()
    {
        $articles = EducationArticle::active()
            ->category('workflow')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        return view('academy.workflows', compact('articles'));
    }

    public function show(EducationArticle $article)
    {
        abort_unless($article->is_active, 404);

        $related = EducationArticle::active()
            ->where('category', $article->category)
            ->where('id', '!=', $article->id)
            ->orderBy('sort_order')
            ->limit(5)
            ->get();

        return view('academy.show', compact('article', 'related'));
    }

    public function tooltip(Request $request)
    {
        $slug = $request->input('slug');

        if (! $slug) {
            return response()->json(['content' => null], 404);
        }

        $article = EducationArticle::active()
            ->where('slug', $slug)
            ->first(['title', 'content']);

        if (! $article) {
            return response()->json(['content' => null], 404);
        }

        // Return first paragraph as tooltip text
        $firstParagraph = \Illuminate\Support\Str::before($article->content, "\n\n");
        $plain = strip_tags(\Illuminate\Support\Str::markdown($firstParagraph, [
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]));

        return response()->json([
            'title'   => $article->title,
            'content' => trim($plain),
        ]);
    }
}
