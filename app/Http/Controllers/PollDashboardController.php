<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PollDashboardController extends Controller
{
    // __invoke = "controller à action unique" : la classe ne fait qu'UNE chose.
    // Comme la route pointe sur la classe seule (Route::get('/polls/dashboard', PollDashboardController::class)),
    // Laravel appelle automatiquement cette méthode __invoke() — pas besoin de nommer une méthode.
    public function __invoke(Request $request)
    {
        // on récupère les sondages du user connecté, avec leurs options,
        // triés par date de création décroissante 
        $polls = $request->user()->polls()
            ->with('options')
            ->orderBy('created_at', 'desc')
            ->get();

        // on renvoie la vue Blade dashboard.blade.php,
        // en lui passant $polls (qui sera injecté dans window.__PROPS__ côté HTML).
        // C'est une route WEB → on renvoie du HTML, pas du JSON.
        return view('polls.dashboard', [
            'polls' => $polls,
        ]);
    }
}
