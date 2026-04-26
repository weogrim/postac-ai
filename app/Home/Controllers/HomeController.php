<?php

declare(strict_types=1);

namespace App\Home\Controllers;

use App\Character\Models\CharacterModel;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController
{
    public function index(Request $request): View
    {
        $characters = CharacterModel::query()
            ->with(['author', 'media'])
            ->latest()
            ->paginate(24);

        if ($request->header('HX-Request') === 'true' && $request->query('page') !== null) {
            return view('partials._character-grid-page', ['characters' => $characters]);
        }

        return view('home', ['characters' => $characters]);
    }
}
