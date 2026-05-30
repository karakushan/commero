<?php

namespace Commero\Http\Controllers;

use Commero\Models\OrderStatus;
use Commero\Models\User;
use Commero\Services\WishlistService;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    /**
     * Display the user's account dashboard.
     */
    public function index(WishlistService $wishlistService)
    {
        $authUser = Auth::user();

        if (! $authUser) {
            return redirect()->route('login');
        }

        $user = User::query()->findOrFail($authUser->id);

        return view('shophats::pages.account.index', [
            'user' => $user,
            'orderStatuses' => OrderStatus::query()
                ->withTranslationsFor(app()->getLocale())
                ->get(['id', 'code', 'name', 'color', 'text_color', 'icon'])
                ->mapWithKeys(fn (OrderStatus $status): array => [
                    $status->code => [
                        'name' => $status->name,
                        'color' => $status->color,
                        'text_color' => $status->text_color,
                        'icon' => $status->icon,
                    ],
                ])
                ->all(),
            'wishlist' => $wishlistService->getWishlistForUser($user),
            'initialAccountTab' => request()->string('wtab')->toString() === 'wishlist'
                ? 'wishlist'
                : (request()->integer('page', 1) > 1 ? 'orders' : 'personal-info'),
        ]);
    }
}
