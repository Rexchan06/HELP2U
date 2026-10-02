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
        $request->user()->supportRequests()->create($request->validated());

        return redirect()
            ->route('support-requests.index')
            ->with('success', 'Your support request has been submitted. We are now finding volunteers for you.');
    }
}
