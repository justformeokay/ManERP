<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class UserGuideController extends Controller
{
    private const CHAPTERS = [
        '00-TABLE-OF-CONTENTS',
        '01-NAVIGATION-AND-LANGUAGE',
        '02-SECURITY-AND-AUTHENTICATION',
        '03-CORE-WORKFLOWS',
        '04-FINANCIAL-REPORTS-AND-PAYROLL',
        '05-ADMINISTRATION-AND-MAINTENANCE',
        '06-GLOSSARY-AND-TERMINOLOGY',
    ];

    /**
     * Show the table of contents (index page).
     */
    public function index()
    {
        $chapters = $this->getChapterList();

        return view('user-guide.index', compact('chapters'));
    }

    /**
     * Show a specific chapter.
     */
    public function show(string $chapter)
    {
        if (! in_array($chapter, self::CHAPTERS, true)) {
            abort(404);
        }

        $path = base_path("docs/user-guide/{$chapter}.md");

        if (! File::exists($path)) {
            abort(404);
        }

        $markdown = File::get($path);
        $chapters = $this->getChapterList();

        return view('user-guide.show', compact('markdown', 'chapter', 'chapters'));
    }

    /**
     * Build chapter list with labels and slugs.
     */
    private function getChapterList(): array
    {
        $list = [];

        foreach (self::CHAPTERS as $slug) {
            if ($slug === '00-TABLE-OF-CONTENTS') {
                continue;
            }

            $number = (int) substr($slug, 0, 2);
            $label  = str_replace('-', ' ', substr($slug, 3));
            $label  = ucwords(strtolower($label));

            $list[] = [
                'slug'   => $slug,
                'number' => $number,
                'label'  => $label,
            ];
        }

        return $list;
    }
}
