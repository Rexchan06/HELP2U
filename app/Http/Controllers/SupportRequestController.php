<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSupportRequestRequest;
use App\Models\Category;
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
        $supportRequests = $request->user()
            ->supportRequests()
            ->with('category', 'volunteerMatches.supportSession', 'volunteerMatches.volunteerProfile.user')
            ->latest()
            ->paginate(10);

        // Volunteers also see the requests students sent to them, below their own.
        $incomingMatches = $request->user()->volunteerProfile
            ?->volunteerMatches()
                ->with(['supportRequest.category', 'supportRequest.user'])
                ->latest()
                ->paginate(10, ['*'], 'incoming_page');

        return view('support-requests.index', compact('supportRequests', 'incomingMatches'));
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
        $supportRequest = $request->user()->supportRequests()->create($request->validated());

        return redirect()
            ->route('volunteers.index', ['support_request' => $supportRequest->id])
            ->with('success', 'Your support request has been submitted. Here are volunteers who can help.');
    }
}