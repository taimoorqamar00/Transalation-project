<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Repositories\TranslationInterface;
use App\Models\Translation;
use Illuminate\Validation\Rule;

/**
 * @OA\Info(
 *      title="Translation Management API",
 *      version="1.0.0",
 *      description="High-performance translation management service with multi-locale support, caching, and scalable architecture.",
 *      @OA\Contact(
 *          email="support@translation-api.com"
 *      ),
 *      @OA\License(
 *          name="MIT",
 *          url="https://opensource.org/licenses/MIT"
 *      )
 * )
 * 
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Translation API Server"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="API authentication endpoints"
 * )
 * 
 * @OA\Tag(
 *     name="Translations",
 *     description="Translation management endpoints"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="sanctum",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter your Bearer token"
 * )
 */
class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationInterface $translations
    ) {
    }

    /**
     * @OA\Post(
     *    path="/api/translations",
     *    summary="Create a new translation",
     *    tags={"Translations"},
     *    security={{"sanctum":{}}},
     *    @OA\RequestBody(
     *        required=true,
     *        @OA\JsonContent(
     *            required={"key","locale_id","content"},
     *            @OA\Property(property="key", type="string", example="welcome.message"),
     *            @OA\Property(property="locale_id", type="integer", example=1),
     *            @OA\Property(property="content", type="string", example="Welcome to our app!"),
     *            @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *        )
     *    ),
     *    @OA\Response(response=201, description="Translation created successfully")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|max:255',
            'locale_id' => 'required|integer|exists:locales,id',
            'content' => 'required|string',
            'tags' => 'array',
            'tags.*' => 'integer|exists:tags,id'
        ]);

        $translation = $this->translations->create($validated);

        return response()->json([
            'success' => true,
            'data' => $translation
        ], 201);
    }

    /**
     * @OA\Get(
     *    path="/api/translations/{id}",
     *    summary="Get a specific translation",
     *    tags={"Translations"},
     *    security={{"sanctum":{}}},
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\Response(response=200, description="Translation details")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $translation = $this->translations->findById($id);

        if (!$translation) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $translation
        ]);
    }

    /**
     * @OA\Put(
     *    path="/api/translations/{id}",
     *    summary="Update a translation",
     *    tags={"Translations"},
     *    security={{"sanctum":{}}},
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\RequestBody(
     *        @OA\JsonContent(
     *            @OA\Property(property="key", type="string"),
     *            @OA\Property(property="content", type="string"),
     *            @OA\Property(property="tags", type="array", @OA\Items(type="integer"))
     *        )
     *    ),
     *    @OA\Response(response=200, description="Translation updated successfully")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $translation = $this->translations->findById($id);

        if (!$translation) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found'
            ], 404);
        }

        $validated = $request->validate([
            'key' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'tags' => 'sometimes|array',
            'tags.*' => 'integer|exists:tags,id'
        ]);

        $updatedTranslation = $this->translations->update($translation, $validated);

        return response()->json([
            'success' => true,
            'data' => $updatedTranslation
        ]);
    }

    /**
     * @OA\Delete(
     *    path="/api/translations/{id}",
     *    summary="Delete a translation",
     *    tags={"Translations"},
     *    security={{"sanctum":{}}},
     *    @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *    @OA\Response(response=204, description="Translation deleted successfully")
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $translation = $this->translations->findById($id);

        if (!$translation) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found'
            ], 404);
        }

        $this->translations->delete($translation);

        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *    path="/api/translations/search",
     *    summary="Search translations",
     *    tags={"Translations"},
     *    security={{"sanctum":{}}},
     *    @OA\Parameter(name="key", in="query", description="Search by key"),
     *    @OA\Parameter(name="content", in="query", description="Search by content"),
     *    @OA\Parameter(name="locale", in="query", description="Filter by locale code"),
     *    @OA\Parameter(name="tag", in="query", description="Filter by tag name"),
     *    @OA\Parameter(name="per_page", in="query", description="Items per page"),
     *    @OA\Response(response=200, description="Paginated list of translations")
     * )
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'key' => 'sometimes|string',
            'content' => 'sometimes|string',
            'locale' => 'sometimes|string|exists:locales,code',
            'tag' => 'sometimes|string|exists:tags,name',
            'per_page' => 'sometimes|integer|min:1|max:100'
        ]);

        $results = $this->translations->search($filters);

        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }

    /**
     * @OA\Get(
     *    path="/api/translations/export",
     *    summary="Export translations by locale",
     *    tags={"Translations"},
     *    security={{"sanctum":{}}},
     *    @OA\Parameter(name="locale", in="query", description="Locale code", required=true),
     *    @OA\Response(response=200, description="Exported translations in JSON format")
     * )
     */
    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'locale' => 'required|string|exists:locales,code'
        ]);

        $translations = $this->translations->exportByLocale($validated['locale']);

        return response()->json([
            'success' => true,
            'data' => $translations
        ])->header('Cache-Control', 'public, max-age=3600');
    }
}
