<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Repositories\TranslationInterface;

class TranslationController extends Controller
{
    public function __construct(
        private TranslationInterface $translations
    ) {}

    /**
     * @OA\Post(
     *    path="/api/translations",
     *    summary="Create a new translation",
     *    tags={"Translations"},
     *    @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *            required={"key","locale_id","content"},
     *            @OA\Property(property="key", type="string"),
     *            @OA\Property(property="locale_id", type="integer"),
     *            @OA\Property(property="content", type="string"),
     *            @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *        )
     *    ),
     *    @OA\Response(response=201, description="Translation created")
     * )
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'key' => 'required',
            'locale_id' => 'required|exists:locales,id',
            'content' => 'required',
            'tags' => 'array'
        ]);

        return response()->json(
            $this->translations->create($data),
            201
        );
    }

    /**
     * @OA\Get(
     *    path="/api/translations/search",
     *    summary="Search translations",
     *    tags={"Translations"},
     *    @OA\Parameter(name="key", in="query", description="Search by key"),
     *    @OA\Response(response=200, description="List of translations")
     * )
     */
    public function search(Request $request)
    {
        return $this->translations->search($request->all());
    }

    /**
     * @OA\Get(
     *    path="/api/translations/export",
     *    summary="Export translations by locale",
     *    tags={"Translations"},
     *    @OA\Parameter(name="locale", in="query", description="Locale code", required=true),
     *    @OA\Response(response=200, description="Exported translations")
     * )
     */
    public function export(Request $request)
    {
        return response()->json(
            $this->translations->exportByLocale($request->locale)
        );
    }
}
