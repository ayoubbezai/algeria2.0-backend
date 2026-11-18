<?php

namespace App\Http\Controllers;

use App\Models\CaseFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaseFileController extends Controller
{
    /**
     * List all accessible cases or cases summary.
     */
    public function index(): JsonResponse
    {
        $cases = CaseFile::select('id', 'case_number', 'tag', 'title', 'owner', 'status', 'restricted')->get();

        return response()->json([
            'cases' => $cases,
        ]);
    }

    /**
     * Case details endpoint demonstrating IDOR vulnerability / fix.
     * Accessible via GET /api/cases/show?id=1042 or GET /api/case?id=1042
     */
    public function show(Request $request): JsonResponse
    {
        $caseId = $request->query('id', '1042');
        $applyFix = $request->boolean('fix', false);

        // Find by case_number or primary id
        $case = CaseFile::where('case_number', $caseId)
            ->orWhere('id', $caseId)
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => "No case file found for #{$caseId}.",
            ], 404);
        }

        // Current authenticated investigator context (or mock default #118)
        $currentInvestigatorId = $request->user()?->id ?? 1; // default Investigator #118

        // If fix is enabled, enforce server-side ownership / access control
        if ($applyFix) {
            // Investigator #118 can only view their own cases unless authorized
            if ($case->restricted && $case->owner_id !== $currentInvestigatorId) {
                return response()->json([
                    'success' => false,
                    'message' => "Access denied. Case #{$caseId} is not assigned to investigator #118. (Ownership check enforced on server).",
                    'fix_applied' => true,
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'case' => [
                'id' => $case->case_number,
                'tag' => $case->tag,
                'title' => $case->title,
                'owner' => $case->owner,
                'status' => $case->status,
                'notes' => $case->notes,
                'evidence' => $case->evidence,
                'restricted' => $case->restricted,
            ],
            'leaked' => $case->restricted && !$applyFix,
            'fix_applied' => $applyFix,
        ]);
    }
}

