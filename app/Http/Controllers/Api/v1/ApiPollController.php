<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiPollController extends Controller
{
    /**
     * Liste les sondages de l'utilisateur connecté avec leurs options et le nombre de votes. retourne les sondages en JSON
     */
    public function index(Request $request)
    {
        $polls = $request->user()
            ->polls()
            ->with('options')
            ->orderBy('created_at', 'desc')
            ->get();

        return $polls;
    }

    /**
     * Affiche un sondage via son token secret (route publique).
     * Ajoute has_voted (utilisateur connecté) et is_open (date de fin non dépassée).
     */
    public function show(Request $request, string $token)
    {
        // on cherche le sondage qui correspond au token, avec ses options et pour chaque option on compte ses votes
        // si on trouve pas, on lance une erreur 
        $poll = Poll::with(['options' => function ($query) {
            $query->withCount('votes');
        }])->where('secret_token', $token)->firstOrFail();

        // Le sondage est ouvert s'il n'a pas de date de fin ou si elle n'est pas dépassée
        // lt = less than
        $poll->is_open = !$poll->ends_at || now()->lt($poll->ends_at);

        // Vérifie si l'utilisateur connecté a déjà voté
        if ($request->user()) {
            // Il y a un utilisateur connecté → on vérifie s'il a voté
            $poll->has_voted = $poll->votes()->where('user_id', $request->user()->id)->exists();
        } else {
            // Personne non-connectée → forcément pas voté
            $poll->has_voted = false;
        }

        return $poll;
    }

    /**
     * Crée un nouveau sondage avec ses options.
     * Génère un token secret unique.
     * Si lancé directement (is_draft = false), enregistre started_at et calcule ends_at.
     */
    public function store(Request $request)
    {
        // règles pour chaque champ
        $validated = $request->validate([
            'title'                  => 'nullable|string|max:255',
            'question'               => 'required|string|max:255',
            'is_draft'               => 'boolean',
            'allow_multiple_choices' => 'boolean',
            // 'allow_vote_change'      => 'boolean', // désactivé : option non utilisée (ajout après coup)
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
            'options'                => 'required|array|min:2',
            'options.*.label'        => 'required|string|max:255',
        ]);

        $poll = new Poll();
        $poll->title                  = $validated['title'] ?? null;
        $poll->question               = $validated['question'];
        // généré auto
        $poll->secret_token           = Str::random(32);
        // par défaut en brouillon
        $poll->is_draft               = $validated['is_draft'] ?? true;
        $poll->allow_multiple_choices = $validated['allow_multiple_choices'] ?? false;
        // $poll->allow_vote_change      = $validated['allow_vote_change'] ?? false; // désactivé : option non utilisée (ajout après coup)
        $poll->results_public         = $validated['results_public'] ?? false;
        $poll->duration               = $validated['duration'] ?? null;

        // Si lancé directement (pas brouillon), enregistre la date de début et calcule la date de fin
        if (!$poll->is_draft) {
            $poll->started_at = now();
            if ($poll->duration) {
                $poll->ends_at = now()->addSeconds($poll->duration);
            }
        }

        // on associe l'id de l'user qui a fait la requête à user_id du poll
        $poll->user_id = $request->user()->id;
        $poll->save();

        // Crée les options associées au sondage
        // create([]) remplit plusieurs colonnes d'un coup. 
        // dans PollOption, c'est défini quelles colonnes peuvent être remplies en mm temps grâce à "fillable"
        foreach ($validated['options'] as $option) {
            $poll->options()->create(['label' => $option['label']]);
        }

        // load recharge les options depuis la base
        return $poll->load('options');
    }

    /**
     * Met à jour un sondage appartenant à l'utilisateur connecté.
     * Si le sondage passe de brouillon à lancé, enregistre started_at et calcule ends_at.
     */
    public function update(Request $request, int $id)
    {
        $poll = Poll::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'title'                  => 'nullable|string|max:255',
            'question'               => 'required|string|max:255',
            'is_draft'               => 'boolean',
            'allow_multiple_choices' => 'boolean',
            // 'allow_vote_change'      => 'boolean', // désactivé : option non utilisée (ajout après coup)
            'results_public'         => 'boolean',
            'duration'               => 'nullable|integer|min:1',
            'options'                => 'required|array|min:2',
            'options.*.label'         => 'required|string|max:255',
        ]);

        // Si le sondage vient d'être lancé (était brouillon avant)
        // 1. était brouillon avant (valeur actuelle BDD) 
        // 2. requête envoie bien is_draft 
        // 3. on le met à false (on lance)
        if ($poll->is_draft && isset($validated['is_draft']) && !$validated['is_draft']) {
            $poll->started_at = now();
            if (!empty($validated['duration'])) {
                $poll->ends_at = now()->addSeconds($validated['duration']);
            }
        }

        $poll->title                  = $validated['title'] ?? null;
        $poll->question               = $validated['question'];
        $poll->is_draft               = $validated['is_draft'] ?? $poll->is_draft;
        $poll->allow_multiple_choices = $validated['allow_multiple_choices'] ?? $poll->allow_multiple_choices;
        // $poll->allow_vote_change      = $validated['allow_vote_change'] ?? $poll->allow_vote_change; // désactivé : option non utilisée (ajout après coup)
        $poll->results_public         = $validated['results_public'] ?? $poll->results_public;
        $poll->duration               = $validated['duration'] ?? $poll->duration;
        $poll->save();

        // Remplace toutes les options
        $poll->options()->delete();

        // Le foreach crée chaque option en base, liée au poll via poll_id.
        // Le load('options') recharge les options depuis la base après leur création, pour les inclure dans la réponse JSON. 
        // Sans ça, $poll->options serait vide dans la réponse.
        foreach ($validated['options'] as $option) {
            $poll->options()->create(['label' => $option['label']]);
        }

        return $poll->load('options');
    }

    /**
     * Supprime un sondage appartenant à l'utilisateur connecté.
     */
    public function remove(Request $request, int $id)
    {
        $poll = Poll::where('user_id', $request->user()->id)->findOrFail($id);
        $poll->delete();

        //  renvoie une réponse JSON de succès pour confirmer la suppression au front 
        return response()->json(['message' => 'success'], 200);
    }

    /**
     * Soumet un vote pour un sondage via son token (utilisateur connecté requis).
     * Garantit qu'un utilisateur ne vote qu'une seule fois (choix unique ou multiple).
     */
    public function vote(Request $request, string $token)
    {
        $poll = Poll::where('secret_token', $token)->firstOrFail();

        // Vérifie que le sondage est ouvert
        if ($poll->is_draft) {
            return response()->json(['message' => 'Ce sondage n\'est pas encore ouvert.'], 403);
        }
        // gt = greater than
        if ($poll->ends_at && now()->gt($poll->ends_at)) {
            return response()->json(['message' => 'Ce sondage est terminé.'], 403);
        }

        // min une option choisie 
        $validated = $request->validate([
            'option_ids'   => 'required|array|min:1',
            // pour chaque élément du tab option_ids, il doit être un entier
            // et doit exister dans la colonne id de la table poll_options (vérification en bdd)
            'option_ids.*' => 'integer|exists:poll_options,id',
        ]);

        // AJOUT (après coup) : empêche de voter plusieurs fois, quel que soit le type de sondage.
        // Avant, ce contrôle était à l'intérieur du if (!allow_multiple_choices),
        // donc un sondage à choix multiple pouvait recevoir plusieurs votes du même utilisateur.
        if ($poll->votes()->where('user_id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Vous avez déjà voté.'], 422);
        }

        // Choix unique : un seul choix autorisé par vote
        if (!$poll->allow_multiple_choices && count($validated['option_ids']) > 1) {
            return response()->json(['message' => 'Ce sondage n\'accepte qu\'un seul choix.'], 422);
        }

        // Vérifie que les options appartiennent bien à ce sondage
        // pluck extrait uniquement colonne id de toutes options du sondage et en fait tableau
        // pour vérifier que chaque option votée appartient bien au sondage
        $validOptionIds = $poll->options()->pluck('id')->toArray();
        foreach ($validated['option_ids'] as $optionId) {
            if (!in_array($optionId, $validOptionIds)) {
                return response()->json(['message' => 'Option invalide.'], 422);
            }
        }

        // Enregistre les votes
        foreach ($validated['option_ids'] as $optionId) {
            $vote = new \App\Models\PollVote();
            $vote->user_id        = $request->user()->id;
            $vote->poll_option_id = $optionId;
            $poll->votes()->save($vote);
        }

        return response()->json(['message' => 'Vote enregistré.'], 201);
    }
}