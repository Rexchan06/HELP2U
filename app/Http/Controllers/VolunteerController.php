<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchVolunteersRequest;
use App\Models\VolunteerProfile;
use App\Services\VolunteerSearchService;
use Illuminate\View\View;

class VolunteerController extends Controller
{
    /**
     * Display the filterable volunteer directory.
     */
    public function index(SearchVolunteersRequest $request, VolunteerSearchService $search): View
    {
        $supportRequest = $request->supportRequestContext();
        $filters = $request->filters();

        if ($supportRequest) {
            // Backend skill filter: only volunteers covering the request's category.
            $filters['category_id'] = $supportRequest->category_id;

            // A fresh redirect pre-checks the mode the student asked for;
            // once they touch the filters themselves we respect their choice.
            if (! $request->hasFilterInput()) {
                $filters['modes'] = [$supportRequest->support_mode->value];
            }
        }

        return view('volunteers.index', [
            'volunteers' => $search->search($filters),
            'filters' => $filters,
            'supportRequest' => $supportRequest,
        ]);
    }

    /**
     * Display a single volunteer's profile (bio, skills, weekly availability).
     */
    public function show(VolunteerProfile $volunteer, VolunteerSearchService $search): View
    {
        abort_unless($volunteer->is_active, 404);

        return view('volunteers.show', [
            'volunteer' => $search->findForDisplay($volunteer->id),
        ]);
    }
}
