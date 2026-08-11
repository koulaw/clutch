<?php

namespace App\Http\Controllers;

use App\Http\Resources\AnalysisProgressResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DemoAnalysisController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $demos = $request->user()->demos()
            ->with('latestAnalysis.demo')
            ->latest()
            ->limit(20)
            ->get();

        return AnalysisProgressResource::collection(
            $demos->pluck('latestAnalysis')->filter()->values(),
        );
    }
}
