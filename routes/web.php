<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return [
        'status' => 'normal',
        'message' => 'Server work normally!',
    ];
});

Route::group(['prefix' => 'test'], function () {
    Route::get('partner', function () {
        return view('test/partner');
    });
    Route::get('device', function () {
        return view('test/device');
    });
    Route::group(['prefix' => 'checkout'], function () {
        Route::get('success', function () {
            return view('checkout.success', [
                'order' => App\Models\Order::with('items.vegetablesInStore.vegetable')
                    ->first()
                    ->toArray(),
            ]);
        });
        Route::get('cancel', function () {
            return view('checkout.cancel');
        });
    });
});

Route::group(['namespace' => 'Admin', 'prefix' => 'admin'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
    });

    Route::group(['prefix' => 'stores'], function () {
        Route::get('/', 'StoreController@index');
        Route::post('/', 'StoreController@create');
        Route::group(['prefix' => '{store}'], function () {
            Route::get('/', 'StoreController@show');
            Route::post('/', 'StoreController@update');
            Route::get('devices', 'StoreController@devices');
            Route::post('delete', 'StoreController@delete');
            Route::post('add-trunks', 'StoreController@addTrunks');
            Route::group(['prefix' => 'trunks'], function () {
                Route::get('/', 'TrunkController@index');
                Route::get('status', 'TrunkController@trunksStatus');
                Route::post('status', 'TrunkController@updateTrunksStatus');
            });
        });
    });

    Route::group(['prefix' => 'partners'], function () {
        Route::get('/', 'PartnerController@index');
        Route::post('/', 'PartnerController@create');
        Route::group(['prefix' => '{partner}'], function () {
            Route::get('/', 'PartnerController@show');
        });
    });

    Route::group(['prefix' => 'users'], function () {
        Route::get('/', 'UserController@index');
    });

    Route::group(['prefix' => 'vegetables'], function () {
        Route::get('/', 'VegetableController@index');
        Route::post('/', 'VegetableController@create');
        Route::group(['prefix' => '{vegetable}'], function () {
            Route::get('/', 'VegetableController@show');
        });
    });

    Route::any('data/{type}', 'MasterDataController@index');
});

Route::group(['namespace' => 'User'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
        Route::post('register', 'SessionController@register');
    });

    Route::group(['prefix' => 'vegetables'], function () {
        Route::get('/', 'VegetableController@index');
    });

    Route::group(['prefix' => 'stores'], function () {
        Route::any('/', 'StoreController@index');
        Route::get('{store}', 'StoreController@show');
    });

    Route::group(['prefix' => 'orders'], function () {
        Route::get('/', 'OrderController@index');
        Route::get('{order}', 'OrderController@show');
    });

    Route::group(['prefix' => 'cart'], function () {
        Route::get('/', 'CartController@index');
        Route::get('delete', 'CartController@deleteAll');
        Route::group(['prefix' => 'checkout'], function () {
            Route::get('/', 'CartController@checkout');
            Route::get('return', 'CartController@checkoutReturn');
            Route::get('cancel', 'CartController@checkoutCancel');
        });
        Route::group(['prefix' => 'items'], function () {
            Route::get('/', 'CartController@index');
            Route::post('/', 'CartController@addItem');
            Route::post('delete', 'CartController@deleteItems');
            Route::post('{item}', 'CartController@updateItem');
        });
    });
});

Route::group(['namespace' => 'Partner', 'prefix' => 'partner'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
    });

    Route::group(['prefix' => 'stores'], function () {
        Route::get('/', 'StoreController@index');
        Route::group(['prefix' => '{store}'], function () {
            Route::get('/', 'StoreController@show');
            Route::post('/', 'StoreController@update');
            Route::get('devices', 'StoreController@devices');
            Route::group(['prefix' => 'trunks'], function () {
                Route::get('/', 'TrunkController@index');
                Route::get('status', 'TrunkController@trunksStatus');
                Route::post('status', 'TrunkController@updateTrunksStatus');
            });
            Route::group(['prefix' => 'orders'], function () {
                Route::get('active', 'StoreController@getActiveOrders');
                Route::group(['prefix' => '{date}'], function () {
                    Route::get('month', 'StoreController@getMonthlyOrders');
                    Route::get('week', 'StoreController@getWeeklyOrders');
                    Route::get('day/{endDate}', 'StoreController@getDaylyOrders');
                });
            });
        });
    });

    Route::group(['prefix' => 'vegetables'], function () {
        Route::get('/', 'VegetableController@index');
    });

    Route::any('data/{type}', 'MasterDataController@index');
});

Route::group(['namespace' => 'Device', 'prefix' => 'device'], function () {
    Route::group(['namespace' => 'Auth', 'prefix' => 'session'], function () {
        Route::any('/', 'SessionController@index');
        Route::post('login', 'SessionController@login');
        Route::post('logout', 'SessionController@logout');
    });
});
