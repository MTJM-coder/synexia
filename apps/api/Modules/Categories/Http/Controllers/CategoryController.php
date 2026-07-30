<?php

namespace Modules\Categories\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Categories\Http\Requests\StoreCategoryRequest;
use Modules\Categories\Http\Requests\UpdateCategoryRequest;
use Modules\Categories\Http\Resources\CategoryResource;
use Modules\Categories\Models\Category;
use Modules\Identity\Models\User;
use Modules\Marketplace\Models\Shop;

/**
 * Module "simple" (voir ARCHITECTURE.md) : pas de Service/Domain séparé —
 * les invariants de hiérarchie vivent directement ici, en méthodes privées
 * dédiées, plutôt que dispersés dans le corps de store()/update().
 *
 * Autorisation v1 : propriétaire de boutique uniquement (décision validée).
 * v2 prévue : un employé pourra aussi gérer les catégories s'il a la
 * permission "catalog.categories.manage" côté Identity. authorizeWrite()
 * est le SEUL endroit à modifier pour ce passage — aucune autre méthode
 * de ce contrôleur ne doit jamais vérifier une autorisation directement.
 */
class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::with('children')->whereNull('parent_id');

        if ($request->filled('shop_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('shop_id', $request->input('shop_id'))->orWhereNull('shop_id');
            });
        } else {
            $query->whereNull('shop_id');
        }

        return CategoryResource::collection(
            $query->where('is_active', true)->orderBy('sort_order')->get()
        )->response();
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $shopId = $request->validated('shop_id');
        $this->authorizeWrite($request, $shopId);

        try {
            $this->assertValidParent($request->validated('parent_id'), $shopId);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $category = Category::create([
            ...$request->validated(),
            'slug' => Str::slug($request->validated('name')).'-'.Str::random(6),
        ]);

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function show(Category $category): JsonResponse
    {
        return (new CategoryResource($category->load('children')))->response();
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorizeWrite($request, $category->shop_id);

        if (array_key_exists('parent_id', $request->validated())) {
            try {
                $this->assertValidParent($request->validated('parent_id'), $category->shop_id, $category->id);
            } catch (\DomainException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        }

        $category->update($request->validated());

        return (new CategoryResource($category->fresh()))->response();
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorizeWrite($request, $category->shop_id);

        if ($category->children()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie qui a des sous-catégories. Déplacez ou supprimez-les d\'abord.',
            ], 422);
        }

        $category->delete();

        return response()->json(['message' => 'Catégorie supprimée.']);
    }

    /**
     * Invariant 1 : le parent doit appartenir au même scope (même boutique,
     * ou globale si $shopId est null) — une catégorie globale ne peut pas
     * avoir un parent de boutique, et inversement.
     * Invariant 2 : pas de référence circulaire — une catégorie ne peut
     * jamais devenir son propre ancêtre, directement ou via une chaîne de
     * parents.
     */
    private function assertValidParent(?int $parentId, ?int $shopId, ?int $categoryId = null): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === $categoryId) {
            throw new \DomainException('Une catégorie ne peut pas être son propre parent.');
        }

        $parent = Category::find($parentId);

        if ($parent === null) {
            throw new \DomainException('La catégorie parente indiquée n\'existe pas.');
        }

        if ($parent->shop_id !== $shopId) {
            throw new \DomainException(
                $shopId === null
                    ? 'Une catégorie globale ne peut pas avoir un parent propre à une boutique.'
                    : 'Le parent doit appartenir exactement à la même boutique que la catégorie.'
            );
        }

        if ($categoryId !== null) {
            $current = $parent;

            while ($current !== null) {
                if ($current->id === $categoryId) {
                    throw new \DomainException('Ce changement créerait une référence circulaire dans la hiérarchie.');
                }

                $current = $current->parent;
            }
        }
    }

    /**
     * SEUL point d'autorisation du contrôleur — v1 : propriétaire
     * uniquement. v2 (prévue) : ajouter ici la vérification de la
     * permission "catalog.categories.manage" via
     * Modules\Identity\Contracts\PermissionResolverContract, en plus (pas
     * à la place) de la vérification propriétaire — aucun autre changement
     * requis ailleurs dans ce fichier.
     */
    private function authorizeWrite(Request $request, ?int $shopId): void
    {
        /** @var User|null $user */
        $user = $request->user();
        abort_if($user === null, 401);

        if ($shopId === null) {
            abort_unless($user->is_super_admin, 403, 'Seul un Super Admin peut gérer une catégorie globale.');

            return;
        }

        $isOwner = Shop::where('id', $shopId)->where('owner_id', $user->id)->exists();
        abort_unless($user->is_super_admin || $isOwner, 403);
    }
}
