<?php

namespace App\Http\Controllers\Researcher;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ResearchKeyController extends Controller
{
    /**
     * Set or rotate the researcher's research key (the send-authorisation secret).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'research_key' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $request->user()->update([
            'research_key_hash' => Hash::make($validated['research_key']),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Research key berhasil disimpan.']);

        return to_route('send-simulation.create');
    }
}
