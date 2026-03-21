<?php

namespace App\Http\Controllers;

use App\Models\SignalRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignalRuleController extends Controller
{
    public function index()
    {
        $rules = SignalRule::where('user_id', Auth::id())
            ->orderBy('active', 'desc')
            ->orderBy('horizon')
            ->orderBy('name')
            ->get();

        return view('signal-rules.index', compact('rules'));
    }

    public function create()
    {
        return view('signal-rules.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'horizon' => 'required|in:short,medium,long',
            'fast_ma' => 'required|integer|min:2|max:500',
            'slow_ma' => 'required|integer|min:2|max:500',
            'active' => 'nullable',
        ]);

        if ((int)$data['fast_ma'] >= (int)$data['slow_ma']) {
            return back()
                ->withErrors(['fast_ma' => 'La rápida debe ser menor que la lenta (ej: 20 y 50).'])
                ->withInput();
        }

        SignalRule::create([
            'user_id' => Auth::id(),
            'name' => $data['name'],
            'horizon' => $data['horizon'],
            'fast_ma' => $data['fast_ma'],
            'slow_ma' => $data['slow_ma'],
            'active' => $request->has('active'),
        ]);

        return redirect()->route('signal-rules.index')->with('ok', 'Regla creada');
    }

    public function edit(SignalRule $signal_rule)
    {
        if ($signal_rule->user_id != Auth::id()) abort(403);

        return view('signal-rules.edit', ['rule' => $signal_rule]);
    }

    public function update(Request $request, SignalRule $signal_rule)
    {
        if ($signal_rule->user_id != Auth::id()) abort(403);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'horizon' => 'required|in:short,medium,long',
            'fast_ma' => 'required|integer|min:2|max:500',
            'slow_ma' => 'required|integer|min:2|max:500',
            'active' => 'nullable',
        ]);

        if ((int)$data['fast_ma'] >= (int)$data['slow_ma']) {
            return back()
                ->withErrors(['fast_ma' => 'La rápida debe ser menor que la lenta (ej: 20 y 50).'])
                ->withInput();
        }

        $signal_rule->update([
            'name' => $data['name'],
            'horizon' => $data['horizon'],
            'fast_ma' => $data['fast_ma'],
            'slow_ma' => $data['slow_ma'],
            'active' => $request->has('active'),
        ]);

        return redirect()->route('signal-rules.index')->with('ok', 'Regla actualizada');
    }

    public function destroy(SignalRule $signal_rule)
    {
        if ($signal_rule->user_id != Auth::id()) abort(403);

        $signal_rule->delete();

        return redirect()->route('signal-rules.index')->with('ok', 'Regla eliminada');
    }
}
