<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Post;
use App\Models\Cadre;
use App\Models\Candidate;

class ContentController extends Controller
{
    public function index()
    {
        $posts = Post::orderBy('cadre_code', 'ASC')->get();
        $cadres = Cadre::orderBy('cadre_code', 'ASC')->get();

        return view('dashboard', [
            'posts' => $posts,
            'cadres' => $cadres,
        ]);
    }

    public function candidates()
    {
        $candidates = Candidate::all();

        return view('candidates', [
            'candidates' => $candidates,
        ]);
    }

    public function allocations()
    {
        $candidates = Candidate::where('assigned_cadre', '!=', '')->orderBy('assigned_cadre', 'ASC')->get();

        return view('allocations', [
            'candidates' => $candidates,
        ]);
    }

    public function allocations_print()
    {
        $candidates = Candidate::where('assigned_cadre', '!=', '')->orderBy('assigned_cadre', 'ASC')->get();

        return view('allocations-print', [
            'candidates' => $candidates,
        ]);
    }
}
