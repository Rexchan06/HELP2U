<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportRequestRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportRequestController extends Controller
{
    /**
     * Display the student's support requests (history).
     */
    public function index(Request $request): View
    {
        $supportRequests = $this->currentUser($request)
            ->supportRequests()
            ->with('category')
            ->latest()
            ->paginate(10);

        return view('support-requests.index', compact('supportRequests'));
    }

    /**
     * Show the form for creating a new support request.
     */
    public function create(): View
    {
        $categories = Category::orderBy('name')->get();

        return view('support-requests.create', compact('categories'));
    }

    /**
     * Store a newly created support request and start volunteer matching.
     */
    public function store(StoreSupportRequestRequest $request): RedirectResponse
    {
        $this->currentUser($request)->supportRequests()->create($request->validated());

        return redirect()
            ->route('support-requests.index')
            ->with('success', 'Your support request has been submitted. We are now finding volunteers for you.');
    }

    /**
     * Resolve the acting user. Falls back to a seeded dev user until
     * the authentication module is ready and routes are re-protected.
     */
    private function currentUser(Request $request): User
    {
        return $request->user() ?? User::first() ?? User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
