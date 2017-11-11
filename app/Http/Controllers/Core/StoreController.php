<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Store;
use App\Core\QueryFilter\StoreFilter;
use App\Core\Responses\Store\ManageResponse;
use Exception;

abstract class StoreController extends Controller
{
    public function __construct()
    {
        $this->middleware($this->authMiddleware());
    }

    public function index(Request $request, StoreFilter $query)
    {
        try {
            $itemPerPage = $request->get('items_per_page', Store::ITEMS_PER_PAGE);
            $stores = Store::filterBy($query)
                ->with(['partner'])->paginate($itemPerPage)->toArray();

            return ManageResponse::listStoreResponse('success', $stores);
        } catch (Exception $e) {
            return ManageResponse::listStoreResponse('error');
        }
    }

    public function show(Store $store, Request $request)
    {
        try {
            $store = $store->load(['partner', 'vegetables.images', 'images'])->toArray();

            $store['vegetables'] = collect($store['vegetables'])->transform(function ($item) {
                $images = [];
                if (empty($item['images'])) {
                    $images[] = url('public/' . config('upload.path.default') . '/' . config('upload.default.vegetable_image'));
                } else {
                    $images = collect($item['images'])->transform(function ($image) {
                        return url($image['src']);
                    })->toArray();
                }
                $item['images'] = $images;
                return $item;
            })->toArray();

            $store['images'] = collect($store['images'])->pluck('src')->toArray();

            return ManageResponse::showStoreResponse('success', $store);
        } catch (Exception $e) {
            return ManageResponse::showStoreResponse('error');
        }
    }

    protected function orderByDistance($stores, $latitude, $longitude)
    {
        $match = '(POW((latitude-?),2) + POW((longitude-?),2))';
        $stores = $stores->orderByRaw($match . " ASC", [$latitude, $longitude]);
        return $stores;
    }
}
