<?php

namespace App\Http\Controllers;

use App\Models\EvidenceItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EvidenceController extends Controller
{
    /**
     * List evidence items.
     */
    public function index(): JsonResponse
    {
        $items = EvidenceItem::with('caseFile:id,case_number,title')->get();

        return response()->json([
            'evidence' => $items,
        ]);
    }

    /**
     * Evidence URL import interface demonstrating SSRF vulnerability / fix.
     * POST or GET /api/evidence/fetch
     */
    public function fetch(Request $request): JsonResponse
    {
        $url = trim($request->input('url', ''));
        $applyFix = $request->boolean('fix', false);

        if (empty($url)) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide an evidence URL to fetch.',
            ], 422);
        }

        // Check if destination is internal/private (metadata, localhost, 10.x, 192.168.x)
        $isInternal = (bool) preg_match('/169\.254\.169\.254|localhost|127\.0\.0\.1|10\.\d+\.\d+\.\d+|192\.168\.|internal/i', $url);

        // Allow-list for secure mode
        $allowedDomains = ['picsum.photos', 'images.unsplash.com', 'via.placeholder.com'];
        $host = parse_url($url, PHP_URL_HOST);

        if ($applyFix) {
            // Secure SSRF defense: enforce whitelist of allowed external domains & protocol
            $isWhitelisted = $host && in_array(strtolower($host), $allowedDomains, true);
            $scheme = parse_url($url, PHP_URL_SCHEME);

            if (!$isWhitelisted || !in_array(strtolower($scheme ?? ''), ['http', 'https'], true) || $isInternal) {
                return response()->json([
                    'danger' => false,
                    'title' => 'BLOCKED',
                    'body' => "Request refused: \"{$url}\" is not on the approved image-host list. (SSRF mitigation active).",
                    'fix_applied' => true,
                ], 403);
            }
        }

        // Vulnerable behavior: If internal endpoint is requested and fix is NOT applied, leak internal service response
        if ($isInternal) {
            return response()->json([
                'danger' => true,
                'title' => 'INTERNAL RESPONSE — NOT AN IMAGE',
                'body' => "HTTP 200 from {$url}\n\n{\n  \"role\": \"admin\",\n  \"service\": \"casefile404-internal-daemon\",\n  \"auth_token\": \"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...\",\n  \"note\": \"CRITICAL: Internal microservice exposed via unvalidated server-side URL request.\"\n}",
                'fix_applied' => false,
            ]);
        }

        // Simulated successful external image load
        return response()->json([
            'danger' => false,
            'title' => 'IMAGE LOADED',
            'image' => $url,
            'fix_applied' => $applyFix,
        ]);
    }
}

