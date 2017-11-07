<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Core\StoreController as BaseController;
use App\Models\Store;
use App\Models\Order;
use Exception;
use App\Http\Controllers\Core\Contracts\StoreManageContract;
use App\Http\Controllers\Core\Traits\StoreManageTrait;
use App\Core\QueryFilter\StoreFilter;
use App\Core\Responses\ManageResponse;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StoreController extends BaseController implements StoreManageContract
{
    use StoreManageTrait;

    protected $guard = 'partner';
    protected $updateFields = ['logo', 'name', 'address', 'phone_number', 'info', 'latitude', 'longitude'];

    protected function validateUpdateRequest($request, $store)
    {
        if ($request->user()->id !== $store->partner_id) {
            return ManageResponse::cantContinue();
        }

        $updateRules = [
            'logo' => 'string',
            'name' => 'string',
            'address' => 'required:string',
            'phone_number' => 'numeric',
            'info' => 'max:50000',
            'latitude' => 'numeric',
            'longitude' => 'numeric',
        ];

        return $this->validate($request, $updateRules);
    }

    public function index(Request $request, StoreFilter $query)
    {
        $user = $request->user();
        $itemPerPage = $request->get('items_per_page', Store::ITEMS_PER_PAGE);
        $all = $request->get('all', 0);
        if ($all) {
            $stores = [
                'data' => Store::where('partner_id', $user->id)->with('images')->get()->toArray(),
            ];
        } else {
            $stores = Store::filterBy($query)->where('partner_id', $user->id)->with('images')->paginate($itemPerPage)->toArray();
        }

        return ManageResponse::response(
            'success',
            trans('response.list_success', ['name' => trans('name.store')]),
            $stores
        );
    }

    public function show(Store $store, Request $request)
    {
        $user = $request->user();
        if ($user->id !== $store->partner_id) {
            return ManageResponse::cantContinue();
        }

        return ManageResponse::response(
            'success',
            trans('response.list_success', ['name' => trans('name.store')]),
            $store->load(['vegetables.images'])->toArray()
        );
    }

    public function devices(Store $store, Request $request)
    {
        if ($request->user()->id !== $store->partner_id) {
            return ManageResponse::cantContinue();
        }

        return ManageResponse::response(
            'success',
            config('response.get_store_detail_success'),
            ['data' => $store->load([
                'devices' => function ($query) {
                    $query->with([
                        'category' => function ($q) {
                            $q->select(['id', 'name', 'symbol']);
                        }
                    ]);
                }
            ])]
        );
    }

    public function getActiveOrders(Store $store)
    {
        return ManageResponse::response(
            'success',
            trans('response.list_success', ['name' => trans('name.order')]),
            ['data' => $this->getOrderWithStatus($store, Order::ORDER_PROCESSING)->toArray()]
        );
    }

    public function getMonthlyOrders(Store $store, $date, Request $request)
    {
        if ($request->user()->id !== $store->partner_id) {
            return ManageResponse::cantContinue();
        }
        $date = Carbon::parse($date);
        $startDateString = $date->startOfMonth()->toDateString();
        $endDateString = $date->endOfMonth()->toDateString();
        return $this->getOrderByDay($store, $startDateString, $endDateString);
    }

    public function getWeeklyOrders(Store $store, $date, Request $request)
    {
        if ($request->user()->id !== $store->partner_id) {
            return ManageResponse::cantContinue();
        }
        $date = Carbon::parse($date);
        $startDateString = $date->startOfWeek()->toDateString();
        $endDateString = $date->endOfWeek()->toDateString();
        return $this->getOrderByDay($store, $startDateString, $endDateString);
    }

    public function getDaylyOrders(Store $store, $date, $endDate, Request $request)
    {
        if ($request->user()->id !== $store->partner_id) {
            return ManageResponse::cantContinue();
        }
        $date = Carbon::parse($date);
        $endDate = Carbon::parse($endDate);
        $startDateString = $date->toDateString();
        $endDateString = $endDate->toDateString();
        return $this->getOrderByDay($store, $startDateString, $endDateString);
    }

    protected function getOrderByDay(Store $store, string $startDateString, string $endDateString)
    {
        $vegetablesInStore = $store->vegetablesInStore()
            ->with([
                'orderItems' => function ($query) use ($startDateString, $endDateString) {
                    $query->whereDate('created_at', '>=' , $startDateString)
                        ->whereDate('created_at', '<=' , $endDateString)
                        ->whereExists(function ($qr) {
                            $qr->from('orders')
                                ->where('status', Order::ORDER_COMPLETED)
                                ->whereRaw('orders.id = order_items.order_id');
                        });
                },
                'vegetable' => function ($query) {
                    $query->select(['id', 'name']);
                },
            ])
            ->get()
            ->toArray();
        $orderItems = $this->getOrderItems($vegetablesInStore);
        $orderItemsInDays = $this->groupOrderItemsByDay($orderItems);
        $dataResult = $this->calcPrice($orderItemsInDays);

        return ManageResponse::response(
            'success',
            trans('response.list_success', ['name' => trans('name.order')]),
            ['data' => $dataResult->toArray()]
        );
    }

    protected function getOrderWithStatus(Store $store, $status)
    {
        $vegetableInStore = $store->vegetablesInStore()->get()->pluck('id');
        $orders = Order::where('status', $status)
            ->whereExists(function ($qr) use ($vegetableInStore) {
                $qr->from('order_items')
                    ->whereIn('vegetable_in_store_id', $vegetableInStore)
                    ->whereRaw('orders.id = order_items.order_id');
            })
            ->with('items.vegetablesInStore.vegetable.images')
            ->get();
        Order::where('status', $status)
            ->whereExists(function ($qr) use ($vegetableInStore) {
                $qr->from('order_items')
                    ->whereIn('vegetable_in_store_id', $vegetableInStore)
                    ->whereRaw('orders.id = order_items.order_id');
            })
            ->update(['status', Order::ORDER_COMPLETED]);
        return $orders;
    }

    protected function getOrderItems(array $vegetablesInStore)
    {
        $orderItems = [];
        foreach ($vegetablesInStore as $vegetableInStore) {
            foreach ($vegetableInStore['order_items'] as $item) {
                $item['vegetable'] = $vegetableInStore['vegetable'];
                $item['vegetable_price'] = $vegetableInStore['price'];
                $item['total_price'] = $item['quantity'] * $item['vegetable_price'];
                $orderItems[] = $item;
            }
        }
        return $orderItems;
    }

    protected function groupOrderItemsByDay(array $orderItems)
    {
        return collect($orderItems)->groupBy(function ($item) {
            return substr($item['created_at'], 0, 10);
        });
    }

    protected function calcPrice(Collection $orderItemsInDays)
    {
        return $orderItemsInDays->map(function ($item) {
            $totalPrice = 0;
            $item = $item->toArray();
            foreach ($item as $value) {
                $totalPrice += $value['total_price'];
            }

            return [
                'total_price' => $totalPrice,
                'items' => $item,
            ];
        });
    }
}
